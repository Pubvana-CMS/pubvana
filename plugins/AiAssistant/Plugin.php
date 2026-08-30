<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant;

use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use Pubvana\Plugins\AiAssistant\Controllers\AiAdminController;
use Pubvana\Plugins\AiAssistant\Controllers\AiApiController;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\AiAssistant\Services\MarkdownService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * AI Assistant Plugin - API-key ingestion endpoints and per-key grants.
 *
 * Registers the `ai` and `aiMarkdown` services, the admin screens under
 * Tools > AI Assistant, and the sessionless `/ai/*` REST API. Every
 * public endpoint authenticates with a per-request bearer API key;
 * grants are deny-all until an admin explicitly grants a permission to
 * a key. The core CSRF middleware skips `/ai/*` (see
 * app/config/services.php) because these endpoints cannot present a
 * session token.
 *
 * @package Pubvana\Plugins\AiAssistant
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $app->map('ai', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new AiService($app->db(), $app, $config);
            }
            return $instance;
        });

        $app->map('aiMarkdown', function () use ($config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new MarkdownService($config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $prefix = $app->pluginLoader()->routePrefix('pubvana/ai');

        // Admin screens require the seeded ai.manage permission.
        $manageMiddleware = new PermissionMiddleware($app, 'ai.manage');

        // ─── Admin Routes (adext prepends /admin) ──────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/ai/manage',                    [AiAdminController::class, 'manage'],       [$manageMiddleware]],
            ['POST', '/ai/manage/keys',               [AiAdminController::class, 'createKey'],    [$manageMiddleware]],
            ['POST', '/ai/manage/keys/@id/grants',    [AiAdminController::class, 'updateGrants'], [$manageMiddleware]],
            ['POST', '/ai/manage/keys/@id/toggle',    [AiAdminController::class, 'toggleKey'],    [$manageMiddleware]],
            ['POST', '/ai/manage/keys/@id/delete',    [AiAdminController::class, 'deleteKey'],    [$manageMiddleware]],
            ['POST', '/ai/manage/author',             [AiAdminController::class, 'saveAuthor'],   [$manageMiddleware]],
            ['GET',  '/ai/help',                      [AiAdminController::class, 'help'],         [$manageMiddleware]],
        ], 'pubvana.ai');

        // ─── Public REST API (sessionless, bearer-key auth) ─────────────
        // Static taxonomy routes (/posts/tags, /posts/categories) MUST be
        // registered before the parameterized /posts/@slug route.

        $adext->addRoutes('public', [
            ['GET',  $prefix . '/help',                        [AiApiController::class, 'help']],
            ['GET',  $prefix . '/help/@permission',             [AiApiController::class, 'helpPermission']],
            ['GET',  $prefix . '/posts',                        [AiApiController::class, 'posts']],
            ['GET',  $prefix . '/posts/tags',                   [AiApiController::class, 'tags']],
            ['GET',  $prefix . '/posts/categories',             [AiApiController::class, 'categories']],
            ['GET',  $prefix . '/posts/@slug',                  [AiApiController::class, 'post']],
            ['POST', $prefix . '/posts',                        [AiApiController::class, 'createPost']],
            ['POST', $prefix . '/posts/@id/update',             [AiApiController::class, 'updatePost']],
            ['POST', $prefix . '/posts/@id/delete',             [AiApiController::class, 'deletePost']],
            ['GET',  $prefix . '/pages',                        [AiApiController::class, 'pages']],
            ['GET',  $prefix . '/pages/@slug',                  [AiApiController::class, 'page']],
            ['POST', $prefix . '/pages',                        [AiApiController::class, 'createPage']],
            ['POST', $prefix . '/pages/@id/update',             [AiApiController::class, 'updatePage']],
            ['POST', $prefix . '/pages/@id/delete',             [AiApiController::class, 'deletePage']],
            ['GET',  $prefix . '/comments',                     [AiApiController::class, 'comments']],
            ['POST', $prefix . '/comments/@id/approve',         [AiApiController::class, 'approveComment']],
            ['POST', $prefix . '/comments/@id/reject',          [AiApiController::class, 'rejectComment']],
            ['POST', $prefix . '/comments/@id/delete',          [AiApiController::class, 'deleteComment']],
            ['GET',  $prefix . '/redirects',                    [AiApiController::class, 'redirects']],
            ['POST', $prefix . '/redirects',                    [AiApiController::class, 'createRedirect']],
            ['POST', $prefix . '/redirects/@id/update',         [AiApiController::class, 'updateRedirect']],
            ['POST', $prefix . '/redirects/@id/delete',         [AiApiController::class, 'deleteRedirect']],
            ['GET',  $prefix . '/navigation',                   [AiApiController::class, 'navigation']],
            ['POST', $prefix . '/navigation',                   [AiApiController::class, 'createNavigation']],
            ['POST', $prefix . '/navigation/@id/update',        [AiApiController::class, 'updateNavigation']],
            ['POST', $prefix . '/navigation/@id/delete',        [AiApiController::class, 'deleteNavigation']],
            ['GET',  $prefix . '/broken-links',                 [AiApiController::class, 'brokenLinks']],
            ['GET',  $prefix . '/analytics',                    [AiApiController::class, 'analytics']],
        ], 'pubvana.ai');
    }
}