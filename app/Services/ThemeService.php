<?php

namespace App\Services;

use App\Models\AdminNotificationModel;
use App\Models\ThemeModel;
use App\Models\WidgetAreaModel;
use App\Libraries\TemplateEngine\Engine;
use App\Models\NavigationModel;
use App\Models\SocialModel;
use App\Models\MarketplaceLicenseModel;
use App\Services\PluginManager;
use App\Services\VettingService;

class ThemeService
{
    protected ?object $activeTheme = null;

    private ?Engine $engine = null;

    public function discover(): array
    {
        $themes = [];
        foreach (glob(THEMES_PATH . '*', GLOB_ONLYDIR) as $dir) {
            $folder   = basename($dir);
            $jsonFile = $dir . '/theme_info.json';
            $phpFile  = $dir . '/theme_info.php';

            if (is_file($jsonFile)) {
                $info = json_decode(file_get_contents($jsonFile), true);
            } elseif (is_file($phpFile)) {
                $info = require $phpFile;
            } else {
                continue; // No info file at all — not an addon, skip entirely
            }

            $disabledReason = null;

            if (! is_array($info)) {
                $disabledReason = lang('Admin.addonDisabledInvalidJson', [$folder, 'theme_info.json']);
                $info = [];
            } else {
                $required = ['name', 'version', 'description', 'author'];
                $missing  = array_diff($required, array_keys($info));
                if (! empty($missing)) {
                    $disabledReason = lang('Admin.addonDisabledMissingFields', [$folder, implode(', ', $missing)]);
                }
            }

            $info['folder']           = $folder;
            $info['_disabled_reason'] = $disabledReason;
            $themes[] = $info;
        }
        return $themes;
    }

    public function sync(): void
    {
        $model = new ThemeModel();
        $now   = date('Y-m-d H:i:s');
        $hasNew = false;

        // Remove orphaned records — theme folder deleted from disk
        $registered = $model->findAll();
        foreach ($registered as $row) {
            if (! is_dir(THEMES_PATH . $row->folder)) {
                $model->delete($row->id);
            }
        }

        foreach ($this->discover() as $info) {
            $folder         = $info['folder'];
            $disabledReason = $info['_disabled_reason'];
            $existing       = $model->where('folder', $folder)->first();

            $metaFields = [
                // Flags
                'bundled'             => ! empty($info['bundled']) ? 1 : 0,
                'free'                => ! empty($info['free'])    ? 1 : 0,
                // Support & store URLs
                'support_url'         => $info['support_url']         ?? null,
                'author_url'          => $info['author_url']          ?? null,
                'items_url'           => $info['items_url']           ?? null,
                'item_url'            => $info['item_url']            ?? null,
                'store_url'           => $info['store_url']           ?? null,
                // Category URLs
                'categories_url'      => $info['categories_url']      ?? null,
                'categories_all_url'  => $info['categories_all_url']  ?? null,
                'category_url'        => $info['category_url']        ?? null,
                // Discovery URLs
                'featured_url'        => $info['featured_url']        ?? null,
                // License URLs
                'license_validate_url' => $info['license_validate_url'] ?? null,
                'license_check_url'   => $info['license_check_url']   ?? null,
                // Update URLs
                'update_url'          => $info['update_url']          ?? null,
                'update_check_url'    => $info['update_check_url']    ?? null,
                'download_url'        => $info['download_url']        ?? null,
            ];

            // ── Disabled → always force inactive ────────────────────────
            if ($disabledReason !== null) {
                if ($existing) {
                    $model->update($existing->id, array_merge([
                        'is_active'       => 0,
                        'disabled'        => 1,
                        'disabled_reason' => $disabledReason,
                    ], $metaFields));
                } else {
                    $model->insert(array_merge([
                        'name'            => $info['name']        ?? $folder,
                        'folder'          => $folder,
                        'description'     => $info['description'] ?? '',
                        'version'         => $info['version']     ?? '0.0.0',
                        'author'          => VettingService::normalizeAuthor($info['author'] ?? ''),
                        'is_active'       => 0,
                        'disabled'        => 1,
                        'disabled_reason' => $disabledReason,
                        'installed_at'    => $now,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ], $metaFields));
                    $hasNew = true;
                }

                // Still validate and publish assets for disabled themes
                $this->validationResults[$folder] = $this->validateTheme($folder);
                $this->publishAssets($folder);
                continue;
            }

            // ── Valid addon ─────────────────────────────────────────────
            if (! $existing) {
                $model->insert(array_merge([
                    'name'         => $info['name'],
                    'folder'       => $folder,
                    'description'  => $info['description'],
                    'version'      => $info['version'],
                    'author'       => VettingService::normalizeAuthor($info['author'] ?? ''),
                    'is_active'    => 0,
                    'installed_at' => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ], $metaFields));
                $hasNew = true;
            } else {
                $newVersion = $info['version'];
                $newAuthor  = VettingService::normalizeAuthor($info['author'] ?? '');
                $clearDisabled = [];
                if (! empty($existing->disabled)) {
                    $clearDisabled = ['disabled' => null, 'disabled_reason' => null];
                }

                if ($newVersion !== ($existing->version ?? '')) {
                    $model->update($existing->id, array_merge([
                        'version'     => $newVersion,
                        'name'        => $info['name'],
                        'description' => $info['description'],
                        'author'      => $newAuthor,
                        'pv_safe'     => null,
                    ], $metaFields, $clearDisabled));
                    $hasNew = true;
                } elseif (($info['name'] ?? '') !== ($existing->name ?? '') || ($info['description'] ?? '') !== ($existing->description ?? '')) {
                    $model->update($existing->id, array_merge([
                        'name'        => $info['name'],
                        'description' => $info['description'],
                        'author'      => $newAuthor,
                    ], $metaFields, $clearDisabled));
                } elseif ($newAuthor !== ($existing->author ?? '')) {
                    $model->update($existing->id, array_merge([
                        'author' => $newAuthor,
                    ], $metaFields, $clearDisabled));
                } elseif ($existing->support_url === null || $existing->author_url === null || ! empty($existing->disabled)) {
                    $model->update($existing->id, array_merge($metaFields, $clearDisabled));
                }

            }

            // Validate - check for PHP tags
            $this->validationResults[$folder] = $this->validateTheme($folder);

            // Publish assets for all discovered themes
            $this->publishAssets($folder);

            // Seed default options
            $theme = $model->where('folder', $folder)->first();
            if ($theme) {
                $this->syncDefaultOptions($theme);
            }
        }

        if ($hasNew) {
            (new VettingService())->checkApproval();
        }
    }

    /**
     * Get validation results (populated after sync() runs).
     *
     * @return array<string, bool> folder => isValid
     */
    public function getValidationResults(): array
    {
        return $this->validationResults;
    }

    public function getActive(): ?object
    {
        if ($this->activeTheme !== null) {
            return $this->activeTheme;
        }
        $model = new ThemeModel();
        $this->activeTheme = $model->where('is_active', 1)->where('disabled IS NULL')->first();
        return $this->activeTheme;
    }

    private function getEngine(): Engine
    {
        if ($this->engine === null) {
            $this->engine = new Engine();
        }
        return $this->engine;
    }

    /**
     * Render a theme view with the template engine.
     *
     * ThemeService owns the full data bag: it loads common data (nav, settings,
     * theme options, auth state, etc.) internally, then merges in page-specific
     * data from the controller.
     *
     * @param string $name     View name (e.g. 'home', 'post', 'page')
     * @param array  $pageData Page-specific data from the controller
     * @return string Rendered HTML
     */
    public function view(string $name, array $pageData = []): string
    {
        $theme = $this->getActive();
        if (! $theme) {
            return '<p>No active theme.</p>';
        }

        // Namespaced path (plugin view) — resolve via FileLocator
        if (str_contains($name, '\\')) {
            $path = service('locator')->locateFile($name, 'Views', 'tpl');
            if (! $path) {
                return '<p>Plugin view not found: ' . esc($name) . '</p>';
            }
        } else {
            $path = THEMES_PATH . $theme->folder . '/views/' . $name . '.tpl';
            if (! is_file($path)) {
                return '<p>Theme view not found: ' . esc($name) . '</p>';
            }
        }

        // Check cache (skip for logged-in users — they may see different content)
        $cacheTtl = (int) (setting('App.pageCacheTtl') ?? 120);
        $useCache  = $cacheTtl > 0 && ! $this->isLoggedIn();

        if ($useCache) {
            $cacheKey = $this->buildCacheKey($name, $pageData);
            $cached = cache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Pre-render pager to HTML if present
        if (isset($pageData['pager'])) {
            $pageData['pager_links'] = $pageData['pager']->links('default', 'theme_pager');
            unset($pageData['pager']);
        }

        $data = array_merge($this->buildCommonData(), $pageData);

        // Build page_title for the <title> tag: "Post Title - Site Name" or just "Site Name"
        $siteName = $data['site_name'] ?? '';
        $seoTitle = $data['seo']['title'] ?? '';
        $data['page_title'] = ($seoTitle !== '' && $seoTitle !== $siteName)
            ? $seoTitle . ' - ' . $siteName
            : $siteName;

        $basePath = THEMES_PATH . $theme->folder . '/views/';
        $html = $this->getEngine()->render($path, $data, $basePath);

        if ($useCache) {
            cache()->save($cacheKey, $html, $cacheTtl);
        }

        return $html;
    }

    private function isLoggedIn(): bool
    {
        try {
            return auth()->loggedIn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function buildCacheKey(string $viewName, array $pageData): string
    {
        $theme = $this->getActive();
        $uri = service('request')->getUri()->getPath();
        $locale = service('request')->getLocale();

        return 'page_cache_' . md5(
            ($theme->folder ?? 'none') . '|' . $viewName . '|' . $uri . '|' . $locale
        );
    }

    /**
     * Build the common data bag that every theme view receives.
     *
     * This replaces what BaseController::initController() used to build.
     */
    private function buildCommonData(): array
    {
        $theme = $this->getActive();
        $request = service('request');

        // Navigation
        try {
            $navService = new \App\Services\NavigationService();
            $primaryNav = $navService->getTree('primary');
            $footerNav  = $navService->getTree('footer');
        } catch (\Throwable $e) {
            $primaryNav = [];
            $footerNav  = [];
        }

        // Social links
        try {
            $socialLinks = (new SocialModel())->where('is_active', 1)->orderBy('sort_order')->findAll();
        } catch (\Throwable $e) {
            $socialLinks = [];
        }

        // Plugin menu items (boot already ran at pre_system)
        try {
            $pluginMenuItems = PluginManager::instance()->getMenuItems();
        } catch (\Throwable $e) {
            $pluginMenuItems = [];
        }

        // Theme options — load ALL options for active theme into the bag by key name
        $themeOptions = [];
        if ($theme) {
            $optionModel = model(\App\Models\ThemeOptionModel::class);
            $rows = $optionModel->getForTheme($theme->id);
            foreach ($rows as $row) {
                $themeOptions[$row->option_key] = $row->option_value;
            }
        }

        // Locale
        $locale = $request->getLocale();
        if (empty($locale)) {
            $locale = config('App')->defaultLocale;
        }

        // Auth state
        $isLoggedIn = $this->isLoggedIn();

        // Flash messages
        $flashSuccess = session()->getFlashdata('success');
        $flashError   = session()->getFlashdata('error');

        // Settings
        $analyticsId       = setting('Seo.googleAnalytics');
        $sitemapEnabled    = (bool) setting('Seo.sitemapEnabled');
        $commentsEnabled   = (bool) setting('App.commentsEnabled');
        $commentModeration = (bool) setting('App.commentModeration');
        $hcaptchaSiteKey   = setting('App.hcaptchaSiteKey') ?? '';

        return array_merge($themeOptions, [
            'theme'              => $theme,
            'site_name'          => site_name(),
            'site_tagline'       => site_tagline(),
            'locale'             => $locale,
            'primary_nav'        => $primaryNav,
            'footer_nav'         => $footerNav,
            'social_links'       => $socialLinks,
            'plugin_menu_items'  => $pluginMenuItems,
            'is_logged_in'       => $isLoggedIn,
            'can_access_admin'   => $isLoggedIn && auth()->user()->can('admin.access'),
            'flash_success'      => $flashSuccess,
            'flash_error'        => $flashError,
            'analytics_id'       => $analyticsId,
            'sitemap_enabled'    => $sitemapEnabled,
            'comments_enabled'   => $commentsEnabled,
            'comment_moderation' => $commentModeration,
            'hcaptcha_site_key'  => $hcaptchaSiteKey,
            'csrf_field'         => csrf_field(),
            'csrf_token_name'    => csrf_token(),
            'csrf_token_value'   => csrf_hash(),
            'lang_switcher'      => $this->langSwitcherData,
        ]);
    }

    /**
     * Allow controllers to inject the language switcher data.
     * Called after buildLangSwitcher() in public controllers.
     */
    public function setLangSwitcher(array $langSwitcher): void
    {
        $this->langSwitcherData = $langSwitcher;
    }

    private array $langSwitcherData = [];

    /**
     * Scan all files in a theme directory for PHP tags.
     * Returns true if the theme is clean, false if PHP is found.
     */
    public function validateTheme(string $folder): bool
    {
        $dir = THEMES_PATH . $folder;
        if (! is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            // Only scan text-like files, skip images/fonts/binaries
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'zip'], true)) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if (str_contains($content, '<?php') || str_contains($content, '<?=') || str_contains($content, '<%')) {
                return false;
            }
        }

        return true;
    }

    private array $validationResults = [];

    public function activate(int $id): string
    {
        $model = new ThemeModel();
        $theme = $model->find($id);
        if (! $theme) {
            return 'not_found';
        }

        if (! empty($theme->disabled)) {
            return 'disabled';
        }

        $isPubvana      = in_array($theme->author ?? '', ['pubvana', 'pubvana_team'], true);
        $isBundled      = ! empty($theme->bundled);
        $isFree         = ! empty($theme->free);
        $hasLicenseUrls = ! empty($theme->license_validate_url) || ! empty($theme->item_url);

        // Abuse/tamper checks
        if (! $isPubvana && $isBundled) {
            return 'tampered_bundled';
        }
        if (! $isPubvana && ! $isFree && ! $hasLicenseUrls) {
            return 'tampered_no_urls';
        }
        if ($isPubvana && $isFree && ! $isBundled) {
            return 'tampered_free_flag';
        }

        // Activation chain
        if ($isBundled && $isPubvana) {
            // Bundled Pubvana — activate, no check
        } elseif ($isPubvana) {
            // Pubvana paid — require valid license
            if ($theme->store_product_id) {
                $license = (new MarketplaceLicenseModel())->where('store_product_id', $theme->store_product_id)->first();
                if (! $license || (int) ($license->license_valid ?? -1) !== 1) {
                    return 'invalid_license';
                }
            } else {
                return 'invalid_license'; // No store product ID = can't validate
            }
        } elseif ($isFree) {
            // Third party free — activate, no check
        } elseif ($hasLicenseUrls) {
            // Third party paid with license URLs — check against their API
            if ($theme->store_product_id) {
                $license = (new MarketplaceLicenseModel())->where('store_product_id', $theme->store_product_id)->first();
                if (! $license || (int) ($license->license_valid ?? -1) !== 1) {
                    return 'invalid_license';
                }
            } else {
                return 'invalid_license';
            }
        } else {
            return 'tampered_no_urls'; // Belt-and-suspenders
        }

        // All checks passed — activate
        $model->where('id !=', $id)->set('is_active', 0)->update();
        $model->update($id, ['is_active' => 1]);

        $this->activeTheme = null;
        $this->syncWidgetAreas($theme);
        $this->publishAssets($theme->folder);

        return 'activated';
    }

    protected function syncWidgetAreas(object $theme): void
    {
        $jsonFile = THEMES_PATH . $theme->folder . '/theme_info.json';
        $phpFile  = THEMES_PATH . $theme->folder . '/theme_info.php';

        if (is_file($jsonFile)) {
            $info = json_decode(file_get_contents($jsonFile), true) ?? [];
        } elseif (is_file($phpFile)) {
            $info = require $phpFile;
        } else {
            return;
        }

        $areas = $info['widget_areas'] ?? [];

        $areaModel = new WidgetAreaModel();

        // Build a map of existing areas by slug so we don't delete instances
        $existing = [];
        foreach ($areaModel->where('theme_id', $theme->id)->findAll() as $row) {
            $existing[$row->slug] = $row;
        }

        // Insert only areas that don't already exist; update name if it changed
        foreach ($areas as $slug => $name) {
            if (isset($existing[$slug])) {
                if ($existing[$slug]->name !== $name) {
                    $areaModel->update($existing[$slug]->id, ['name' => $name]);
                }
                unset($existing[$slug]);
            } else {
                $areaModel->insert([
                    'name'     => $name,
                    'slug'     => $slug,
                    'theme_id' => $theme->id,
                ]);
            }
        }

        // Remove areas no longer in theme_info (slug removed from theme)
        foreach ($existing as $obsolete) {
            $areaModel->delete($obsolete->id);
        }
    }

    /**
     * Seed default theme options into the DB if they don't already exist.
     */
    protected function syncDefaultOptions(object $theme): void
    {
        $jsonFile = THEMES_PATH . $theme->folder . '/theme_info.json';
        if (! is_file($jsonFile)) {
            return;
        }

        $info    = json_decode(file_get_contents($jsonFile), true) ?? [];
        $options = $info['options'] ?? [];
        if (empty($options)) {
            return;
        }

        $optionModel = model(\App\Models\ThemeOptionModel::class);
        foreach ($options as $key => $def) {
            $optionModel->seedDefault($theme->id, $key, $def['default'] ?? '');
        }
    }

    /**
     * Copy theme assets to the web-accessible directory.
     *
     * Copies themes/{folder}/assets/* → FCPATH/themes/{folder}/
     * Replaces the old symlink approach — real files, no symlinks needed.
     */
    public function publishAssets(string $folder): void
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $folder)) {
            throw new \RuntimeException('Invalid theme folder name: ' . $folder);
        }

        $source = THEMES_PATH . $folder . '/assets';
        $dest   = FCPATH . 'themes/' . $folder;

        if (! is_dir($source)) {
            return;
        }

        // Clean replace: remove existing destination
        if (is_dir($dest)) {
            $this->removeDirectory($dest);
        }

        // Also remove any existing symlink from the old system
        if (is_link($dest)) {
            unlink($dest);
        }

        // Ensure parent directory exists
        $parentDir = FCPATH . 'themes';
        if (! is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        $this->copyDirectory($source, $dest);
    }

    /** Recursively copy a directory. */
    private function copyDirectory(string $source, string $dest): void
    {
        mkdir($dest, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $dest . '/' . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /** Recursively remove a directory and all its contents. */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    /**
     * Return pagination CSS classes from the active theme's css_class_mapping.
     *
     * Themes override these via cls_pager_* keys in theme_info.json css_class_mapping.
     * Defaults are framework-agnostic pv-* classes.
     */
    public function getPaginationClasses(): array
    {
        $defaults = [
            'cls_pager_list'     => 'pv-pagination',
            'cls_pager_item'     => 'pv-page-item',
            'cls_pager_link'     => 'pv-page-link',
            'cls_pager_active'   => 'pv-page-active',
            'cls_pager_disabled' => 'pv-page-disabled',
        ];

        $theme = $this->getActive();
        if (! $theme) {
            return $defaults;
        }

        $jsonPath = THEMES_PATH . $theme->folder . '/theme_info.json';
        if (! is_file($jsonPath)) {
            return $defaults;
        }

        $info = json_decode(file_get_contents($jsonPath), true);
        $wc   = $info['css_class_mapping'] ?? [];

        foreach ($defaults as $key => $fallback) {
            $defaults[$key] = $wc[$key] ?? $fallback;
        }

        return $defaults;
    }

    public function getThemeOption(int $themeId, string $key, mixed $default = null): mixed
    {
        return model(\App\Models\ThemeOptionModel::class)->getOption($themeId, $key, $default);
    }

    public function saveThemeOption(int $themeId, string $key, mixed $value): void
    {
        model(\App\Models\ThemeOptionModel::class)->saveOption($themeId, $key, (string) $value);
    }
}
