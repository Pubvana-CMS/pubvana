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
    /**
     * Upper bound on the published items the dashboard coverage scan reads.
     *
     * The dashboard is a summary, so it samples the first N items per type
     * rather than loading an entire large site on every admin visit.
     */
    protected const DASHBOARD_SCAN_LIMIT = 5000;

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

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/seo',         [SeoAdminController::class, 'settings'],     []],
            ['POST', '/seo',         [SeoAdminController::class, 'saveSettings'], []],
            ['POST', '/seo/meta',    [SeoAdminController::class, 'saveMeta'],     []],
            ['POST', '/seo/analyze', [SeoAdminController::class, 'analyze'],      []],
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

                // Configured site origin; the Host header is never trusted (AUDIT M4).
                $siteUrl = $app->url()->siteOrigin();

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
     *
     * @param Engine<object> $app
     * @return array<int, array<string, mixed>>
     */
    protected function getDashboardCards(Engine $app): array
    {
        $totalContent = 0;
        $withMeta = 0;

        // Count only published items that carry a meta title, so drafts and
        // orphaned rows cannot push coverage past 100%.
        if ($app->pluginLoader()->isEnabled('pubvana/pages')) {
            $pages = $app->pages()->listPublished(self::DASHBOARD_SCAN_LIMIT);
            $titled = $this->metaTitleIds($app, 'page');
            $totalContent += count($pages);

            foreach ($pages as $page) {
                if (isset($titled[(int) $page->id])) {
                    $withMeta++;
                }
            }
        }

        if ($app->pluginLoader()->isEnabled('pubvana/blog')) {
            $result = $app->blog()->listPosts(1, self::DASHBOARD_SCAN_LIMIT, 'published');
            $titled = $this->metaTitleIds($app, 'post');
            $items = $result['items'];
            $totalContent += count($items);

            foreach ($items as $post) {
                if (isset($titled[(int) $post->id])) {
                    $withMeta++;
                }
            }
        }

        $missingMeta = max(0, $totalContent - $withMeta);
        $avgScore = (new SeoMeta($app->db()))->averageScore();

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

    /**
     * Ids of content in a type whose seo_meta row has a non-empty title.
     *
     * One query per type, so the dashboard does not run a lookup per item.
     *
     * @param Engine<object> $app
     * @return array<int, true>
     */
    private function metaTitleIds(Engine $app, string $contentType): array
    {
        $model = new SeoMeta($app->db());
        $ids = [];

        foreach ($model->findByContentType($contentType) as $meta) {
            if (trim((string) ($meta->meta_title ?? '')) !== '') {
                $ids[(int) $meta->content_id] = true;
            }
        }

        return $ids;
    }
}
