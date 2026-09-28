<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Services;

use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Blog\Models\Category;
use Pubvana\Plugins\Blog\Models\Tag;
use Pubvana\Plugins\Blog\Models\PostCategory;
use Pubvana\Plugins\Blog\Models\PostTag;
use Pubvana\Plugins\Blog\Models\PostRevision;
use Enlivenapp\FlightShield\Models\User;
use flight\Engine;
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

    /** @var Engine<object> Flight application instance */
    private Engine $app;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param Engine<object> $app
     * @param array<string, mixed> $config
     */
    public function __construct(Engine $app, array $config = [])
    {
        $pdo = $app->db();

        $this->app                 = $app;
        $this->pdo                 = $pdo;
        $this->postModel           = new Post($pdo);
        $this->categoryModel       = new Category($pdo);
        $this->tagModel            = new Tag($pdo);
        $this->postCategoryModel   = new PostCategory($pdo);
        $this->postTagModel        = new PostTag($pdo);
        $this->revisionModel       = new PostRevision($pdo);
        $this->config              = $config;
    }

    // ─── Posts ────────────────────────────────────────────────────────────

    /**
     * @return array{items: array<int, Post>, total: int, page: int, per_page: int}
     */
    public function listPosts(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        return [
            'items'    => $this->postModel->paginate($page, $perPage, $status),
            'total'    => $this->postModel->countAll($status),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array{items: array<int, Post>, total: int, page: int, per_page: int}
     */
    public function listPublished(int $page = 1, int $perPage = 25): array
    {
        return $this->listPosts($page, $perPage, 'published');
    }

    /**
     * Paginated published posts in a category, pagination scoped to the
     * category count rather than the global post list.
     *
     * @return array{items: array<int, Post>, total: int, page: int, per_page: int}
     */
    public function listPostsByCategory(int $categoryId, int $page = 1, int $perPage = 25): array
    {
        return [
            'items'    => $this->postModel->paginateByCategory($categoryId, $page, $perPage, 'published'),
            'total'    => $this->postModel->countByCategory($categoryId, 'published'),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Paginated published posts carrying a tag.
     *
     * @return array{items: array<int, Post>, total: int, page: int, per_page: int}
     */
    public function listPostsByTag(int $tagId, int $page = 1, int $perPage = 25): array
    {
        return [
            'items'    => $this->postModel->paginateByTag($tagId, $page, $perPage, 'published'),
            'total'    => $this->postModel->countByTag($tagId, 'published'),
            'page'     => $page,
            'per_page' => $perPage,
        ];
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

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @return array<int, PostRevision>
     */
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

        // Snapshot the current (pre-restore) state first so the restore is
        // reversible; updateRecord() below would otherwise overwrite it.
        $this->revisionModel->createFromPost($post, $userId);

        $post->updateRecord([
            'title'   => $revision->title,
            'content' => $revision->content,
            'excerpt' => $revision->excerpt,
            'status'  => $revision->status,
        ]);

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

    /**
     * @return array<int, Category>
     */
    public function listCategories(): array
    {
        return $this->categoryModel->getAll();
    }

    public function findCategory(int $id): ?Category
    {
        return $this->categoryModel->findById($id);
    }

    public function findCategoryBySlug(string $slug): ?Category
    {
        return $this->categoryModel->findBySlug($slug);
    }

    public function categorySlugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->categoryModel->slugExists($slug, $excludeId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createCategory(array $data): Category
    {
        return $this->categoryModel->createRecord($data);
    }

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @return array<int, Tag>
     */
    public function listTags(): array
    {
        return $this->tagModel->getAll();
    }

    public function findTag(int $id): ?Tag
    {
        return $this->tagModel->findById($id);
    }

    public function findTagBySlug(string $slug): ?Tag
    {
        return $this->tagModel->findBySlug($slug);
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

    /**
     * Category ids for one post.
     *
     * @return array<int, int>
     */
    public function getPostCategoryIds(int $postId): array
    {
        return $this->categoryIdsForPostIds([$postId])[$postId] ?? [];
    }

    /**
     * Tag names for one post.
     *
     * @return array<int, string>
     */
    public function getPostTagNames(int $postId): array
    {
        return $this->tagNamesForPostIds([$postId])[$postId] ?? [];
    }

    /**
     * Category ids for many posts in one pivot query.
     *
     * Related Posts scores a page of candidates at once; a per-post lookup
     * would mean one query per candidate for what a single IN() answers.
     *
     * @param array<int, int> $postIds
     * @return array<int, list<int>> post id => category ids
     */
    public function categoryIdsForPostIds(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $links = (new PostCategory($this->pdo))->in('post_id', $postIds)->findAll();

        $map = [];
        foreach ($postIds as $postId) {
            $map[(int) $postId] = [];
        }
        foreach ($links as $link) {
            $map[(int) $link->post_id][] = (int) $link->category_id;
        }

        return $map;
    }

    /**
     * Tag names for many posts in two queries: the pivot rows, then the
     * referenced tags.
     *
     * @param array<int, int> $postIds
     * @return array<int, list<string>> post id => tag names
     */
    public function tagNamesForPostIds(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $links = (new PostTag($this->pdo))->in('post_id', $postIds)->findAll();

        $tagIdsByPost = [];
        $allTagIds = [];
        foreach ($links as $link) {
            $tagId = (int) $link->tag_id;
            $tagIdsByPost[(int) $link->post_id][] = $tagId;
            $allTagIds[$tagId] = true;
        }

        $tagsById = $allTagIds === [] ? [] : $this->tagModel->findByIds(array_keys($allTagIds));

        $map = [];
        foreach ($postIds as $postId) {
            $map[(int) $postId] = [];
        }
        foreach ($tagIdsByPost as $postId => $tagIds) {
            foreach ($tagIds as $tagId) {
                if (isset($tagsById[$tagId])) {
                    $map[$postId][] = $tagsById[$tagId]->name;
                }
            }
        }

        return $map;
    }

    /**
     * Formatted category items for many posts in two queries: the pivot
     * rows for every post id, then the referenced categories. Listing and
     * archive pages render taxonomies for 10+ posts; per-post lookups
     * would mean two queries per post.
     *
     * @param array<int, int> $postIds
     * @return array<int, list<array<string, mixed>>> post id => formatted category items
     */
    public function categoryItemsForPostIds(array $postIds, string $urlPrefix): array
    {
        if ($postIds === []) {
            return [];
        }

        $links = (new PostCategory($this->pdo))->in('post_id', $postIds)->findAll();

        $idsByPost = [];
        $allCategoryIds = [];
        foreach ($links as $link) {
            $categoryId = (int) $link->category_id;
            $idsByPost[(int) $link->post_id][] = $categoryId;
            $allCategoryIds[$categoryId] = true;
        }

        $categoriesById = [];
        if ($allCategoryIds !== []) {
            foreach ((new Category($this->pdo))->in('id', array_keys($allCategoryIds))->findAll() as $category) {
                $categoriesById[(int) $category->id] = $category;
            }
        }

        $map = [];
        foreach ($postIds as $postId) {
            $map[(int) $postId] = [];
        }
        foreach ($idsByPost as $postId => $categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $category = $categoriesById[$categoryId] ?? null;
                if ($category !== null) {
                    $map[$postId][] = [
                        'id'   => $categoryId,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'url'  => $urlPrefix . '/category/' . $category->slug,
                    ];
                }
            }
        }

        return $map;
    }

    /**
     * Formatted tag items for many posts in two queries.
     *
     * @param array<int, int> $postIds
     * @return array<int, list<array<string, mixed>>> post id => formatted tag items
     */
    public function tagItemsForPostIds(array $postIds, string $urlPrefix): array
    {
        if ($postIds === []) {
            return [];
        }

        $links = (new PostTag($this->pdo))->in('post_id', $postIds)->findAll();

        $idsByPost = [];
        $allTagIds = [];
        foreach ($links as $link) {
            $tagId = (int) $link->tag_id;
            $idsByPost[(int) $link->post_id][] = $tagId;
            $allTagIds[$tagId] = true;
        }

        $tagsById = [];
        if ($allTagIds !== []) {
            foreach ((new Tag($this->pdo))->in('id', array_keys($allTagIds))->findAll() as $tag) {
                $tagsById[(int) $tag->id] = $tag;
            }
        }

        $map = [];
        foreach ($postIds as $postId) {
            $map[(int) $postId] = [];
        }
        foreach ($idsByPost as $postId => $tagIds) {
            foreach ($tagIds as $tagId) {
                $tag = $tagsById[$tagId] ?? null;
                if ($tag !== null) {
                    $map[$postId][] = [
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'url'  => $urlPrefix . '/tag/' . $tag->slug,
                    ];
                }
            }
        }

        return $map;
    }

    /**
     * Author name and profile URL per user id, or null when the user is gone.
     *
     * Names and URLs come from the Profiles plugin. With Profiles disabled
     * the Shield username stands in and there is no profile URL.
     *
     * @param array<int, int> $authorIds
     * @return array<int, array{name: string, url: string|null}|null> author id => entry
     */
    public function authorItemsForIds(array $authorIds): array
    {
        $authorIds = array_values(array_unique(array_filter(array_map('intval', $authorIds), fn($id) => $id > 0)));
        if ($authorIds === []) {
            return [];
        }

        // Resolved per call, never at boot: reading a peer plugin's config
        // while plugins are still loading yields the derived fallback prefix
        // instead of the configured one.
        $authors = $this->app->pluginLoader()->isEnabled('pubvana/profiles')
            ? $this->app->profileBlock()->nameAndUrlFor($authorIds)
            : $this->usernamesFor($authorIds);

        $map = [];
        foreach ($authorIds as $authorId) {
            $map[$authorId] = $authors[$authorId] ?? null;
        }

        return $map;
    }

    /**
     * Username-only author entries, used when Profiles is not loaded.
     *
     * Soft-deleted accounts included: attribution outlives the account.
     *
     * @param array<int, int> $authorIds
     * @return array<int, array{name: string, url: string|null}>
     */
    private function usernamesFor(array $authorIds): array
    {
        /** @var array<int, User> $users */
        $users = (new User($this->pdo))->in('id', $authorIds)->findAll();

        $map = [];
        foreach ($users as $user) {
            $map[(int) $user->id] = [
                'name' => (string) $user->username,
                'url'  => null,
            ];
        }

        return $map;
    }

    /**
     * @param array<int, int> $categoryIds
     */
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
            $tagIds[(int) $tag->id] = true;
        }

        $this->postTagModel->syncForPost($postId, array_keys($tagIds));
    }

    // ─── Blocks ───────────────────────────────────────────────────────────

     /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function recentPostsBlock(array $options, string $prefix): array
    {
        $count = (int) ($options['count'] ?? 5);
        $posts = [];
        foreach ($this->postModel->publishedRecent($count) as $post) {
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

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
    */
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

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
    */
    public function tagsBlock(array $options, string $prefix): array
    {
        $tags = $this->listTags();
        $list = [];
        foreach ($tags as $tag) {
     /**
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
    */
    public function archiveBlock(array $options, string $prefix): array
    {
        // Portable month grouping: published_at stores 'Y-m-d H:i:s', so the
        // YYYY-MM prefix groups by month on MySQL, SQLite, and Postgres
        // alike (YEAR()/MONTH() are MySQL-only).
        $stmt = $this->pdo->query(
            "SELECT SUBSTR(published_at, 1, 7) as ym, COUNT(*) as c
             FROM posts
             WHERE status = 'published' AND deleted_at IS NULL AND published_at IS NOT NULL
             GROUP BY ym
             ORDER BY ym DESC
             LIMIT 24"
        );
        if ($stmt === false) {
            return ['title' => $options['title'] ?? 'Archives', 'months' => []];
        }

        $months = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            [$y, $m] = explode('-', (string) $row->ym) + [null, null];
            $ts = strtotime($row->ym . '-01');
            $months[] = [
                'year'  => $y,
                'month' => str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                'count' => $row->c,
                'label' => $ts === false ? (string) $row->ym : date('F Y', $ts),
                'url'   => $prefix . '/archive/' . $y . '/' . str_pad((string) $m, 2, '0', STR_PAD_LEFT),
            ];
        }
        return [
            'title'  => $options['title'] ?? 'Archives',
            'months' => $months,
        ];
    }

    /**
     * Posts sharing tags or categories with the current post.
     *
     * Taxonomy for the current post and every candidate is loaded in one
     * batched pass (two queries for tags, one for categories) rather than
     * two lookups per candidate.
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $context
     * @return array<string, mixed>
    */
    public function relatedPostsBlock(array $options, array $context, string $prefix): array
    {
        $postId = (int) ($context['post_id'] ?? 0);
        if ($postId <= 0) {
            return ['title' => $options['title'] ?? 'Related Posts', 'posts' => []];
        }

        $candidates = $this->postModel->publishedRecent(20);

        $candidateIds = [$postId];
        foreach ($candidates as $post) {
            $candidateIds[] = (int) $post->id;
        }

        $tagNamesByPost = $this->tagNamesForPostIds($candidateIds);
        $categoryIdsByPost = $this->categoryIdsForPostIds($candidateIds);

        $currentTagNames = $tagNamesByPost[$postId] ?? [];
        $currentCategoryIds = $categoryIdsByPost[$postId] ?? [];

        $posts = [];

        foreach ($candidates as $post) {
            $id = (int) $post->id;
            if ($id === $postId) {
                continue;
            }

            $score = count(array_intersect($currentTagNames, $tagNamesByPost[$id] ?? []))
                + count(array_intersect($currentCategoryIds, $categoryIdsByPost[$id] ?? []));

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

    /**
     * Normalized content matches for the Search plugin: published posts whose
     * title, excerpt, or body match the term.
     *
     * Finds content only. Ranking belongs to SearchService::scoreItem(), so
     * this computes no score. The stripped body rides along as `content` so
     * the service can score a body hit.
     *
     * @return array<int, array<string, mixed>>
    */
    public function searchProvider(string $term, string $urlPrefix): array
    {
        $posts = $this->postModel->searchByPattern('%' . $this->escapeLikePattern($term) . '%');

        $results = [];

        foreach ($posts as $post) {
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
                'id'           => (int) $post->id,
                'title'        => $post->title,
                'url'          => $urlPrefix . '/' . $post->slug,
                'excerpt'      => $excerpt,
                'content'      => $stripped,
                'content_type' => 'Post',
                'published_at' => $post->published_at,
            ];
        }

        return $results;
    }

    // ─── Comments Host ──────────────────────────────────────────────────

    /**
     * Enumerate published posts for the Comments host contract.
     *
     * @return array<int, array{type: string, id: int, title: string, url: string, allow_comments: bool}>
     * @return array<int, array<string, mixed>>
     */
    public function commentHostItems(string $urlPrefix): array
    {
        $items = [];
        foreach ($this->postModel->findAllPublished() as $post) {
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

    /**
     * Published posts as navigation manager Quick Add targets.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function navLinkableItems(string $urlPrefix): array
    {
        $items = [];
        foreach ($this->postModel->findAllPublished() as $post) {
            $items[] = [
                'label' => (string) $post->title,
                'url'   => $urlPrefix . '/' . (string) $post->slug,
            ];
        }
        return $items;
    }

    /**
     * Published posts for the Broken Links scanner.
     *
     * @return array<int, array{type: string, id: int, title: string, content: string}>
     */
    public function brokenLinksItems(): array
    {
        $items = [];
        foreach ($this->postModel->findAllPublished() as $post) {
            $items[] = [
                'type'    => 'post',
                'id'      => (int) $post->id,
                'title'   => (string) $post->title,
                'content' => (string) ($post->content ?? ''),
            ];
        }
        return $items;
    }

    // ─── Dashboard ────────────────────────────────────────────────────────

    private function routePrefix(): string
    {
        return rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
    }

    /**
     * @return array<int, array<string, mixed>>
    */
    public function dashboardCards(): array
    {
        $published = $this->listPosts(1, 1, 'published');
        $drafts = $this->listPosts(1, 1, 'draft');
        $scheduled = $this->listPosts(1, 1, 'scheduled');
        $prefix = $this->routePrefix();

        return [
            [
                'id'          => 'published-posts',
                'label'       => 'Published Posts',
                'value'       => (int) $published['total'],
                'icon'        => 'ti-article',
                'tone'        => 'success',
                'group'       => 'content',
                'href'        => $prefix . '?status=published',
                'description' => 'Posts currently live on the site.',
            ],
            [
                'id'          => 'scheduled-posts',
                'label'       => 'Scheduled Posts',
                'value'       => (int) $scheduled['total'],
                'icon'        => 'ti-calendar-time',
                'tone'        => 'info',
                'group'       => 'content',
                'href'        => $prefix . '?status=scheduled',
                'description' => 'Posts queued to publish later.',
            ],
            [
                'id'          => 'draft-posts',
                'label'       => 'Draft Posts',
                'value'       => (int) $drafts['total'],
                'icon'        => 'ti-pencil',
                'tone'        => 'warning',
                'group'       => 'content',
                'href'        => $prefix . '?status=draft',
                'description' => 'Posts still being worked on.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
    */
    public function dashboardSections(): array
    {
        $recent = $this->listPosts(1, 5);
        $prefix = $this->routePrefix();
        $items = [];

        foreach ($recent['items'] as $post) {
            $ts = strtotime((string) $post->published_at);
            $publishedAt = $post->published_at ? ($ts === false ? (string) $post->published_at : date('M j, Y g:ia', $ts)) : 'Not published';
            $items[] = [
                'label'    => $post->title,
                'meta'     => ucfirst((string) $post->status) . ' · ' . $publishedAt,
                'href'     => $prefix . '/' . (int) $post->id . '/edit',
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
            'href'        => $prefix,
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

    /**
     * Neutralize the LIKE wildcards % and _ so a user-supplied search term
     * matches them literally. Post::searchByPattern() carries the matching
     * ESCAPE clause.
     *
     * The escape character there is '!', so '!' is doubled first. strtr()
     * replaces without rescanning what it already emitted, but an escape
     * character must still be escaped by itself to keep the resulting pattern
     * well formed. A backslash needs no handling: it is not the escape
     * character, so it is already literal in the pattern.
     */
    private function escapeLikePattern(string $term): string
    {
        return strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']);
    }
}
