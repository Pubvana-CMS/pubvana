<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages;

use Pubvana\Plugins\Pages\Controllers\PagesAdminController;
use Pubvana\Plugins\Pages\Controllers\PagesPublicController;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Pages Plugin - Registers routes and services for the pages module.
 *
 * Called by PluginLoader after pubvana.json hooks are loaded.
 * Registers admin routes via adext. Public routes use {prefix}/@slug
 * (prefix from routePrepend) to avoid catching unrelated URLs.
 *
 * @package Pubvana\Plugins\Pages
 */
class Plugin implements PluginInterface
{
    /**
     * Register the plugin's routes with adext.
     *
     * @param Engine<object> $app       Flight application
     * @param Router $router    Flight router (unused, routes go through adext)
     * @param array<string, mixed>  $config    Plugin configuration
     * @return void
     */
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/pages');
        $config['route_prefix'] = $prefix;

        $app->map('pages', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new Services\PagesService($app->db(), $config);
            }
            return $instance;
        });

        $adext = $app->adext();

        // Admin routes
        $adext->addRoutes('admin', [
            ['GET',    $prefix,                      [PagesAdminController::class, 'index'],    []],
            ['GET',    $prefix . '/create',          [PagesAdminController::class, 'create'],   []],
            ['POST',   $prefix . '/store',           [PagesAdminController::class, 'store'],    []],
            ['GET',    $prefix . '/@id/edit',        [PagesAdminController::class, 'edit'],     []],
            ['POST',   $prefix . '/@id/update',      [PagesAdminController::class, 'update'],   []],
            ['POST',   $prefix . '/@id/delete',      [PagesAdminController::class, 'delete'],   []],
            ['GET',    $prefix . '/@id/revisions',   [PagesAdminController::class, 'revisions'],[]],
            ['POST',   $prefix . '/@id/restore/@revisionId', [PagesAdminController::class, 'restore'], []],
        ], 'pubvana.pages');

        // Public routes
        $adext->addRoutes('public', [
            ['GET',    $prefix,             [PagesPublicController::class, 'index']],
            ['GET',    $prefix . '/@slug',  [PagesPublicController::class, 'view']],
        ], 'pubvana.pages');

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.pages', [
            'label'    => 'Pages',
            'priority' => 30,
            'callable' => fn(array $context) => $app->pages()->dashboardCards(),
        ]);

        $adext->register('admin.dashboard', 'sections', 'pubvana.pages', [
            'label'    => 'Pages',
            'priority' => 40,
            'callable' => fn(array $context) => $app->pages()->dashboardSections(),
        ]);

        // Quick Add linkable items for navigation manager
        $adext->register('nav.linkable', 'default', 'pubvana.pages', [
            'label'    => 'Pages',
            'callable' => fn() => $app->pages()->navLinkableItems(),
        ]);

        // Broken Links Source
        $adext->register('brokenlinks', 'source', 'pubvana.pages', [
            'label'    => 'Pages',
            'callable' => fn() => $app->pages()->brokenLinksItems(),
        ]);

        // Search source — pages are searchable content
        $adext->register('search', 'provider', 'pubvana.pages', [
            'label'        => 'Pages',
            'content_type' => 'Page',
            'callable'     => fn(string $term) => $app->pages()->searchProvider($term),
        ]);

        // Comments host — pages are commentable content (no per-page toggle)
        $adext->register('comments.host', 'content', 'pubvana.pages', [
            'label'    => 'Pages',
            'callable' => fn() => $app->pages()->commentHostItems(),
        ]);

        // ─── Homepage ───────────────────────────────────────────────────

        // Pages offers itself as a front page candidate. The token follows the
        // plugin's routePrepend ('page'). The page picker is declared here as
        // a field this provider owns, so core carries no Pages setting:
        // SettingsController renders it under the Homepage select for us, and
        // its options load only when the admin form renders or saves.
        //
        // Declining (false) hands "/" back to the next provider, which is what
        // happens when no page is chosen or the chosen page is unpublished.
        $adext->register('homepage', 'provider', 'pubvana.pages', [
            'label'    => 'Static Page',
            'token'    => trim($prefix, '/'),
            'priority' => 30,
            'callable' => function () use ($app): bool {
                $pageId = (int) $app->settings()->get('CMS.homepagePageId', 0);
                if ($pageId <= 0) {
                    return false;
                }

                $page = $app->pages()->findPage($pageId);
                if ($page === null || $page->status !== 'published') {
                    error_log("Pages: homepage page {$pageId} is missing or unpublished - declining '/'");
                    return false;
                }

                (new PagesPublicController($app))->view((string) $page->slug, true);

                return true;
            },
            'fields'   => [
                [
                    'key'              => 'CMS.homepagePageId',
                    'label'            => 'Homepage Page',
                    'type'             => 'select',
                    'options'          => [],
                    'default'          => null,
                    'description'      => 'Which published page to show when Homepage is set to Static Page.',
                    'options_callable' => fn() => $app->pages()->publishedOptions(),
                ],
            ],
        ]);
    }
}
