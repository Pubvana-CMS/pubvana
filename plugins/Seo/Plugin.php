<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo;

use Pubvana\Plugins\Seo\Controllers\SeoAdminController;
use Pubvana\Plugins\Seo\Controllers\SeoPublicController;
use Pubvana\Plugins\Seo\Models\SeoMeta;
use Pubvana\Plugins\Seo\Services\ContentAnalysisService;
use Pubvana\Plugins\Seo\Services\LlmsTxtService;
use Pubvana\Plugins\Seo\Services\RobotsTxtService;
use Pubvana\Plugins\Seo\Services\SchemaService;
use Pubvana\Plugins\Seo\Services\SeoService;
use Pubvana\Plugins\Seo\Services\SitemapService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * SEO Plugin - Meta tags, structured data, sitemaps, Open Graph, AI/GEO.
 *
 * @package Pubvana\Plugins\Seo
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $app->map('seo', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new SeoService($app->db(), $app);
            }
            return $instance;
        });

        $app->map('seoSchema', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new SchemaService($app);
            }
            return $instance;
        });

        $app->map('seoSitemap', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new SitemapService($app->db(), $app);
            }
            return $instance;
        });

        $app->map('seoRobots', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new RobotsTxtService($app);
            }
            return $instance;
        });

        $app->map('seoLlmsTxt', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new LlmsTxtService($app->db(), $app);
            }
            return $instance;
        });

        $app->map('seoAnalysis', function () {
            static $instance = null;
            if ($instance === null) {
                $instance = new ContentAnalysisService();
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/seo',         [SeoAdminController::class, 'settings'],     [$authMiddleware]],
            ['POST', '/seo',         [SeoAdminController::class, 'saveSettings'], [$authMiddleware]],
            ['POST', '/seo/meta',    [SeoAdminController::class, 'saveMeta'],     [$authMiddleware]],
            ['GET',  '/seo/analyze', [SeoAdminController::class, 'analyze'],      [$authMiddleware]],
        ], 'pubvana.seo');

        // ─── Public Routes (root-level, no prefix) ──────────────────────

        $adext->addRoutes('public', [
            ['GET', '/sitemap.xml', [SeoPublicController::class, 'sitemap']],
            ['GET', '/robots.txt',  [SeoPublicController::class, 'robotsTxt']],
            ['GET', '/llms.txt',    [SeoPublicController::class, 'llmsTxt']],
        ], 'pubvana.seo');

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.seo', [
            'label'    => 'SEO',
            'priority' => 40,
            'callable' => function (array $context) use ($app): array {
                return $this->getDashboardCards($app);
            },
        ]);

        // ─── Content Edit Panel ─────────────────────────────────────────

        $adext->register('content.edit.panel', 'default', 'pubvana.seo', [
            'label'    => 'SEO',
            'priority' => 50,
            'callable' => function (array $context) use ($app): string {
                $contentType = $context['content_type'] ?? '';
                $contentId = (int) ($context['content_id'] ?? 0);

                if ($contentId <= 0) {
                    return $app->view()->fetch('pubvana/seo/admin/create-notice');
                }

                $settings = $app->settings();
                $siteUrl = $settings->get('CMS.siteUrl');
                if (empty($siteUrl)) {
                    $request = $app->request();
                    $scheme = $request->secure ? 'https' : 'http';
                    $host = $request->host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    $siteUrl = $scheme . '://' . $host;
                }
                $siteUrl = rtrim($siteUrl, '/');

                $routePrefix = $contentType === 'post'
                    ? $app->pluginLoader()->routePrefix('pubvana/blog')
                    : ($contentType === 'page' ? '/page' : '');

                $meta = $app->seo()->getMeta($contentType, $contentId);
                $ogImage = $meta ? ($meta->og_image ?? '') : '';

                return $app->view()->fetch('pubvana/seo/admin/content-panel', [
                    'content_type'     => $contentType,
                    'content_id'       => $contentId,
                    'content_url_base' => $siteUrl . $routePrefix,
                    'seo_meta'         => $meta,
                    'ogImagePicker'    => $app->media()->picker('seo[og_image]', $ogImage),
                ]);
            },
        ]);
    }

    /**
     * Dashboard cards showing SEO overview stats.
     */
    protected function getDashboardCards(Engine $app): array
    {
        $seoModel = new SeoMeta($app->db());

        $totalPages = count($app->pages()->listPublished());
        $totalPosts = (int) ($app->blog()->listPosts(1, 1, 'published')['total'] ?? 0);
        $totalContent = $totalPages + $totalPosts;

        $withMeta = $seoModel->countWithMetaTitle();
        $missingMeta = max(0, $totalContent - $withMeta);

        $avgScore = $seoModel->averageScore();

        return [
            [
                'id'          => 'seo-coverage',
                'label'       => 'SEO Coverage',
                'value'       => $totalContent > 0 ? round(($withMeta / $totalContent) * 100) . '%' : '0%',
                'icon'        => 'ti-seo',
                'tone'        => $missingMeta === 0 ? 'success' : ($missingMeta <= 5 ? 'warning' : 'danger'),
                'group'       => 'tools',
                'href'        => '/seo',
                'description' => $missingMeta > 0
                    ? $missingMeta . ' published item(s) missing SEO data.'
                    : 'All published content has SEO metadata.',
            ],
            [
                'id'          => 'seo-avg-score',
                'label'       => 'Avg SEO Score',
                'value'       => $avgScore . '/100',
                'icon'        => 'ti-chart-bar',
                'tone'        => $avgScore >= 70 ? 'success' : ($avgScore >= 40 ? 'warning' : 'danger'),
                'group'       => 'tools',
                'href'        => '/seo',
                'description' => 'Average content optimization score.',
            ],
        ];
    }
}
