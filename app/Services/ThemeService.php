<?php

namespace App\Services;

use App\Models\ThemeModel;
use App\Models\WidgetAreaModel;
use App\Libraries\TemplateEngine\Engine;
use App\Models\NavigationModel;
use App\Models\SocialModel;
use App\Services\PluginManager;

class ThemeService
{
    protected ?object $activeTheme = null;

    private ?Engine $engine = null;

    public function discover(): array
    {
        $themes = [];
        foreach (glob(THEMES_PATH . '*', GLOB_ONLYDIR) as $dir) {
            $jsonFile = $dir . '/theme_info.json';
            $phpFile  = $dir . '/theme_info.php';

            if (is_file($jsonFile)) {
                $info = json_decode(file_get_contents($jsonFile), true);
            } elseif (is_file($phpFile)) {
                // Legacy fallback during transition
                $info = require $phpFile;
            } else {
                continue;
            }

            if (! is_array($info)) {
                continue;
            }

            $info['folder'] = basename($dir);
            $themes[] = $info;
        }
        return $themes;
    }

    public function sync(): void
    {
        $model = new ThemeModel();
        $now   = date('Y-m-d H:i:s');

        foreach ($this->discover() as $info) {
            $folder = $info['folder'];
            if (! $model->where('folder', $folder)->first()) {
                $model->insert([
                    'name'         => $info['name']    ?? $folder,
                    'folder'       => $folder,
                    'version'      => $info['version'] ?? 'unknown',
                    'is_active'    => 0,
                    'installed_at' => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }

            // Validate — check for PHP tags
            $this->validationResults[$folder] = $this->validateTheme($folder);

            // Publish assets for all discovered themes (screenshots, etc.)
            $this->publishAssets($folder);

            // Seed default options if not already saved
            $theme = $model->where('folder', $folder)->first();
            if ($theme) {
                $this->syncDefaultOptions($theme);
            }
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
        $this->activeTheme = $model->where('is_active', 1)->first();
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

        $path = THEMES_PATH . $theme->folder . '/views/' . $name . '.tpl';
        if (! is_file($path)) {
            return '<p>Theme view not found: ' . esc($name) . '</p>';
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
            $navModel = new NavigationModel();
            $primaryNav = $navModel->where('nav_group', 'primary')->orderBy('sort_order')->findAll();
            $footerNav  = $navModel->where('nav_group', 'footer')->orderBy('sort_order')->findAll();
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
            $rows = db_connect()->table('theme_options')
                ->where('theme_id', $theme->id)
                ->get()->getResultObject();
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
        $hcaptchaSiteKey   = env('hcaptcha.siteKey') ?: '';
        $isPremiumActive   = (new PremiumService())->isLicensed();

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
            'flash_success'      => $flashSuccess,
            'flash_error'        => $flashError,
            'analytics_id'       => $analyticsId,
            'sitemap_enabled'    => $sitemapEnabled,
            'comments_enabled'   => $commentsEnabled,
            'comment_moderation' => $commentModeration,
            'hcaptcha_site_key'  => $hcaptchaSiteKey,
            'is_premium_active'  => $isPremiumActive,
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

    public function activate(int $id): bool
    {
        $model = new ThemeModel();
        $theme = $model->find($id);
        if (! $theme) {
            return false;
        }

        // Dev domains bypass all license checks
        $host        = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');
        $isDevDomain = $host === 'localhost' || str_ends_with($host, '.local');

        if (! $isDevDomain) {
            $mItem = db_connect()->table('marketplace_items')
                ->where('slug', $theme->folder)->get()->getRowObject();
            if ($mItem && ! empty($mItem->license_key) && (int) $mItem->license_valid === 0) {
                return false;
            }
        }

        $model->where('id !=', $id)->set('is_active', 0)->update();
        $model->update($id, ['is_active' => 1]);

        $this->activeTheme = null;
        $this->syncWidgetAreas($theme);
        $this->publishAssets($theme->folder);

        return true;
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

        $db = db_connect();
        foreach ($options as $key => $def) {
            $exists = $db->table('theme_options')
                ->where('theme_id', $theme->id)
                ->where('option_key', $key)
                ->countAllResults();

            if ($exists === 0) {
                $db->table('theme_options')->insert([
                    'theme_id'     => $theme->id,
                    'option_key'   => $key,
                    'option_value' => $def['default'] ?? '',
                ]);
            }
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
     * Return pagination CSS classes from the active theme's widget_classes.
     *
     * Themes override these via cls_pager_* keys in theme_info.json widget_classes.
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
        $wc   = $info['widget_classes'] ?? [];

        foreach ($defaults as $key => $fallback) {
            $defaults[$key] = $wc[$key] ?? $fallback;
        }

        return $defaults;
    }

    public function getThemeOption(int $themeId, string $key, mixed $default = null): mixed
    {
        $db  = db_connect();
        $row = $db->table('theme_options')
            ->where('theme_id', $themeId)
            ->where('option_key', $key)
            ->get()->getRowObject();

        return $row ? $row->option_value : $default;
    }

    public function saveThemeOption(int $themeId, string $key, mixed $value): void
    {
        $db  = db_connect();
        $row = $db->table('theme_options')
            ->where('theme_id', $themeId)
            ->where('option_key', $key)
            ->get()->getRowObject();

        if ($row) {
            $db->table('theme_options')
                ->where('theme_id', $themeId)
                ->where('option_key', $key)
                ->update(['option_value' => $value]);
        } else {
            $db->table('theme_options')->insert([
                'theme_id'     => $themeId,
                'option_key'   => $key,
                'option_value' => $value,
            ]);
        }
    }
}
