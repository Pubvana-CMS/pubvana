<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * AnalyticsAdminController - Site traffic reports.
 *
 * @package Pubvana\Plugins\Analytics\Controllers
 */
class AnalyticsAdminController extends AdminController
{
    /**
     * Render the analytics report.
     */
    public function index(): void
    {
        $range = $this->validRange($this->app->request()->query->range ?? null);

        $this->render('pubvana/analytics/admin/index', [
            'pageTitle' => 'Analytics',
            'range'     => $range,
            'report'    => $this->app->analytics()->dashboard($range),
            'adminBase' => $this->adminBase(),
        ]);
    }

    /**
     * JSON payload for the AJAX range refresh.
     */
    public function data(): void
    {
        $range = $this->validRange($this->app->request()->query->range ?? null);
        $this->app->json($this->app->analytics()->dashboard($range));
    }

    /**
     * Full list of content paths for the selected range.
     */
    public function contentList(): void
    {
        $this->renderList('content');
    }

    /**
     * Full list of referrer domains for the selected range.
     */
    public function referrerList(): void
    {
        $this->renderList('referrers');
    }

    /**
     * Render one paginated list page.
     */
    private function renderList(string $kind): void
    {
        $range = $this->validRange($this->app->request()->query->range ?? null);
        $page = max(1, (int) ($this->app->request()->query->page ?? 1));
        $perPage = 100;

        $analytics = $this->app->analytics();
        $data = $kind === 'content'
            ? $analytics->contentPage($range, $page, $perPage)
            : $analytics->referrersPage($range, $page, $perPage);

        $base = $this->adminBase() . ($kind === 'content' ? '/content' : '/referrers');

        $this->render('pubvana/analytics/admin/list', [
            'pageTitle'  => $kind === 'content' ? 'All Content' : 'All Referrers',
            'kind'       => $kind,
            'range'      => $range,
            'rows'       => $data['rows'],
            'page'       => $page,
            'perPage'    => $perPage,
            'adminBase'  => $this->adminBase(),
            'pagination' => $this->app->pagination()->build(
                $page,
                $data['total'],
                $perPage,
                static fn (int $number): string => $base . '?' . http_build_query([
                    'range' => $range,
                    'page'  => $number,
                ])
            ),
        ]);
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/analytics'), '/');
    }

    private function validRange(mixed $raw): string
    {
        return $this->app->analytics()->normalizeRange((string) ($raw ?? '30'));
    }
}