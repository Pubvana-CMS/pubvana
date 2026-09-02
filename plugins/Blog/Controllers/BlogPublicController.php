<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Controllers;

use Pubvana\Controllers\Public\PublicController;
use Pubvana\Plugins\Blog\Models\Post;

/**
 * BlogPublicController - Public-facing blog routes.
 *
 * Listing, single post, category, tag, and preview.
 *
 * @package Pubvana\Plugins\Blog\Controllers
 */
class BlogPublicController extends PublicController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.blog');
    }

    /**
     * Paginated blog listing.
     *
     * The current page arrives either as a clean route segment
     * (/blog/page/@page) or, for backward compatibility, the
     * ?page= query string.
     *
     * @param string|null $page Route page number segment, if any
     */
    public function index(?string $page = null): void
    {
        $pageNum = max(1, (int) ($page ?? $this->app->request()->query->page ?? 1));
        $result = $this->app->blog()->listPosts($pageNum, 10, 'published');

        $posts = array_map(fn($post) => $this->formatPost($post), $result['items']);

        $this->render('home', [
            'posts'      => $posts,
            'pagination' => $this->buildPagination($result, $this->app->pluginLoader()->routePrefix('pubvana/blog')),
        ]);
    }

    /**
     * List all categories.
     */
    public function categories(): void
    {
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');
        $categories = $this->app->blog()->listCategories();
        $list = [];

        foreach ($categories as $cat) {
            $list[] = [
                'name'       => $cat->name,
                'slug'       => $cat->slug,
                'url'        => $prefix . '/category/' . $cat->slug,
                'post_count' => $cat->post_count ?? null,
            ];
        }

        $this->render('categories', [
            'categories' => $list,
        ]);
    }

    /**
     * List all tags.
     */
    public function tags(): void
    {
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');
        $tags = $this->app->blog()->listTags();
        $list = [];

        foreach ($tags as $tag) {
            $list[] = [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'url'  => $prefix . '/tag/' . $tag->slug,
            ];
        }

        $this->render('tags', [
            'tags' => $list,
        ]);
    }

    /**
     * Single published post by slug.
     */
    public function show(string $slug): void
    {
        $post = $this->app->blog()->findPostBySlug($slug);

        if ($post === null || $post->status !== 'published') {
            $this->app->halt(404, 'Post not found');
            return;
        }

        $this->app->blog()->recordView((int) $post->id);

        $categories = $this->getPostCategories((int) $post->id);
        $tags = $this->getPostTags((int) $post->id);

        $data = [
            'title'          => $post->title,
            'content'        => $post->content,
            'excerpt'        => $post->excerpt,
            'featured_image' => $this->publicAssetUrl($post->featured_image),
            'published_at'   => $post->published_at,
            'author'         => $this->getAuthor($post),
            'ai_disclosure'  => $this->aiDisclosure((int) ($post->ai_generated ?? 0)),
            'categories'     => $categories,
            'tags'           => $tags,
            'commentable'    => ['type' => 'blog', 'id' => (int) $post->id],
            'allow_comments' => (bool) $post->allow_comments,
        ];

        $this->render('post', $data);
    }

    /**
     * Posts filtered by category slug.
     */
    public function category(string $slug): void
    {
        $categories = $this->app->blog()->listCategories();
        $category = null;
        foreach ($categories as $cat) {
            if ($cat->slug === $slug) {
                $category = $cat;
                break;
            }
        }

        if ($category === null) {
            $this->app->halt(404, 'Category not found');
            return;
        }

        $page = max(1, (int) ($this->app->request()->query->page ?? 1));
        $result = $this->app->blog()->listPosts($page, 10, 'published');
        $filtered = [];

        foreach ($result['items'] as $post) {
            $postCatIds = $this->app->blog()->getPostCategoryIds((int) $post->id);
            if (in_array((int) $category->id, $postCatIds, true)) {
                $filtered[] = $this->formatPost($post);
            }
        }

        $this->render('archive', [
            'archive_title' => 'Category: ' . $category->name,
            'posts'         => $filtered,
            'pagination'    => null,
        ]);
    }

    /**
     * Posts filtered by tag slug.
     */
    public function tag(string $slug): void
    {
        $tags = $this->app->blog()->listTags();
        $tag = null;
        foreach ($tags as $item) {
            if ($item->slug === $slug) {
                $tag = $item;
                break;
            }
        }

        if ($tag === null) {
            $this->app->halt(404, 'Tag not found');
            return;
        }

        $page = max(1, (int) ($this->app->request()->query->page ?? 1));
        $result = $this->app->blog()->listPosts($page, 10, 'published');
        $filtered = [];

        foreach ($result['items'] as $post) {
            $postTagNames = $this->app->blog()->getPostTagNames((int) $post->id);
            if (in_array($tag->name, $postTagNames, true)) {
                $filtered[] = $this->formatPost($post);
            }
        }

        $this->render('archive', [
            'archive_title' => 'Tag: ' . $tag->name,
            'posts'         => $filtered,
            'pagination'    => null,
        ]);
    }

    /**
     * Preview a post by its unique preview token.
     */
    public function preview(string $token): void
    {
        $post = $this->app->blog()->findPostByPreviewToken($token);

        if ($post === null) {
            $this->app->halt(404, 'Preview not found');
            return;
        }

        $categories = $this->getPostCategories((int) $post->id);
        $tags = $this->getPostTags((int) $post->id);

        $this->render('post', [
            'title'          => $post->title . ' (Preview)',
            'content'        => $post->content,
            'excerpt'        => $post->excerpt,
            'featured_image' => $this->publicAssetUrl($post->featured_image),
            'published_at'   => $post->published_at ?? $post->created_at,
            'author'         => $this->getAuthor($post),
            'ai_disclosure'  => $this->aiDisclosure((int) ($post->ai_generated ?? 0)),
            'categories'     => $categories,
            'tags'           => $tags,
            'commentable'    => ['type' => 'blog', 'id' => (int) $post->id],
            'allow_comments' => false,
        ]);
    }

    /**
     * Format a post record into a template-ready array.
     *
     * @param Post $post
     * @return array<string, mixed>
     */
    private function formatPost(object $post): array
    {
        return [
            'id'             => (int) $post->id,
            'title'          => $post->title,
            'slug'           => $post->slug,
            'url'            => $this->app->pluginLoader()->routePrefix('pubvana/blog') . '/' . $post->slug,
            'excerpt'        => $post->excerpt,
            'featured_image' => $this->publicAssetUrl($post->featured_image),
            'published_at'   => $post->published_at,
            'author'         => $this->getAuthor($post),
            'categories'     => $this->getPostCategories((int) $post->id),
            'tags'           => $this->getPostTags((int) $post->id),
        ];
    }

    /**
     * Look up the author for a post.
     *
     * @param Post $post
     * @return array<string, mixed>|null
     */
    private function getAuthor(object $post): ?array
    {
        if (empty($post->author_id)) {
            return null;
        }

        $userId = (int) $post->author_id;

        $user = (new \Enlivenapp\FlightShield\Models\User(\Flight::db()))->findById($userId);
        if ($user === null) {
            return null;
        }

        $username = (string) $user->username;
        $displayName = '';
        try {
            $profile = $this->app->profiles()->findByUserId($userId);
            if ($profile && !empty($profile->display_name)) {
                $displayName = (string) $profile->display_name;
            }
        } catch (\Throwable) {
        }

        return [
            'id'       => $userId,
            'username' => $username,
            'name'     => $displayName !== '' ? $displayName : $username,
            'url'      => $username !== '' ? '/profile/' . $username : null,
        ];
    }

    /**
     * Get categories assigned to a post, formatted for templates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPostCategories(int $postId): array
    {
        $all = $this->app->blog()->listCategories();
        $ids = $this->app->blog()->getPostCategoryIds($postId);
        $items = [];

        foreach ($all as $category) {
            if (in_array((int) $category->id, $ids, true)) {
                $items[] = [
                    'id'   => (int) $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'url'  => $this->app->pluginLoader()->routePrefix('pubvana/blog') . '/category/' . $category->slug,
                ];
            }
        }

        return $items;
    }

    /**
     * Get tags assigned to a post, formatted for templates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPostTags(int $postId): array
    {
        $names = $this->app->blog()->getPostTagNames($postId);
        $all = $this->app->blog()->listTags();
        $items = [];

        foreach ($all as $tag) {
            if (in_array($tag->name, $names, true)) {
                $items[] = [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'url'  => $this->app->pluginLoader()->routePrefix('pubvana/blog') . '/tag/' . $tag->slug,
                ];
            }
        }

        return $items;
    }

    /**
     * Build pagination data for the shared pagination.tpl partial.
     *
     * URLs use clean, crawlable paths: /blog for page 1 and
     * /blog/page/@page for subsequent pages. Returns prev_url,
     * next_url and the page-number list expected by the partial.
     */
    /**
     * @param array<string, mixed> $result List result as returned by BlogService::listPosts()
     * @return array<string, mixed>|null
     */
    private function buildPagination(array $result, string $baseUrl): ?array
    {
        $page = (int) ($result['page'] ?? 1);
        $perPage = (int) ($result['per_page'] ?? 10);
        $total = (int) ($result['total'] ?? 0);
        $pages = (int) ceil($total / max($perPage, 1));

        if ($pages <= 1) {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/') ?: '/';

        $pageUrl = function (int $n) use ($baseUrl): string {
            return $n === 1 ? $baseUrl : $baseUrl . '/page/' . $n;
        };

        $items = [];
        for ($n = 1; $n <= $pages; $n++) {
            $items[] = [
                'number' => $n,
                'url'    => $pageUrl($n),
                'active' => $n === $page,
            ];
        }

        return [
            'current'  => $page,
            'total'    => $pages,
            'prev_url' => $page > 1 ? $pageUrl($page - 1) : null,
            'next_url' => $page < $pages ? $pageUrl($page + 1) : null,
            'pages'    => $items,
        ];
    }

    /**
     * Get a public asset URL.
     */
    private function publicAssetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/' . ltrim($path, '/');
    }

    /**
     * Whether to show the visible AI-assistance disclosure for this post.
     */
    private function aiDisclosure(int|bool $aiGenerated): bool
    {
        if (empty($aiGenerated)) {
            return false;
        }

        return (bool) $this->app->settings()->get('Seo.ai_disclosure_enabled', true);
    }

    /**
     * Generate RSS 2.0 feed.
     */
    public function rss(): void
    {
        $posts = $this->app->blog()->listPublished(1, 20);
        $xml = $this->generateRss($posts);

        $this->app->response()->header('Content-Type', 'application/rss+xml; charset=utf-8');
        $this->app->halt(200, $xml);
    }

    /**
     * Generate Atom feed.
     */
    public function atom(): void
    {
        $posts = $this->app->blog()->listPublished(1, 20);
        $xml = $this->generateAtom($posts);

        $this->app->response()->header('Content-Type', 'application/atom+xml; charset=utf-8');
        $this->app->halt(200, $xml);
    }

    /**
     * Generate RSS 2.0 XML from posts.
     *
     * @param array{items: array<int, Post>, total: int, page: int, per_page: int} $posts
     */
    private function generateRss(array $posts): string
    {
        $siteName = $this->app->settings()->get('CMS.siteName') ?? 'Blog';
        $siteUrl = $this->app->settings()->get('CMS.siteUrl') ?? $this->app->get('flight.base_url');
        $siteDescription = $this->app->settings()->get('CMS.siteByline') ?? '';
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '<title>' . htmlspecialchars($siteName) . '</title>' . "\n";
        $xml .= '<link>' . htmlspecialchars($siteUrl) . '</link>' . "\n";
        $xml .= '<description>' . htmlspecialchars($siteDescription) . '</description>' . "\n";
        $xml .= '<language>en-us</language>' . "\n";
        $xml .= '<atom:link href="' . htmlspecialchars($siteUrl . '/feed') . '" rel="self" type="application/rss+xml"/>' . "\n";

        foreach ($posts['items'] as $post) {
            $postUrl = $siteUrl . $prefix . '/' . $post->slug;
            $categories = $this->getPostCategories((int) $post->id);
            $tags = $this->getPostTags((int) $post->id);

            $xml .= '<item>' . "\n";
            $xml .= '<title>' . htmlspecialchars($post->title) . '</title>' . "\n";
            $xml .= '<link>' . htmlspecialchars($postUrl) . '</link>' . "\n";
            $xml .= '<guid isPermaLink="true">' . htmlspecialchars($postUrl) . '</guid>' . "\n";
            $xml .= '<pubDate>' . $this->feedDate($post->published_at, 'r') . '</pubDate>' . "\n";

            $author = $this->getAuthor($post);
            if ($author) {
                $xml .= '<author>' . htmlspecialchars($author['name']) . '</author>' . "\n";
            }

            foreach ($categories as $cat) {
                $xml .= '<category>' . htmlspecialchars($cat['name']) . '</category>' . "\n";
            }
            foreach ($tags as $tag) {
                $xml .= '<category>' . htmlspecialchars($tag['name']) . '</category>' . "\n";
            }

            $content = $post->content ?? $post->excerpt ?? '';
            $xml .= '<description>' . htmlspecialchars($content) . '</description>' . "\n";
            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Generate Atom XML from posts.
     *
     * @param array{items: array<int, Post>, total: int, page: int, per_page: int} $posts
     */
    private function generateAtom(array $posts): string
    {
        $siteName = $this->app->settings()->get('CMS.siteName') ?? 'Blog';
        $siteUrl = $this->app->settings()->get('CMS.siteUrl') ?? $this->app->get('flight.base_url');
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<title>' . htmlspecialchars($siteName) . '</title>' . "\n";
        $xml .= '<link href="' . htmlspecialchars($siteUrl) . '"/>' . "\n";
        $xml .= '<link href="' . htmlspecialchars($siteUrl . '/atom.xml') . '" rel="self" type="application/atom+xml"/>' . "\n";
        $xml .= '<id>' . htmlspecialchars($siteUrl . '/atom.xml') . '</id>' . "\n";

        if (!empty($posts['items'])) {
            $firstPost = $posts['items'][0];
            $xml .= '<updated>' . $this->feedDate($firstPost->updated_at ?? $firstPost->published_at, 'c') . '</updated>' . "\n";
        }

        foreach ($posts['items'] as $post) {
            $postUrl = $siteUrl . $prefix . '/' . $post->slug;
            $categories = $this->getPostCategories((int) $post->id);
            $tags = $this->getPostTags((int) $post->id);

            $xml .= '<entry>' . "\n";
            $xml .= '<title>' . htmlspecialchars($post->title) . '</title>' . "\n";
            $xml .= '<link href="' . htmlspecialchars($postUrl) . '"/>' . "\n";
            $xml .= '<id>' . htmlspecialchars($postUrl) . '</id>' . "\n";
            $xml .= '<published>' . $this->feedDate($post->published_at, 'c') . '</published>' . "\n";
            $xml .= '<updated>' . $this->feedDate($post->updated_at ?? $post->published_at, 'c') . '</updated>' . "\n";

            $author = $this->getAuthor($post);
            if ($author) {
                $xml .= '<author><name>' . htmlspecialchars($author['name']) . '</name></author>' . "\n";
            }

            foreach ($categories as $cat) {
                $xml .= '<category term="' . htmlspecialchars($cat['name']) . '"/>' . "\n";
            }
            foreach ($tags as $tag) {
                $xml .= '<category term="' . htmlspecialchars($tag['name']) . '"/>' . "\n";
            }

            $content = $post->content ?? $post->excerpt ?? '';
            $xml .= '<content type="html">' . htmlspecialchars($content) . '</content>' . "\n";
            $xml .= '</entry>' . "\n";
        }

        $xml .= '</feed>';

        return $xml;
    }

    /**
     * Format a nullable timestamp for feed output, falling back to empty string.
     */
    private function feedDate(?string $date, string $format): string
    {
        $ts = strtotime((string) $date);
        return $ts === false ? '' : date($format, $ts);
    }
}
