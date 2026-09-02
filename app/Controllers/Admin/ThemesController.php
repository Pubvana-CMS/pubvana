<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use Pubvana\Services\RegionManager;
use Pubvana\Services\ThemeService;
use Pubvana\Models\Theme;
use Pubvana\Models\BlockPlacement;

/**
 * ThemesController - Admin UI for theme management.
 *
 * Handles theme listing, activation, options editing, and
 * block/region management within themes.
 *
 * Routes (registered via adext in core-admin.php):
 *   GET  /admin/themes                      - Theme listing
 *   POST /admin/themes/@id/activate         - Activate a theme
 *   GET  /admin/themes/@id/options          - Theme options form
 *   POST /admin/themes/@id/options          - Save theme options
 *   GET  /admin/themes/regions              - Region manager
 *   POST /admin/themes/regions/place        - Place a block in a region
 *   POST /admin/themes/regions/remove       - Remove a placement
 *   POST /admin/themes/regions/reorder      - Reorder placements
 *   POST /admin/themes/regions/move         - Move an orphaned placement
 *   POST /admin/themes/regions/values       - Save block values
 *
 * @package Pubvana\Controllers\Admin
 */
class ThemesController extends AdminController
{
    protected function service(): ThemeService
    {
        return $this->app->themes();
    }

    protected function regionManager(): RegionManager
    {
        return $this->app->regions();
    }

    /**
     * Theme listing — syncs filesystem, shows all themes with activate buttons.
     */
    public function index(): void
    {
        $service = $this->service();
        $service->sync();

        $themes = (new Theme($this->app->db()))->getAll();

        $theme_info = [];
        foreach ($themes as $theme) {
            $theme_info[$theme->folder] = $this->readThemeInfo($theme->folder);
        }

        $this->render('admin/themes/index', [
            'pageTitle'  => 'Themes',
            'themes'     => $themes,
            'validation' => $service->getValidationResults(),
            'theme_info' => $theme_info,
        ]);
    }

    /**
     * Activate a theme.
     */
    public function activate(string $id): void
    {
        $service = $this->service();
        $status = $service->activate((int) $id);

        $flash = match ($status) {
            'activated' => ['success', 'Theme activated.'],
            'not_found' => ['error', 'Theme not found.'],
            'disabled'  => ['error', 'Theme is disabled and cannot be activated.'],
            'invalid'   => ['error', 'Theme failed validation (PHP detected in template files).'],
            default     => ['error', 'Could not activate theme.'],
        };

        if (method_exists($this->app, 'session')) {
            $this->app->session()->flash($flash[0], $flash[1]);
        }

        $this->app->redirect('/admin/themes');
    }

    /**
     * Theme options form.
     */
    public function options(string $id): void
    {
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->redirect('/admin/themes');
            return;
        }

        $info = $this->readThemeInfo($theme->folder);
        $optionDefs = $info['provides']['options'] ?? [];

        $saved = [];
        $service = $this->service();
        foreach ($optionDefs as $key => $def) {
            if (($def['type'] ?? '') === 'group') {
                foreach (($def['fields'] ?? []) as $fKey => $fDef) {
                    $dbKey = $key . '.' . $fKey;
                    $saved[$dbKey] = $service->getThemeOption((int) $id, $dbKey, $fDef['default'] ?? '');
                }
            } else {
                $saved[$key] = $service->getThemeOption((int) $id, $key, $def['default'] ?? '');
            }
        }

        $mediaPickers = [];
        try {
            $media = $this->app->media();
            foreach ($optionDefs as $key => $def) {
                if (($def['type'] ?? '') === 'group') {
                    foreach (($def['fields'] ?? []) as $fKey => $fDef) {
                        if (($fDef['type'] ?? '') === 'media') {
                            $dbKey = $key . '.' . $fKey;
                            $mediaPickers[$dbKey] = $media->picker("options[{$key}][{$fKey}]", $saved[$dbKey] ?? '');
                        }
                    }
                } elseif (($def['type'] ?? '') === 'media') {
                    $mediaPickers[$key] = $media->picker("options[{$key}]", $saved[$key] ?? '');
                }
            }
        } catch (\Throwable) {
            $mediaPickers = [];
        }

        $this->render('admin/themes/options', [
            'pageTitle' => 'Theme Options — ' . $theme->name,
            'theme'     => $theme,
            'options'   => $optionDefs,
            'saved'     => $saved,
            'mediaPickers' => $mediaPickers,
        ]);
    }

    /**
     * Save theme options.
     */
    public function saveOptions(string $id): void
    {
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->redirect('/admin/themes');
            return;
        }

        $info = $this->readThemeInfo($theme->folder);
        $optionDefs = $info['provides']['options'] ?? [];

        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $service = $this->service();
        $posted = $post['options'] ?? [];
        foreach ($optionDefs as $key => $def) {
            if (($def['type'] ?? '') === 'group') {
                foreach (array_keys($def['fields'] ?? []) as $fKey) {
                    $dbKey = $key . '.' . $fKey;
                    $value = $posted[$key][$fKey] ?? '';
                    $service->saveThemeOption((int) $id, $dbKey, (string) $value);
                }
            } else {
                $value = $posted[$key] ?? '';
                $service->saveThemeOption((int) $id, $key, (string) $value);
            }
        }

        if (method_exists($this->app, 'session')) {
            $this->app->session()->flash('success', 'Theme options saved.');
        }

        $this->app->redirect("/admin/themes/{$id}/options");
    }

    /**
     * Region manager — show all regions, placements, available blocks, orphans.
     */
    public function regions(): void
    {
        $rm = $this->regionManager();
        $allPlacements = $rm->getAllPlacements();

        // Load saved values for each placement
        $savedValues = [];
        foreach ($allPlacements as $regionPlacements) {
            foreach ($regionPlacements as $placement) {
                $savedValues[(int) $placement->id] = $rm->getPlacementValues((int) $placement->id);
            }
        }

        $this->render('admin/themes/regions', [
            'pageTitle'   => 'Regions',
            'regions'     => $rm->getRegions(),
            'placements'  => $allPlacements,
            'blocks'      => $rm->getAvailableBlocks(),
            'orphaned'    => $rm->getOrphanedPlacements(),
            'savedValues' => $savedValues,
        ]);
    }

    /**
     * Place a block into a region.
     */
    public function placeBlock(): void
    {
        $post = $this->app->request()->data->getData();
        $regionId = $post['region_id'] ?? '';
        $blockKey = $post['block_key'] ?? '';

        if ($regionId !== '' && $blockKey !== '') {
            $this->regionManager()->savePlacement($regionId, $blockKey);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Remove a block placement.
     */
    public function removePlacement(): void
    {
        $post = $this->app->request()->data->getData();
        $id = (int) ($post['placement_id'] ?? 0);

        if ($id > 0) {
            $this->regionManager()->removePlacement($id);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Reorder placements within a region.
     */
    public function reorderPlacements(): void
    {
        $post = $this->app->request()->data->getData();
        $regionId = $post['region_id'] ?? '';
        $ids = $post['placement_ids'] ?? [];

        if ($regionId !== '' && is_array($ids)) {
            $this->regionManager()->reorderPlacements($regionId, $ids);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Move an orphaned placement to a new region.
     */
    public function movePlacement(): void
    {
        $post = $this->app->request()->data->getData();
        $id = (int) ($post['placement_id'] ?? 0);
        $newRegionId = $post['region_id'] ?? '';

        if ($id > 0 && $newRegionId !== '') {
            $this->regionManager()->movePlacement($id, $newRegionId);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Save block placement values (from modal edit form).
     */
    public function saveBlockValues(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $placementId = (int) ($post['placement_id'] ?? 0);
        if ($placementId <= 0) {
            $this->app->redirect('/admin/themes/regions');
            return;
        }

        // Look up the block's option definitions to identify textarea fields
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();
        $blockDef = $this->regionManager()->getAvailableBlocks()[$placement->block_key ?? ''] ?? [];
        $optionDefs = $blockDef['options'] ?? [];

        // Collect textarea field keys for sanitization
        $textareaKeys = [];
        foreach ($optionDefs as $fieldKey => $fieldDef) {
            if (($fieldDef['type'] ?? '') === 'textarea') {
                $textareaKeys[] = $fieldKey;
            }
        }

        $values = $post['values'] ?? [];
        $flat = [];

        foreach ($values as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $index => $row) {
                    if (is_array($row)) {
                        foreach ($row as $subKey => $subVal) {
                            $flat[$key . '.' . $index . '.' . $subKey] = (string) $subVal;
                        }
                    } else {
                        $flat[$key . '.' . $index] = (string) $row;
                    }
                }
            } else {
                $value = (string) $val;
                if (in_array($key, $textareaKeys, true)) {
                    $value = $this->purifyHtml($value);
                }
                $flat[$key] = $value;
            }
        }

        $this->regionManager()->savePlacementValues($placementId, $flat);
        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Sanitize HTML via HTMLPurifier.
     */
    private function purifyHtml(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        return (new \HTMLPurifier($config))->purify($html);
    }

    /**
     * Read pubvana.json for a theme folder.
     *
     * @return array<string, mixed>
     */
    private function readThemeInfo(string $folder): array
    {
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2);
        $path = rtrim($root, '/') . '/themes/' . $folder . '/pubvana.json';

        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        return json_decode($raw, true) ?? [];
    }
}
