<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use Pubvana\Plugins\SiteHealth\Services\CheckResult;

class HealthAdminController extends AdminController
{
    public function index(): void
    {
        $data = $this->app->health()->runAll();
        $grouped = $this->app->health()->groupByCategory($data['results']);

        $this->render('pubvana/sitehealth/admin/index', [
            'pageTitle'  => 'Site Health',
            'results'    => $data['results'],
            'grouped'    => $grouped,
            'summary'    => $data['summary'],
            'cachedAt'   => $data['cached_at'],
            'adminBase'  => $this->adminBase(),
            'categories' => $this->categories($grouped),
        ]);
    }

    /**
     * Category label/icon map, extended with any category a check reported
     * that is not in the fixed four. A contributed check with a new category
     * still renders instead of counting in the summary while staying
     * invisible on the page.
     *
     * @param array<string, array<int, array<string, mixed>>> $grouped
     * @return array<string, array{label: string, icon: string}>
     */
    private function categories(array $grouped): array
    {
        $categories = [
            CheckResult::CAT_ENVIRONMENT   => ['label' => 'Environment', 'icon' => 'ti-server'],
            CheckResult::CAT_SECURITY      => ['label' => 'Security', 'icon' => 'ti-shield-lock'],
            CheckResult::CAT_CONFIGURATION => ['label' => 'Configuration', 'icon' => 'ti-settings'],
            CheckResult::CAT_PLUGINS       => ['label' => 'Plugins', 'icon' => 'ti-puzzle'],
        ];

        foreach (array_keys($grouped) as $category) {
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'label' => ucfirst(str_replace(['-', '_'], ' ', $category)),
                    'icon'  => 'ti-list',
                ];
            }
        }

        return $categories;
    }

    public function rerun(): void
    {
        $this->app->health()->clearCache();
        $this->app->health()->runAll(true);

        $this->app->session()->flash('success', 'Site health checks re-run.');
        $this->app->redirect($this->adminBase());
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/sitehealth'), '/');
    }
}
