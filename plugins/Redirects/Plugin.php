<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects;

use Pubvana\Plugins\Redirects\Controllers\RedirectsAdminController;
use Pubvana\Plugins\Redirects\Controllers\RedirectLinksAdminController;
use Pubvana\Plugins\Redirects\Services\RedirectsService;
use Pubvana\Plugins\Redirects\Services\RedirectLinksService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Redirects Plugin - Manages URL redirects and tracks incoming 404s.
 *
 * Registers the redirect and redirect-link services, the admin
 * screens under Tools, dashboard contributions, and runtime request
 * interception:
 *
 *   - before start    - issue a matching 301/302 before normal routing
 *   - before notFound - log the incoming request as a redirect link
 *   - before halt     - log an internally generated 404 the same way
 *
 * @package Pubvana\Plugins\Redirects
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $app->map('redirects', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new RedirectsService($app->db(), $app, $config);
            }
            return $instance;
        });

        $app->map('redirectLinks', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new RedirectLinksService($app->db(), $app, $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/redirects',                    [RedirectsAdminController::class, 'index'],    [$authMiddleware]],
            ['GET',  '/redirects/create',             [RedirectsAdminController::class, 'create'],   [$authMiddleware]],
            ['POST', '/redirects/store',              [RedirectsAdminController::class, 'store'],    [$authMiddleware]],
            ['GET',  '/redirects/@id/edit',           [RedirectsAdminController::class, 'edit'],     [$authMiddleware]],
            ['POST', '/redirects/@id/update',         [RedirectsAdminController::class, 'update'],   [$authMiddleware]],
            ['POST', '/redirects/@id/delete',         [RedirectsAdminController::class, 'delete'],   [$authMiddleware]],
            ['GET',  '/404-manager',                  [RedirectLinksAdminController::class, 'index'],    [$authMiddleware]],
            ['POST', '/404-manager/@id/ignore',       [RedirectLinksAdminController::class, 'ignore'],   [$authMiddleware]],
            ['POST', '/404-manager/@id/unignore',     [RedirectLinksAdminController::class, 'unignore'], [$authMiddleware]],
            ['POST', '/404-manager/@id/delete',       [RedirectLinksAdminController::class, 'delete'],   [$authMiddleware]],
        ], 'pubvana.redirects');

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.redirects', [
            'label'    => 'Redirects',
            'priority' => 35,
            'callable' => function (array $context) use ($app): array {
                $active404s = $app->redirectLinks()->count('active');
                $enabledRedirects = $app->redirects()->countEnabled();

                return [
                    [
                        'id'          => 'active-404s',
                        'label'       => 'Active 404s',
                        'value'       => $active404s,
                        'icon'        => 'ti-link-off',
                        'tone'        => $active404s > 0 ? 'danger' : 'success',
                        'group'       => 'tools',
                        'href'        => '/404-manager',
                        'description' => $active404s > 0
                            ? 'Unresolved redirect links needing attention.'
                            : 'No unresolved redirect links right now.',
                    ],
                    [
                        'id'          => 'enabled-redirects',
                        'label'       => 'Enabled Redirects',
                        'value'       => $enabledRedirects,
                        'icon'        => 'ti-route-2',
                        'tone'        => 'info',
                        'group'       => 'tools',
                        'href'        => '/redirects',
                        'description' => 'Redirect rules currently active.',
                    ],
                ];
            },
        ]);

        $adext->register('admin.dashboard', 'sections', 'pubvana.redirects', [
            'label'    => 'Redirects',
            'priority' => 15,
            'callable' => function (array $context) use ($app): array {
                $items = [];
                foreach ($app->redirectLinks()->recent('active', 5) as $entry) {
                    $items[] = [
                        'label'    => $entry->source_path,
                        'meta'     => ((int) $entry->hit_count) . ' hits · Last seen ' . date('M j, Y g:ia', strtotime((string) $entry->last_seen_at)),
                        'href'     => '/404-manager',
                        'emphasis' => 'danger',
                    ];
                }

                return [[
                    'id'          => 'recent-redirect-links',
                    'title'       => 'Recent Redirect Links',
                    'type'        => 'list',
                    'icon'        => 'ti-unlink',
                    'tone'        => 'danger',
                    'group'       => 'tools',
                    'href'        => '/404-manager',
                    'empty_state' => 'No redirect links have been recorded.',
                    'items'       => $items,
                ]];
            },
        ]);

        // ─── Request Interception ───────────────────────────────────────

        $app->before('start', function () use ($app) {
            $app->redirects()->handleCurrentRequest();
        });

        $app->before('notFound', function () use ($app) {
            $app->redirectLinks()->logCurrentRequest();
        });

        $app->before('halt', function (array &$params) use ($app) {
            $statusCode = (int) ($params[0] ?? 200);
            if ($statusCode === 404) {
                $app->redirectLinks()->logCurrentRequest();
            }
        });
    }
}
