<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Services;

use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Blog\Models\Category;
use Pubvana\Plugins\Blog\Models\Tag;
use Pubvana\Plugins\Blog\Models\PostCategory;
use Pubvana\Plugins\Blog\Models\PostTag;
use Pubvana\Plugins\Blog\Models\PostRevision;
use Flight;

class BlogService
{
    private Post $postModel;
    private Category $categoryModel;
    private Tag $tagModel;
    private PostCategory $postCategoryModel;
    private PostTag $postTagModel;
    private PostRevision $revisionModel;
    private \PDO $pdo;
    private array $config;

    public function __construct(\PDO $pdo, array $config = [])
    {
        $this->postModel         = new Post($pdo);
        $this->categoryModel     = new Category($pdo);
        $this->tagModel          = new Tag($pdo);
        $this->postCategoryModel = new PostCategory($pdo);
        $this->postTagModel      = new PostTag($pdo);
        $this->revisionModel     = new PostRevision($pdo);
        $this->pdo               = $pdo;
        $this->config            = $config;
    }

    // ─── Posts ────────────────────────────────────────────────────────────

    public function listPosts(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        return [
            'items'    => $this->postModel->paginate($page, $perPage, $status),
            'total'    => $this->postModel->countAll($status),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    public function listPublished(int $page = 1, int $perPage = 25): array
    {
        return $this->listPosts($page, $perPage, 'published');
    }

    public function findPost(int $id): ?Post
    {
        return $this->postModel->findById($id);
    }

    public function findPostBySlug(string $slug): ?Post
    {
        return $this->postModel->findBySlug($slug);
    }

    public function findPostByPreviewToken(string $token): ?Post
    {
        return $this->postModel->findByPreviewToken($token);
    }

    public function postSlugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->postModel->slugExists($slug, $excludeId);
    }

    public function createPost(array $data, int $userId): Post
    {
        $purify = $data['purify_content'] ?? true;
        unset($data['purify_content']);

        if ($purify && !empty($data['content'])) {
            $data['content'] = $this->purifyContent($data['content']);
        }

        $data['author_id'] = $userId;
        $data['ai_generated'] = !empty($data['ai_generated']) ? 1 : 0;
        $post = $this->postModel->createRecord($data);

        $post->generatePreviewToken();

        $this->revisionModel->createFromPost($post, $userId);
        $this->pruneRevisions((int) $post->id);

        return $post;
    }

    public function updatePost(int $id, array $data, int $userId): ?Post
    {
        $purify = $data['purify_content'] ?? true;
        unset($data['purify_content']);

        if ($purify && !empty($data['content'])) {
            $data['content'] = $this->purifyContent($data['content']);
        }

        $post = $this->postModel->findById($id);
        if ($post === null) {
            return null;
        }

        $this->revisionModel->createFromPost($post, $userId);

        $post->updateRecord($data);
        $this->pruneRevisions($id);

        return $post;
    }

    public function deletePost(int $id): bool
    {
        $post = $this->postModel->findById($id);
        if ($post === null) {
            return false;
        }

        $post->softDelete();
        return true;
    }

    public function getRevisions(int $postId): array
    {
        return $this->revisionModel->getForPost($postId);
    }

    public function restoreRevision(int $postId, int $revisionId, int $userId): ?Post
    {
        $post = $this->postModel->findById($postId);
        if ($post === null) {
            return null;
        }

        $revision = $this->revisionModel->findById($revisionId);
        if ($revision === null || (int) $revision->post_id !== $postId) {
            return null;
        }

        $post->updateRecord([
            'title'   => $revision->title,
            'content' => $revision->content,
            'excerpt' => $revision->excerpt,
            'status'  => $revision->status,
        ]);

        $this->revisionModel->createFromPost($post, $userId);
        $this->pruneRevisions($postId);

        return $post;
    }

    public function recordView(int $postId): void
    {
        // Atomic increment: previously this hydrates the row with findById()
        // then writes views+1 back via ActiveRecord::save(). That is two
        // round-trips; a direct UPDATE is one, concurrent-safe, and we
        // don't need the hydrated row here.
        $this->postModel->incrementViewsDirect($postId);
    }

    // ─── Categories ───────────────────────────────────────────────────────

    public function listCategories(): array
    {
        return $this->categoryModel->getAll();
    }

    public function findCategory(int $id): ?Category
    {
        return $this->categoryModel->findById($id);
    }

    public function categorySlugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->categoryModel->slugExists($slug, $excludeId);
    }

    public function createCategory(array $data): Category
    {
        return $this->categoryModel->createRecord($data);
    }

    public function updateCategory(int $id, array $data): ?Category
    {
        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            return null;
        }

        $category->updateRecord($data);
        return $category;
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            return false;
        }

        $this->postCategoryModel->deleteForCategory($id);
        $category->delete();
        return true;
    }

    // ─── Tags ─────────────────────────────────────────────────────────────

    public function listTags(): array
    {
        return $this->tagModel->getAll();
    }

    public function findTag(int $id): ?Tag
    {
        return $this->tagModel->findById($id);
    }

    public function deleteTag(int $id): bool
    {
        $tag = $this->tagModel->findById($id);
        if ($tag === null) {
            return false;
        }

        $this->postTagModel->deleteForTag($id);
        $tag->delete();
        return true;
    }

    // ─── Taxonomy Sync ────────────────────────────────────────────────────

    public function getPostCategoryIds(int $postId): array
    {
        return $this->postCategoryModel->getCategoryIds($postId);
    }

    public function getPostTagNames(int $postId): array
    {
        $tagIds = $this->postTagModel->getTagIds($postId);
        if ($tagIds === []) {
            return [];
        }
        $tagsById = $this->tagModel->findByIds($tagIds);
        $names = [];
        foreach ($tagIds as $tagId) {
            if (isset($tagsById[$tagId])) {
                $names[] = $tagsById[$tagId]->name;
            }
        }
        return $names;
    }

    public function syncPostCategories(int $postId, array $categoryIds): void
    {
        $this->postCategoryModel->syncForPost($postId, $categoryIds);
    }

    public function syncPostTags(int $postId, string $tagsRaw): void
    {
        $names = array_filter(array_map('trim', explode(',', $tagsRaw)));
        $tagIds = [];

        foreach ($names as $name) {
            $slug = Flight::slugify($name);
            if ($slug === '') {
                continue;
            }

            $tag = $this->tagModel->findOrCreate($name, $slug);
            $tagIds[] = (int) $tag->id;
        }

        $this->postTagModel->syncForPost($postId, $tagIds);
    }

    // ─── Blocks ───────────────────────────────────────────────────────────

    public function recentPostsBlock(array $options, string $prefix): array
    {
        $count = (int) ($options['count'] ?? 5);
        $result = $this->listPosts(1, $count, 'published');
        $posts = [];
        foreach ($result['items'] as $post) {
            $posts[] = [
                'title'        => $post->title,
                'url'          => $prefix . '/' . $post->slug,
                'published_at' => $post->published_at,
            ];
        }
        return [
            'title' => $options['title'] ?? 'Recent Posts',
            'posts' => $posts,
        ];
    }

    public function categoriesBlock(array $options, string $prefix): array
    {
        $categories = $this->listCategories();
        $list = [];
        foreach ($categories as $cat) {
            $list[] = [
                'name' => $cat->name,
                'slug' => $cat->slug,
                'url'  => $prefix . '/category/' . $cat->slug,
            ];
        }
        return [
            'title'      => $options['title'] ?? 'Categories',
            'categories' => $list,
        ];
    }

    public function tagsBlock(array $options, string $prefix): array
    {
        $tags = $this->listTags();
        $list = [];
        foreach ($tags as $tag) {
            $list[] = [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'url'  => $prefix . '/tag/' . $tag->slug,
            ];
        }
        return [
            'title' => $options['title'] ?? 'Tags',
            'tags'  => $list,
        ];
    }

    public function archiveBlock(array $options, string $prefix): array
    {
        $stmt = $this->pdo->query(
            "SELECT YEAR(published_at) as y, MONTH(published_at) as m, COUNT(*) as c
             FROM posts
             WHERE status = 'published' AND deleted_at IS NULL AND published_at IS NOT NULL
             GROUP BY y, m
             ORDER BY y DESC, m DESC
             LIMIT 24"
        );
        $months = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $months[] = [
                'year'  => $row->y,
                'month' => str_pad((string) $row->m, 2, '0', STR_PAD_LEFT),
                'count' => $row->c,
                'label' => date('F Y', strtotime($row->y . '-' . $row->m . '-01')),
                'url'   => $prefix . '/archive/' . $row->y . '/' . str_pad((string) $row->m, 2, '0', STR_PAD_LEFT),
            ];
        }
        return [
            'title'  => $options['title'] ?? 'Archives',
            'months' => $months,
        ];
    }

    public function relatedPostsBlock(array $options, array $context, string $prefix): array
    {
        $postId = (int) ($context['post_id'] ?? 0);
        if ($postId <= 0) {
            return ['title' => $options['title'] ?? 'Related Posts', 'posts' => []];
        }

        $currentTagNames = $this->getPostTagNames($postId);
        $currentCategoryIds = $this->getPostCategoryIds($postId);
        $result = $this->listPosts(1, 20, 'published');
        $posts = [];

        foreach ($result['items'] as $post) {
            if ((int) $post->id === $postId) {
                continue;
            }

            $score = 0;
            $tagNames = $this->getPostTagNames((int) $post->id);
            $categoryIds = $this->getPostCategoryIds((int) $post->id);

            $score += count(array_intersect($currentTagNames, $tagNames));
            $score += count(array_intersect($currentCategoryIds, $categoryIds));

            if ($score > 0) {
                $posts[] = [
                    'title' => $post->title,
                    'url'   => $prefix . '/' . $post->slug,
                    'score' => $score,
                ];
            }
        }

        usort($posts, fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        $posts = array_slice($posts, 0, (int) ($options['count'] ?? 5));

        return [
            'title' => $options['title'] ?? 'Related Posts',
            'posts' => $posts,
        ];
    }

    // ─── Search ───────────────────────────────────────────────────────────

    public function searchProvider(string $term, string $urlPrefix): array
    {
        $posts = (new Post($this->postModel->getDatabaseConnection()))
            ->startWrap()
                ->like('title', '%' . $term . '%')
                ->like('content', '%' . $term . '%', 'or')
                ->like('excerpt', '%' . $term . '%', 'or')
            ->endWrap('OR')
            ->eq('status', 'published')
            ->isNull('deleted_at')
            ->findAll();

        $results = [];
        $words = array_filter(preg_split('/\s+/', $term));

        foreach ($posts as $post) {
            $relevance = 0;
            $titleLower = mb_strtolower((string) $post->title);
            $excerptLower = mb_strtolower((string) ($post->excerpt ?? ''));
            $contentLower = mb_strtolower((string) ($post->content ?? ''));
            $termLower = mb_strtolower($term);

            if (mb_strpos($titleLower, $termLower) !== false) {
                $relevance += 10;
            }
            if (mb_strpos($excerptLower, $termLower) !== false) {
                $relevance += 5;
            }
            if (mb_strpos($contentLower, $termLower) !== false) {
                $relevance += 3;
            }

            if (count($words) > 1) {
                foreach ($words as $word) {
                    $wordLower = mb_strtolower($word);
                    if (mb_strpos($titleLower, $wordLower) !== false) {
                        $relevance += 3;
                    }
                    if (mb_strpos($excerptLower, $wordLower) !== false) {
                        $relevance += 2;
                    }
                    if (mb_strpos($contentLower, $wordLower) !== false) {
                        $relevance += 1;
                    }
                }
            }

            $stripped = html_entity_decode(strip_tags((string) ($post->content ?? '')), ENT_QUOTES, 'UTF-8');
            $len = mb_strlen($stripped);
            $pos = mb_stripos($stripped, $term);
            if ($pos !== false) {
                $start = max(0, $pos - 80);
                $excerpt = ($start > 0 ? '...' : '') . mb_substr($stripped, $start, 200) . ($start + 200 < $len ? '...' : '');
            } elseif ($post->excerpt) {
                $excerpt = $post->excerpt;
            } else {
                $excerpt = mb_substr($stripped, 0, 200) . ($len > 200 ? '...' : '');
            }

            $results[] = [
                'title'        => $post->title,
                'url'          => $urlPrefix . '/' . $post->slug,
                'excerpt'      => $excerpt,
                'content_type' => 'Post',
                'published_at' => $post->published_at,
                'relevance'    => $relevance,
            ];
        }

        return $results;
    }

    // ─── Comments Host ──────────────────────────────────────────────────

    /**
     * Enumerate published posts for the Comments host contract.
     *
     * @return array<int, array{type: string, id: int, title: string, url: string, allow_comments: bool}>
     */
    public function commentHostItems(string $urlPrefix): array
    {
        $posts = (new Post($this->postModel->getDatabaseConnection()))
            ->eq('status', 'published')
            ->isNull('deleted_at')
            ->order('published_at DESC')
            ->findAll();

        $items = [];
        foreach ($posts as $post) {
            $items[] = [
                'type'           => 'blog',
                'id'             => (int) $post->id,
                'title'          => (string) $post->title,
                'url'            => $urlPrefix . '/' . $post->slug,
                'allow_comments' => (bool) $post->allow_comments,
            ];
        }

        return $items;
    }

    // ─── Dashboard ────────────────────────────────────────────────────────

    public function dashboardCards(): array
    {
        $published = $this->listPosts(1, 1, 'published');
        $drafts = $this->listPosts(1, 1, 'draft');
        $scheduled = $this->listPosts(1, 1, 'scheduled');

        return [
            [
                'id'          => 'published-posts',
                'label'       => 'Published Posts',
                'value'       => (int) ($published['total'] ?? 0),
                'icon'        => 'ti-article',
                'tone'        => 'success',
                'group'       => 'content',
                'href'        => '/admin/blog?status=published',
                'description' => 'Posts currently live on the site.',
            ],
            [
                'id'          => 'scheduled-posts',
                'label'       => 'Scheduled Posts',
                'value'       => (int) ($scheduled['total'] ?? 0),
                'icon'        => 'ti-calendar-time',
                'tone'        => 'info',
                'group'       => 'content',
                'href'        => '/admin/blog?status=scheduled',
                'description' => 'Posts queued to publish later.',
            ],
            [
                'id'          => 'draft-posts',
                'label'       => 'Draft Posts',
                'value'       => (int) ($drafts['total'] ?? 0),
                'icon'        => 'ti-pencil',
                'tone'        => 'warning',
                'group'       => 'content',
                'href'        => '/admin/blog?status=draft',
                'description' => 'Posts still being worked on.',
            ],
        ];
    }

    public function dashboardSections(): array
    {
        $recent = $this->listPosts(1, 5);
        $items = [];

        foreach (($recent['items'] ?? []) as $post) {
            $publishedAt = $post->published_at ? date('M j, Y g:ia', strtotime((string) $post->published_at)) : 'Not published';
            $items[] = [
                'label'    => $post->title,
                'meta'     => ucfirst((string) $post->status) . ' · ' . $publishedAt,
                'href'     => '/admin/blog/' . (int) $post->id . '/edit',
                'emphasis' => match ($post->status) {
                    'published' => 'success',
                    'scheduled' => 'info',
                    default => 'secondary',
                },
            ];
        }

        return [[
            'id'          => 'recent-posts',
            'title'       => 'Recent Posts',
            'type'        => 'list',
            'icon'        => 'ti-writing',
            'group'       => 'content',
            'href'        => '/admin/blog',
            'empty_state' => 'No blog posts have been created yet.',
            'items'       => $items,
        ]];
    }

    // ─── Private ──────────────────────────────────────────────────────────

    private function pruneRevisions(int $postId): void
    {
        $max = $this->config['max_revisions'] ?? 15;
        $this->revisionModel->pruneForPost($postId, $max);
    }

    private function purifyContent(string $html): string
    {
        if (!class_exists(\HTMLPurifier_Config::class)) {
            return $html;
        }
        $config = \HTMLPurifier_Config::create(Flight::get('html_purifier') ?? []);
        return (new \HTMLPurifier($config))->purify($html);
    }
}
