<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use Pubvana\Models\TrustCache;
use Pubvana\Services\HtmlPurifierFactory;
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
     * Theme listing, syncs filesystem, shows all themes with activate buttons.
     *
     * Trust statuses come from the shared trust cache; any theme with no
     * cache row is live-checked first (one batch) so fresh installs show real
     * answers. A trust-layer failure degrades to 'no data' badges.
     */
    public function index(): void
    {
        $service = $this->service();
        $service->sync();

        $themes = (new Theme($this->app->db()))->getAll();

        $trust = [];
        $maliciousActive = [];
        try {
            $this->app->trustClient()->ensureCacheForAll();
            $statuses = $this->app->trustClient()->statusesForAll();
            foreach ($themes as $theme) {
                $folder = (string) $theme->folder;
                $trust[$folder] = ['status' => 'none', 'warning' => null];

                $item = $this->app->trustClient()->themeItem($folder);
                if ($item !== null) {
                    $cached = $statuses[TrustCache::compositeKey(
                        $item['type'],
                        $item['slug'],
                        $item['version'],
                        $item['author']
                    )] ?? null;
                    if ($cached !== null) {
                        $trust[$folder] = ['status' => $cached['status'], 'warning' => $cached['warning']];
                    }
                }

                if (!empty($theme->is_active) && $trust[$folder]['status'] === TrustCache::STATUS_MALICIOUS) {
                    $maliciousActive[] = [
                        'name'    => (string) $theme->name,
                        'warning' => $trust[$folder]['warning'],
                    ];
                }
            }
        } catch (\Throwable) {
            foreach ($themes as $theme) {
                $trust[(string) $theme->folder] = ['status' => 'none', 'warning' => null];
            }
        }

        $theme_info = [];
        foreach ($themes as $theme) {
            $theme_info[$theme->folder] = $this->readThemeInfo($theme->folder);
        }

        $this->render('admin/themes/index', [
            'pageTitle'       => 'Themes',
            'themes'          => $themes,
            'validation'      => $service->getValidationResults(),
            'theme_info'      => $theme_info,
            'trust'           => $trust,
            'maliciousActive' => $maliciousActive,
        ]);
    }

    /**
     * Activate a theme.
     *
     * The trust gate runs before activation: a malicious theme is refused, an
     * unknown one needs the admin's confirmation in the modal (force=1 on the
     * confirmed resubmit), a known or trusted one proceeds untouched.
     */
    public function activate(string $id): void
    {
        $service = $this->service();
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->redirect('/admin/themes');
            return;
        }

        $data = $this->app->request()->data->getData();
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $force = !empty($data['force']);

        $refusal = $this->themeTrustVerdict($theme, $force, $isAjax);
        if ($refusal !== null) {
            if (method_exists($this->app, 'session')) {
                $this->app->session()->flash('error', 'Theme ' . $refusal);
            }
            $this->app->redirect('/admin/themes');
            return;
        }

        $status = $service->activate((int) $id);

        if ($isAjax) {
            if ($status === 'activated') {
                $this->app->jsonHalt(['ok' => true]);
            }
            $errorText = match ($status) {
                'not_found' => 'Theme not found.',
                'disabled'  => 'Theme is disabled and cannot be activated.',
                'invalid'   => 'Theme failed validation (PHP detected in template files).',
                default     => 'Could not activate theme.',
            };
            // Flash also: the JS reloads after the JSON answer and the flash
            // is how the error reaches the fresh page.
            if (method_exists($this->app, 'session')) {
                $this->app->session()->flash('error', $errorText);
            }
            $this->app->jsonHalt(['ok' => false, 'error' => $errorText]);
        }

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
     * Manual recheck of one theme by folder (AJAX), for screens that list
     * themes by folder rather than by DB id (the Updates screen inventory).
     */
    public function recheckByFolder(): void
    {
        $folder = (string) ($this->app->request()->data->getData()['folder'] ?? '');
        if ($folder === '') {
            $this->app->jsonHalt(['ok' => false, 'error' => 'No theme given.'], 422);
        }

        try {
            $item = $this->app->trustClient()->themeItem($folder);
        } catch (\Throwable) {
            $item = null;
        }

        if ($item === null) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'This theme has no version to check.'], 422);
        }

        $result = $this->trustLiveStatus($item);

        if (!$result['answered']) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'The trust service could not be reached.'], 503);
        }

        $this->app->jsonHalt([
            'ok'      => true,
            'status'  => $result['status'],
            'warning' => $result['warning'],
        ]);
    }

    /**
     * Manual recheck of one theme (AJAX). Re-derives the theme's identity
     * from its manifest, asks the trust service live, updates the cache, and
     * answers with the fresh status so the page can update the badge.
     */
    public function recheck(string $id): void
    {
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'Unknown theme.'], 404);
        }

        try {
            $item = $this->app->trustClient()->themeItem((string) $theme->folder);
        } catch (\Throwable) {
            $item = null;
        }

        if ($item === null) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'This theme has no version to check.'], 422);
        }

        $result = $this->trustLiveStatus($item);

        if (!$result['answered']) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'The trust service could not be reached.'], 503);
        }

        $this->app->jsonHalt([
            'ok'      => true,
            'status'  => $result['status'],
            'warning' => $result['warning'],
        ]);
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
        if (!is_array($optionDefs)) {
            $optionDefs = [];
        }

        $saved = [];
        $service = $this->service();
        foreach ($optionDefs as $key => $def) {
            if (!is_array($def)) {
                continue;
            }
            $key = (string) $key;
            if (($def['type'] ?? '') === 'group' && is_array($def['fields'] ?? null)) {
                foreach ($def['fields'] as $fKey => $fDef) {
                    if (!is_array($fDef)) {
                        continue;
                    }
                    $dbKey = $key . '.' . $fKey;
                    $saved[$dbKey] = $this->readOptionValue($service, (int) $id, $dbKey, $fDef);
                }
            } else {
                $saved[$key] = $this->readOptionValue($service, (int) $id, $key, $def);
            }
        }

        $mediaPickers = [];
        try {
            $media = $this->app->media();
            foreach ($optionDefs as $key => $def) {
                if (!is_array($def)) {
                    continue;
                }
                if (($def['type'] ?? '') === 'group' && is_array($def['fields'] ?? null)) {
                    foreach ($def['fields'] as $fKey => $fDef) {
                        if (!is_array($fDef)) {
                            continue;
                        }
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
            'pageTitle' => 'Theme Options: ' . $theme->name,
            'theme'     => $theme,
            'options'   => $optionDefs,
            'saved'     => $saved,
            'mediaPickers' => $mediaPickers,
            'fontChoices' => $this->fontChoices(),
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
        if (!is_array($optionDefs)) {
            $optionDefs = [];
        }

        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $service = $this->service();
        $posted = $post['options'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }

        foreach ($optionDefs as $key => $def) {
            if (!is_array($def)) {
                continue;
            }
            $key = (string) $key;
            if (($def['type'] ?? '') === 'group' && is_array($def['fields'] ?? null)) {
                $group = is_array($posted[$key] ?? null) ? $posted[$key] : [];
                foreach ($def['fields'] as $fKey => $fDef) {
                    if (!is_array($fDef)) {
                        continue;
                    }
                    $dbKey = $key . '.' . $fKey;
                    $value = $group[$fKey] ?? '';
                    $service->saveThemeOption((int) $id, $dbKey, $this->storedOptionValue($value, $fDef));
                }
            } else {
                $value = $posted[$key] ?? '';
                $service->saveThemeOption((int) $id, $key, $this->storedOptionValue($value, $def));
            }
        }

        if (method_exists($this->app, 'session')) {
            $this->app->session()->flash('success', 'Theme options saved.');
        }

        $this->app->redirect("/admin/themes/{$id}/options");
    }

    /**
     * Region manager, show all regions, placements, available blocks, orphans.
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
            'fontChoices' => $this->fontChoices(),
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

        if (is_string($regionId) && $regionId !== ''
            && is_string($blockKey) && $blockKey !== ''
            && isset($this->regionManager()->getAvailableBlocks()[$blockKey])) {
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

        // Look up the block's option definitions to sanitize textarea fields
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();
        $blockDef = $this->regionManager()->getAvailableBlocks()[$placement->block_key ?? ''] ?? [];

        /** @var array<string, array<string, mixed>> $optionDefs */
        $optionDefs = $blockDef['options'] ?? [];

        $values = $post['values'] ?? [];
        if (!is_array($values)) {
            $values = [];
        }

        $this->regionManager()->savePlacementValues(
            $placementId,
            $this->sanitizeOptionValues($values, $optionDefs)
        );
        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Sanitize submitted block option values against the block's option
     * definitions. Values keep the shape they were submitted in, so the edit
     * form reads repeater rows back as rows.
     *
     * @param array<array-key, mixed>             $values
     * @param array<string, array<string, mixed>> $optionDefs
     * @return array<array-key, mixed>
     */
    private function sanitizeOptionValues(array $values, array $optionDefs): array
    {
        $clean = [];

        foreach ($values as $key => $val) {
            $def = $optionDefs[(string) $key] ?? [];
            $clean[$key] = $this->sanitizeOptionValue($val, $def);
        }

        return $clean;
    }

    /**
     * Sanitize one submitted value against its option definition. An array is
     * a repeater field and recurses through the definition's sub-fields;
     * scalars are cast to string and purified when the option is a textarea.
     *
     * @param mixed                $value
     * @param array<string, mixed> $def
     * @return mixed
     */
    private function sanitizeOptionValue(mixed $value, array $def): mixed
    {
        if (is_array($value)) {
            $subDefs = $def['fields'] ?? [];

            /** @var array<string, array<string, mixed>> $subDefs */
            $subDefs = is_array($subDefs) ? $subDefs : [];

            $rows = [];
            foreach ($value as $rowKey => $row) {
                if ((string) $rowKey === '__INDEX__') {
                    continue;
                }
                $rows[$rowKey] = is_array($row)
                    ? $this->sanitizeOptionValues($row, $subDefs)
                    : (string) $row;
            }

            return $rows;
        }

        $string = (string) $value;

        return ($def['type'] ?? '') === 'textarea' ? $this->purifyHtml($string) : $string;
    }

    /**
     * Sanitize HTML via HTMLPurifier.
     */
    private function purifyHtml(string $html): string
    {
        return (new \HTMLPurifier(HtmlPurifierFactory::create()))->purify($html);
    }

    /**
     * The trust verdict for a theme activation, via the shared admin gate.
     */
    private function themeTrustVerdict(Theme $theme, bool $force, bool $isAjax): ?string
    {
        try {
            $item = $this->app->trustClient()->themeItem((string) $theme->folder);
        } catch (\Throwable) {
            $item = null;
        }

        $payload = [
            'id'      => (int) $theme->id,
            'name'    => (string) $theme->name,
            'version' => $item['version'] ?? '',
        ];

        return $this->trustGate(
            $item,
            'theme',
            $payload,
            $force,
            $isAjax,
            fn (): array => $this->storeGateFor((string) $theme->folder, (string) $theme->name)
        );
    }

    /**
     * Font stacks offered by the font option field.
     *
     * @return array<string, string> Label => CSS font-family stack
     */
    protected function fontChoices(): array
    {
        return [
            'System Default'    => 'system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
            'Arial'             => 'Arial, Helvetica, sans-serif',
            'Verdana'           => 'Verdana, Geneva, sans-serif',
            'Tahoma'            => 'Tahoma, Geneva, sans-serif',
            'Trebuchet MS'      => '"Trebuchet MS", Helvetica, sans-serif',
            'Georgia'           => 'Georgia, "Times New Roman", serif',
            'Times New Roman'   => '"Times New Roman", Times, serif',
            'Palatino'          => 'Palatino, "Palatino Linotype", "Book Antiqua", serif',
            'Garamond'          => 'Garamond, "Times New Roman", serif',
            'Courier New'       => '"Courier New", Courier, monospace',
            'Monospace'         => 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
        ];
    }

    /**
     * Read one stored theme option, decoding repeater JSON into rows.
     *
     * @param array<string, mixed> $def Field definition
     * @return mixed
     */
    private function readOptionValue(ThemeService $service, int $themeId, string $dbKey, array $def): mixed
    {
        $default = $def['default'] ?? '';
        if (is_array($default)) {
            $default = json_encode($default);
        }

        $value = $service->getThemeOption($themeId, $dbKey, is_string($default) ? $default : '');

        if (($def['type'] ?? '') === 'repeater') {
            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return $value;
    }

    /**
     * Turn a posted theme option value into the string stored in the table.
     * Repeater rows are stored as JSON.
     *
     * @param mixed                $value
     * @param array<string, mixed> $def
     */
    private function storedOptionValue(mixed $value, array $def): string
    {
        if (($def['type'] ?? '') === 'repeater') {
            $rows = is_array($value) ? $this->sanitizeThemeRepeaterRows($value, $def) : [];
            $encoded = json_encode($rows);

            return $encoded === false ? '[]' : $encoded;
        }

        if (($def['type'] ?? '') === 'textarea' && is_string($value)) {
            return $this->purifyHtml($value);
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Purify textarea subfields in repeater rows, matching the block side.
     *
     * @param array<array-key, mixed> $rows
     * @param array<string, mixed>    $def
     * @return array<array-key, mixed>
     */
    private function sanitizeThemeRepeaterRows(array $rows, array $def): array
    {
        $fields = is_array($def['fields'] ?? null) ? $def['fields'] : [];
        $clean = [];

        foreach ($rows as $rowKey => $row) {
            if ((string) $rowKey === '__INDEX__') {
                continue;
            }
            if (!is_array($row)) {
                $clean[$rowKey] = is_scalar($row) ? (string) $row : '';
                continue;
            }

            foreach ($row as $subKey => $subValue) {
                $subDef = $fields[$subKey] ?? [];
                if (!is_array($subDef)) {
                    $subDef = [];
                }

                if (($subDef['type'] ?? '') === 'textarea' && is_string($subValue)) {
                    $row[$subKey] = $this->purifyHtml($subValue);
                } elseif (is_scalar($subValue)) {
                    $row[$subKey] = (string) $subValue;
                } else {
                    $row[$subKey] = '';
                }
            }

            $clean[$rowKey] = $row;
        }

        return $clean;
    }

    /**
     * Read pubvana.json for a theme folder.
     *
     * @return array<string, mixed>
     */
    private function readThemeInfo(string $folder): array
    {
        $root = PROJECT_ROOT;
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
