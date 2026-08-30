<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog;

use Pubvana\Plugins\Blog\Controllers\BlogPublicController;
use Pubvana\Plugins\Blog\Controllers\BlogAdminController;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Blog Plugin - Registers routes, services, menus, dashboard, blocks, and search.
 *
 * @package Pubvana\Plugins\Blog
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
            $app->map('blog', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new Services\BlogService($app->db(), $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        // Posts
        $adext->addRoutes('admin', [
            ['GET',    '/blog',              [BlogAdminController::class, 'index'],       [$authMiddleware]],
            ['GET',    '/blog/create',       [BlogAdminController::class, 'create'],      [$authMiddleware]],
            ['POST',   '/blog/store',        [BlogAdminController::class, 'store'],       [$authMiddleware]],
            ['GET',    '/blog/@id/edit',     [BlogAdminController::class, 'edit'],        [$authMiddleware]],
            ['POST',   '/blog/@id/update',   [BlogAdminController::class, 'update'],      [$authMiddleware]],
            ['POST',   '/blog/@id/delete',   [BlogAdminController::class, 'delete'],      [$authMiddleware]],
            ['GET',    '/blog/@id/revisions', [BlogAdminController::class, 'revisions'],  [$authMiddleware]],
            ['POST',   '/blog/@id/restore/@revisionId', [BlogAdminController::class, 'restore'], [$authMiddleware]],
        ], 'pubvana.blog');

        // Categories
        $adext->addRoutes('admin', [
            ['GET',    '/blog/categories',              [BlogAdminController::class, 'categories'],       [$authMiddleware]],
            ['GET',    '/blog/categories/create',       [BlogAdminController::class, 'createCategory'],   [$authMiddleware]],
            ['POST',   '/blog/categories/store',        [BlogAdminController::class, 'storeCategory'],    [$authMiddleware]],
            ['GET',    '/blog/categories/@id/edit',     [BlogAdminController::class, 'editCategory'],     [$authMiddleware]],
            ['POST',   '/blog/categories/@id/update',   [BlogAdminController::class, 'updateCategory'],   [$authMiddleware]],
            ['POST',   '/blog/categories/@id/delete',   [BlogAdminController::class, 'deleteCategory'],   [$authMiddleware]],
        ], 'pubvana.blog');

        // Tags
        $adext->addRoutes('admin', [
            ['GET',    '/blog/tags',              [BlogAdminController::class, 'tags'],       [$authMiddleware]],
            ['POST',   '/blog/tags/@id/delete',   [BlogAdminController::class, 'deleteTag'],  [$authMiddleware]],
        ], 'pubvana.blog');

        // ─── Public Routes ──────────────────────────────────────────────

        $prefix = $app->pluginLoader()->routePrefix('pubvana/blog');

        $adext->addRoutes('public', [
            ['GET',    $prefix,                   [BlogPublicController::class, 'index']],
            ['GET',    $prefix . '/page/@page',   [BlogPublicController::class, 'index']],
            ['GET',    $prefix . '/category',     [BlogPublicController::class, 'categories']],
            ['GET',    $prefix . '/category/@slug', [BlogPublicController::class, 'category']],
            ['GET',    $prefix . '/tag',          [BlogPublicController::class, 'tags']],
            ['GET',    $prefix . '/tag/@slug',    [BlogPublicController::class, 'tag']],
            ['GET',    $prefix . '/preview/@token', [BlogPublicController::class, 'preview']],
            ['GET',    $prefix . '/@slug',        [BlogPublicController::class, 'show']],
        ], 'pubvana.blog');

        // Feed routes (root paths)
        $adext->addRoutes('public', [
            ['GET', '/feed',     [BlogPublicController::class, 'rss']],
            ['GET', '/rss',      [BlogPublicController::class, 'rss']],
            ['GET', '/atom.xml', [BlogPublicController::class, 'atom']],
        ], 'pubvana.blog');

        // Auto-discovery link tags
        $siteName = $app->settings()->get('CMS.siteName') ?? 'Blog';
        $adext->register('public.head', 'other', 'pubvana.blog.feeds', [
            'output'   => '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars($siteName) . ' RSS" href="/feed">' . "\n" .
                          '<link rel="alternate" type="application/atom+xml" title="' . htmlspecialchars($siteName) . ' Atom" href="/atom.xml">',
            'priority' => 10,
        ]);

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.blog', [
            'label'    => 'Blog',
            'priority' => 20,
            'callable' => fn(array $context) => $app->blog()->dashboardCards(),
        ]);

        $adext->register('admin.dashboard', 'sections', 'pubvana.blog', [
            'label'    => 'Blog',
            'priority' => 30,
            'callable' => fn(array $context) => $app->blog()->dashboardSections(),
        ]);

        // ─── Blocks ─────────────────────────────────────────────────────

        $adext->register('block', 'available', 'pubvana.blog.recent-posts', [
            'label'       => 'Recent Posts',
            'description' => 'List of recent blog posts',
            'provider'    => fn(array $options) => $app->blog()->recentPostsBlock($options, $prefix),
            'template'    => 'pubvana/blog/public/blocks/recent-posts',
            'priority'    => 10,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Recent Posts'],
                'count' => ['type' => 'input', 'label' => 'Number of posts', 'default' => '5'],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.blog.categories', [
            'label'       => 'Categories',
            'description' => 'List of blog categories',
            'provider'    => fn(array $options) => $app->blog()->categoriesBlock($options, $prefix),
            'template'    => 'pubvana/blog/public/blocks/categories',
            'priority'    => 20,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Categories'],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.blog.tags', [
            'label'       => 'Tags',
            'description' => 'List of blog tags',
            'provider'    => fn(array $options) => $app->blog()->tagsBlock($options, $prefix),
            'template'    => 'pubvana/blog/public/blocks/tags',
            'priority'    => 30,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Tags'],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.blog.archive', [
            'label'       => 'Archive List',
            'description' => 'Posts grouped by month',
            'provider'    => fn(array $options) => $app->blog()->archiveBlock($options, $prefix),
            'template'    => 'pubvana/blog/public/blocks/archive',
            'priority'    => 40,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Archives'],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.blog.related-posts', [
            'label'       => 'Related Posts',
            'description' => 'Posts sharing tags or categories with the current post',
            'provider'    => fn(array $options, array $context = []) => $app->blog()->relatedPostsBlock($options, $context, $prefix),
            'template'    => 'pubvana/blog/public/blocks/related-posts',
            'priority'    => 50,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Related Posts'],
                'count' => ['type' => 'input', 'label' => 'Number of posts', 'default' => '5'],
            ],
        ]);

        // ─── Search ─────────────────────────────────────────────────────

        $adext->register('search', 'provider', 'pubvana.blog', [
            'label'    => 'Blog Posts',
            'callable' => fn(string $term) => $app->blog()->searchProvider($term, $prefix),
        ]);

        // ─── Comments Host ──────────────────────────────────────────────

        $adext->register('comments.host', 'content', 'pubvana.blog', [
            'label'    => 'Blog Posts',
            'callable' => fn() => $app->blog()->commentHostItems($prefix),
        ]);

        // ─── Navigation Linkable ────────────────────────────────────────

        $adext->register('nav.linkable', 'default', 'pubvana.blog', [
            'label'    => 'Blog Posts',
            'callable' => function() use ($app, $prefix) {
                $stmt = $app->db()->query(
                    "SELECT title, slug FROM posts WHERE status = 'published' AND deleted_at IS NULL ORDER BY published_at DESC"
                );
                $items = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $items[] = [
                        'label' => $row['title'],
                        'url'   => $prefix . '/' . $row['slug'],
                    ];
                }
                return $items;
            },
        ]);

        // ─── Admin CSS ──────────────────────────────────────────────────

        $adext->register('admin.css', 'default', 'pubvana.blog', [
            'url'      => '/assets/plugin/Blog/css/blog.css',
            'priority' => 20,
        ]);
    }
}
