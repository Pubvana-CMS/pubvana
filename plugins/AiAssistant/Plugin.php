<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant;

use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use Pubvana\Plugins\AiAssistant\Controllers\AiAdminController;
use Pubvana\Plugins\AiAssistant\Controllers\AiAnalyticsApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiBrokenLinksApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiCommentsApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiFactCheckAdminController;
use Pubvana\Plugins\AiAssistant\Controllers\AiFactCheckApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiNavigationApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiPagesApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiPostsApiController;
use Pubvana\Plugins\AiAssistant\Controllers\AiRedirectsApiController;
use Pubvana\Plugins\AiAssistant\Services\AiService;
use Pubvana\Plugins\AiAssistant\Services\FactCheckService;
use Pubvana\Plugins\AiAssistant\Services\MarkdownService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * AI Assistant Plugin - API-key ingestion endpoints and per-key grants.
 *
 * Registers the `ai`, `aiFactCheck`, and `aiMarkdown` services, the
 * admin screens under Tools > AI Assistant, and the sessionless `/api/ai/*`
 * REST API. Every public endpoint authenticates with a per-request
 * bearer API key; grants are deny-all until an admin explicitly grants a
 * permission to a key. The plugin registers its `/api/ai/*` prefix under the
 * csrf.exempt extension type so the core CSRF middleware skips these
 * sessionless endpoints (they cannot present a session token).
 *
 * Fact Checking is the one site-level feature: instead of per-key
 * grants, its endpoints open when the admin accepts the prompt's terms
 * and flips the toggle, and stay shut otherwise.
 *
 * @package Pubvana\Plugins\AiAssistant
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/ai');
        $config['route_prefix'] = $prefix;
        $apiPrefix = $app->pluginLoader()->apiPrefix('pubvana/ai');

        $app->map('ai', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new AiService($app->db(), $app, $config);
            }
            return $instance;
        });

        $app->map('aiFactCheck', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new FactCheckService($app->db(), $app, $config);
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

        // Exempt the sessionless /api/ai/* API from CSRF validation.
        $adext->register('csrf.exempt', 'default', 'pubvana.ai', [
            'prefix' => $apiPrefix . '/',
            'label'  => 'AI Assistant API',
        ]);

        // Admin screens require the seeded ai.manage permission.
        $manageMiddleware = new PermissionMiddleware($app, 'ai.manage');

        // ─── Admin Routes (adext prepends /admin) ──────────────────────

        $adext->addRoutes('admin', [
            ['GET',  $prefix . '/manage',                    [AiAdminController::class, 'manage'],       [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys',               [AiAdminController::class, 'createKey'],    [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys/@id/grants',    [AiAdminController::class, 'updateGrants'], [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys/@id/toggle',    [AiAdminController::class, 'toggleKey'],    [$manageMiddleware]],
            ['POST', $prefix . '/manage/keys/@id/delete',    [AiAdminController::class, 'deleteKey'],    [$manageMiddleware]],
            ['POST', $prefix . '/manage/author',             [AiAdminController::class, 'saveAuthor'],   [$manageMiddleware]],
            ['GET',  $prefix . '/help',                      [AiAdminController::class, 'help'],         [$manageMiddleware]],
            ['GET',  $prefix . '/fact-checks',               [AiFactCheckAdminController::class, 'index'],       [$manageMiddleware]],
            ['GET',  $prefix . '/fact-checks/@id',           [AiFactCheckAdminController::class, 'show'],        [$manageMiddleware]],
            ['POST', $prefix . '/fact-checks/terms',         [AiFactCheckAdminController::class, 'acceptTerms'], [$manageMiddleware]],
            ['POST', $prefix . '/fact-checks/toggle',        [AiFactCheckAdminController::class, 'toggle'],      [$manageMiddleware]],
            ['POST', $prefix . '/fact-checks/@id/delete',    [AiFactCheckAdminController::class, 'delete'],      [$manageMiddleware]],
        ], 'pubvana.ai');

        // ─── Public REST API (sessionless, bearer-key auth) ─────────────
        // Static taxonomy routes (/posts/tags, /posts/categories) MUST be
        // registered before the parameterized /posts/@slug route.

        $adext->addRoutes('public', [
            ['GET',  $apiPrefix . '/help',                        [AiApiController::class, 'help']],
            ['GET',  $apiPrefix . '/help/@permission',             [AiApiController::class, 'helpPermission']],
            ['GET',  $apiPrefix . '/posts',                        [AiPostsApiController::class, 'posts']],
            ['GET',  $apiPrefix . '/posts/tags',                   [AiPostsApiController::class, 'tags']],
            ['GET',  $apiPrefix . '/posts/categories',             [AiPostsApiController::class, 'categories']],
            ['GET',  $apiPrefix . '/posts/@slug',                  [AiPostsApiController::class, 'post']],
            ['POST', $apiPrefix . '/posts',                        [AiPostsApiController::class, 'createPost']],
            ['POST', $apiPrefix . '/posts/@id/update',             [AiPostsApiController::class, 'updatePost']],
            ['POST', $apiPrefix . '/posts/@id/delete',             [AiPostsApiController::class, 'deletePost']],
            ['GET',  $apiPrefix . '/pages',                        [AiPagesApiController::class, 'pages']],
            ['GET',  $apiPrefix . '/pages/@slug',                  [AiPagesApiController::class, 'page']],
            ['POST', $apiPrefix . '/pages',                        [AiPagesApiController::class, 'createPage']],
            ['POST', $apiPrefix . '/pages/@id/update',             [AiPagesApiController::class, 'updatePage']],
            ['POST', $apiPrefix . '/pages/@id/delete',             [AiPagesApiController::class, 'deletePage']],
            ['GET',  $apiPrefix . '/comments',                     [AiCommentsApiController::class, 'comments']],
            ['POST', $apiPrefix . '/comments/@id/approve',         [AiCommentsApiController::class, 'approveComment']],
            ['POST', $apiPrefix . '/comments/@id/reject',          [AiCommentsApiController::class, 'rejectComment']],
            ['POST', $apiPrefix . '/comments/@id/delete',          [AiCommentsApiController::class, 'deleteComment']],
            ['GET',  $apiPrefix . '/redirects',                    [AiRedirectsApiController::class, 'redirects']],
            ['POST', $apiPrefix . '/redirects',                    [AiRedirectsApiController::class, 'createRedirect']],
            ['POST', $apiPrefix . '/redirects/@id/update',         [AiRedirectsApiController::class, 'updateRedirect']],
            ['POST', $apiPrefix . '/redirects/@id/delete',         [AiRedirectsApiController::class, 'deleteRedirect']],
            ['GET',  $apiPrefix . '/navigation',                   [AiNavigationApiController::class, 'navigation']],
            ['POST', $apiPrefix . '/navigation',                   [AiNavigationApiController::class, 'createNavigation']],
            ['POST', $apiPrefix . '/navigation/@id/update',        [AiNavigationApiController::class, 'updateNavigation']],
            ['POST', $apiPrefix . '/navigation/@id/delete',        [AiNavigationApiController::class, 'deleteNavigation']],
            ['GET',  $apiPrefix . '/fact-check/prompt',            [AiFactCheckApiController::class, 'factCheckPrompt']],
            ['GET',  $apiPrefix . '/fact-checks',                  [AiFactCheckApiController::class, 'factChecks']],
            ['GET',  $apiPrefix . '/fact-checks/@id',              [AiFactCheckApiController::class, 'factCheck']],
            ['POST', $apiPrefix . '/posts/@id/fact-check',         [AiFactCheckApiController::class, 'submitPostFactCheck']],
            ['POST', $apiPrefix . '/pages/@id/fact-check',         [AiFactCheckApiController::class, 'submitPageFactCheck']],
            ['GET',  $apiPrefix . '/broken-links',                 [AiBrokenLinksApiController::class, 'brokenLinks']],
            ['POST', $apiPrefix . '/broken-links/scan',            [AiBrokenLinksApiController::class, 'scanBrokenLinks']],
            ['POST', $apiPrefix . '/broken-links/@id/recheck',     [AiBrokenLinksApiController::class, 'recheckBrokenLink']],
            ['POST', $apiPrefix . '/broken-links/@id/dismiss',     [AiBrokenLinksApiController::class, 'dismissBrokenLink']],
            ['GET',  $apiPrefix . '/analytics',                    [AiAnalyticsApiController::class, 'analytics']],
        ], 'pubvana.ai');

        // ─── Content Edit Panel (read-only, in Blog/Pages editors) ─────

        $adext->register('content.edit.panel', 'default', 'pubvana.ai.factcheck', [
            'label'    => 'Fact Check',
            'priority' => 60,
            'callable' => function (array $context) use ($app, $prefix): string {
                $contentType = ($context['content_type'] ?? '') === 'page' ? 'page' : 'post';
                $contentId = (int) ($context['content_id'] ?? 0);

                return $app->view()->fetch('pubvana/ai/admin/fact-check-panel', [
                    'panel'       => $app->aiFactCheck()->panelData($contentType, $contentId),
                    'content_id'  => $contentId,
                    'adminBase'   => '/admin' . rtrim($prefix, '/'),
                ]);
            },
        ]);

        // ─── Public Block: Fact Check Summary ──────────────────────────

        $adext->register('block', 'available', 'pubvana.ai.fact-check-summary', [
            'label'       => 'Fact Check Summary',
            'description' => 'Shows the fact-check findings and verdict for the post or page being viewed. Renders nothing where no report exists.',
            'provider'    => fn (array $options) => $app->aiFactCheck()->blockData($options),
            'template'    => 'fact-check-summary.tpl',
            'priority'    => 60,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Fact Check'],
            ],
        ]);
    }
}