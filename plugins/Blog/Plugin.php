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
        $prefix = $app->pluginLoader()->routePrefix('pubvana/blog');
        $config['route_prefix'] = $prefix;

        $app->map('blog', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new Services\BlogService($app->db(), $config);
            }
            return $instance;
        });

        $adext = $app->adext();

        // ─── Admin Routes ──────────────────────────────────────────────

        // Posts
        $adext->addRoutes('admin', [
            ['GET',  $prefix,                              [BlogAdminController::class, 'index'],       []],
            ['GET',  $prefix . '/create',                  [BlogAdminController::class, 'create'],      []],
            ['POST', $prefix . '/store',                   [BlogAdminController::class, 'store'],       []],
            ['GET',  $prefix . '/@id/edit',                [BlogAdminController::class, 'edit'],        []],
            ['POST', $prefix . '/@id/update',              [BlogAdminController::class, 'update'],      []],
            ['POST', $prefix . '/@id/delete',              [BlogAdminController::class, 'delete'],      []],
            ['GET',  $prefix . '/@id/revisions',           [BlogAdminController::class, 'revisions'],   []],
            ['POST', $prefix . '/@id/restore/@revisionId', [BlogAdminController::class, 'restore'],     []],
        ], 'pubvana.blog');

        // Categories
        $adext->addRoutes('admin', [
            ['GET',  $prefix . '/categories',              [BlogAdminController::class, 'categories'],     []],
            ['GET',  $prefix . '/categories/create',       [BlogAdminController::class, 'createCategory'], []],
            ['POST', $prefix . '/categories/store',        [BlogAdminController::class, 'storeCategory'],  []],
            ['GET',  $prefix . '/categories/@id/edit',     [BlogAdminController::class, 'editCategory'],   []],
            ['POST', $prefix . '/categories/@id/update',   [BlogAdminController::class, 'updateCategory'], []],
            ['POST', $prefix . '/categories/@id/delete',   [BlogAdminController::class, 'deleteCategory'], []],
        ], 'pubvana.blog');

        // Tags
        $adext->addRoutes('admin', [
            ['GET',  $prefix . '/tags',              [BlogAdminController::class, 'tags'],      []],
            ['POST', $prefix . '/tags/@id/delete',   [BlogAdminController::class, 'deleteTag'], []],
        ], 'pubvana.blog');

        // ─── Public Routes ──────────────────────────────────────────────

        $adext->addRoutes('public', [
            ['GET',    $prefix,                   [BlogPublicController::class, 'index']],
            ['GET',    $prefix . '/page/@page',   [BlogPublicController::class, 'index']],
            ['GET',    $prefix . '/category',     [BlogPublicController::class, 'categories']],
            ['GET',    $prefix . '/category/@slug/page/@page', [BlogPublicController::class, 'category']],
            ['GET',    $prefix . '/category/@slug', [BlogPublicController::class, 'category']],
            ['GET',    $prefix . '/tag',          [BlogPublicController::class, 'tags']],
            ['GET',    $prefix . '/tag/@slug/page/@page', [BlogPublicController::class, 'tag']],
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

        // ─── Homepage ───────────────────────────────────────────────────

        // Blog offers itself as a front page candidate. The token follows the
        // plugin's routePrepend, so the stored value and the public URL prefix
        // stay in step. Priority 20 puts it first in the Settings Homepage
        // select, which is also what serves "/" when the setting is unset or
        // names a provider that is no longer registered.
        $adext->register('homepage', 'provider', 'pubvana.blog', [
            'label'    => 'Blog Feed',
            'token'    => trim($prefix, '/'),
            'priority' => 20,
            'callable' => function () use ($app): bool {
                (new BlogPublicController($app))->index();

                return true;
            },
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
            'template'    => 'recent-posts.tpl',
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
            'template'    => 'categories.tpl',
            'priority'    => 20,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Categories'],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.blog.tags', [
            'label'       => 'Tags',
            'description' => 'List of blog tags',
            'provider'    => fn(array $options) => $app->blog()->tagsBlock($options, $prefix),
            'template'    => 'tags.tpl',
            'priority'    => 30,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Tags'],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.blog.archive', [
            'label'       => 'Archive List',
            'description' => 'Posts grouped by month',
            'provider'    => fn(array $options) => $app->blog()->archiveBlock($options, $prefix),
            'template'    => 'archive.tpl',
            'priority'    => 40,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Archives'],
            ],
        ]);

        $adext->register('block', 'available', 'pubvana.blog.related-posts', [
            'label'       => 'Related Posts',
            'description' => 'Posts sharing tags or categories with the current post',
            'provider'    => fn(array $options, array $context = []) => $app->blog()->relatedPostsBlock($options, $context, $prefix),
            'template'    => 'related-posts.tpl',
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

        // ─── Broken Links Source ────────────────────────────────────────

        $adext->register('brokenlinks', 'source', 'pubvana.blog', [
            'label'    => 'Blog Posts',
            'callable' => fn() => $app->blog()->brokenLinksItems(),
        ]);

        // ─── Navigation Linkable ────────────────────────────────────────

        $adext->register('nav.linkable', 'default', 'pubvana.blog', [
            'label'    => 'Blog Posts',
            'callable' => fn() => $app->blog()->navLinkableItems($prefix),
        ]);

        // ─── Admin CSS ──────────────────────────────────────────────────

        $adext->register('admin.css', 'default', 'pubvana.blog', [
            'url'      => '/assets/plugin/Blog/css/blog.css',
            'priority' => 20,
        ]);
    }
}
