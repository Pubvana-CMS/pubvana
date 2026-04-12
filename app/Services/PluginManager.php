<?php

namespace App\Services;

use App\Interfaces\PluginInterface;
use App\Models\AdminNotificationModel;
use App\Models\MarketplaceLicenseModel;
use App\Models\PluginModel;
use App\Services\VettingService;

class PluginManager
{
    private static ?self $instance = null;

    /** @var PluginInterface[] keyed by slug */
    private array $plugins = [];

    /** @var array<string, string> slug => base path */
    private array $pluginPaths = [];

    private bool $booted = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Reset singleton state. For testing only.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Boot the plugin system.
     *
     * Called early in the request lifecycle (pre_system event).
     * Reads the `plugins` table, loads only active plugins, registers
     * their PSR-4 namespaces, calls register(), and applies CSRF exemptions.
     *
     * Safe to call multiple times — only runs once per request.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        try {
            $activePlugins = model(PluginModel::class)
                ->where('is_active', 1)
                ->where('disabled IS NULL')
                ->findAll();
        } catch (\Throwable $e) {
            // Table doesn't exist yet (fresh install before migrations).
            log_message('debug', 'PluginManager::boot skipped: ' . $e->getMessage());
            return;
        }

        foreach ($activePlugins as $row) {
            $this->loadPlugin($row->folder);
        }

        $this->registerCsrfExemptions();
    }

    /**
     * Backward-compat alias — delegates to boot().
     */
    public function loadAll(): void
    {
        $this->boot();
    }

    /**
     * CLI-only boot: register PSR-4 namespaces for active plugins
     * so their Commands/ directories are visible to spark's
     * command discovery.
     *
     * Called from Config\Services::commands() before the Commands
     * service is constructed.
     */
    public function cliBoot(): void
    {
        if ($this->booted) {
            return;
        }

        try {
            $activePlugins = model(PluginModel::class)
                ->where('is_active', 1)
                ->where('disabled IS NULL')
                ->findAll();
        } catch (\Throwable $e) {
            log_message('debug', 'PluginManager::cliBoot skipped: ' . $e->getMessage());
            return;
        }

        foreach ($activePlugins as $row) {
            $folder   = $row->folder;
            $basePath = PLUGINS_PATH . $folder . '/';

            if (is_dir($basePath . 'Commands')) {
                service('autoloader')->addNamespace('Plugins\\' . $folder, $basePath);
            }
        }
    }

    /**
     * Load a single plugin by folder name.
     *
     * Registers the PSR-4 namespace `Plugins\{Folder}\` pointing to the
     * plugin's directory, then instantiates `Plugins\{Folder}\Plugin`.
     */
    private function loadPlugin(string $folder): void
    {
        $basePath   = PLUGINS_PATH . $folder . '/';
        $pluginFile = $basePath . 'Plugin.php';

        if (! is_file($pluginFile)) {
            log_message('warning', "PluginManager: {$folder}/Plugin.php not found, skipping.");
            return;
        }

        // Register PSR-4 namespace so the autoloader can find plugin classes
        $namespace = 'Plugins\\' . $folder;
        service('autoloader')->addNamespace($namespace, $basePath);

        $className = $namespace . '\\Plugin';

        try {
            if (! class_exists($className)) {
                log_message('warning', "PluginManager: class {$className} not found after registering namespace.");
                return;
            }
        } catch (\Throwable $e) {
            log_message('error', "PluginManager: failed to load {$className} — {$e->getMessage()}");
            return;
        }

        if (! is_subclass_of($className, PluginInterface::class)) {
            log_message('warning', "PluginManager: {$className} does not implement PluginInterface, skipping.");
            return;
        }

        $plugin = new $className();

        try {
            $plugin->register();
        } catch (\Throwable $e) {
            log_message('error', "PluginManager: register() failed for {$folder} — {$e->getMessage()}");
            return;
        }

        $slug = $plugin->getSlug();
        $this->plugins[$slug]     = $plugin;
        $this->pluginPaths[$slug] = $basePath;
    }

    /**
     * Collect CSRF exemption routes from all loaded plugins
     * and merge them into the Filters config.
     */
    private function registerCsrfExemptions(): void
    {
        $exemptions = [];

        foreach ($this->plugins as $plugin) {
            $pluginExemptions = $plugin->getCsrfExemptions();
            if (! empty($pluginExemptions)) {
                $exemptions = array_merge($exemptions, $pluginExemptions);
            }
        }

        if (empty($exemptions)) {
            return;
        }

        $filters = config('Filters');

        // CSRF may be an array with 'except' key, a plain string, or absent
        if (isset($filters->globals['before']['csrf']['except'])) {
            $filters->globals['before']['csrf']['except'] = array_merge(
                $filters->globals['before']['csrf']['except'],
                $exemptions
            );
        } elseif (isset($filters->globals['before']['csrf'])) {
            // CSRF was listed as a plain string — convert to array form
            $filters->globals['before']['csrf'] = ['except' => $exemptions];
        } else {
            // CSRF not in globals at all — add it with exemptions
            $filters->globals['before']['csrf'] = ['except' => $exemptions];
        }
    }

    /**
     * Scan the plugins directory and sync to the DB.
     *
     * First removes DB records for plugins no longer on disk.
     * Then inserts new plugins as inactive, updates existing ones.
     *
     * @return array{discovered: string[], warnings: string[]}
     */
    public function discover(): array
    {
        $discovered = [];
        $warnings   = [];
        $model      = model(PluginModel::class);

        // Remove orphaned records — plugin folder deleted from disk
        $registered = $model->findAll();
        foreach ($registered as $row) {
            if (! is_dir(PLUGINS_PATH . $row->folder)) {
                $model->delete($row->id);
            }
        }

        foreach (glob(PLUGINS_PATH . '*/plugin_info.json') as $infoFile) {
            $folder = basename(dirname($infoFile));
            $raw    = file_get_contents($infoFile);
            $info   = json_decode($raw, true);

            // Look up existing record before validation so we can update/insert for broken plugins too
            $existing = $model->where('folder', $folder)->first();

            // Validate — build a disabled_reason if something is wrong
            $disabledReason = null;
            $required       = ['name', 'version', 'description', 'author'];

            if (! is_array($info)) {
                $disabledReason = lang('Admin.addonDisabledInvalidJson', [$folder, 'plugin_info.json']);
                $info           = [];
            } else {
                $missing = array_diff($required, array_keys($info));
                if (! empty($missing)) {
                    $disabledReason = lang('Admin.addonDisabledMissingFields', [$folder, implode(', ', $missing)]);
                }
            }

            $capabilities = isset($info['capabilities']) ? json_encode($info['capabilities']) : null;

            $metaFields = [
                // Flags
                'bundled'             => ! empty($info['bundled']) ? 1 : 0,
                'free'                => ! empty($info['free'])    ? 1 : 0,
                'capabilities'        => $capabilities,
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

            // --- Broken plugin: register/update with disabled flag ---
            if ($disabledReason !== null) {
                log_message('warning', 'PluginManager: ' . $disabledReason);
                $warnings[] = $disabledReason;

                if ($existing) {
                    $model->update($existing->id, [
                        'is_active'       => 0,
                        'disabled'        => 1,
                        'disabled_reason' => $disabledReason,
                    ]);
                } else {
                    $model->insert([
                        'folder'          => $folder,
                        'name'            => $info['name']        ?? $folder,
                        'version'         => $info['version']     ?? '0.0.0',
                        'description'     => $info['description'] ?? '',
                        'author'          => VettingService::normalizeAuthor($info['author'] ?? ''),
                        'is_active'       => 0,
                        'disabled'        => 1,
                        'disabled_reason' => $disabledReason,
                        'pv_safe'         => null,
                    ]);
                }
                continue;
            }

            // --- Valid plugin ---

            // If previously disabled, clear those flags on save
            $clearDisabled = ! empty($existing->disabled)
                ? ['disabled' => null, 'disabled_reason' => null]
                : [];

            if ($existing) {
                $newVersion = $info['version'];
                if ($newVersion !== $existing->version) {
                    // Version changed — update and reset approval (new code needs re-vetting)
                    $model->update($existing->id, array_merge([
                        'version'     => $newVersion,
                        'name'        => $info['name'],
                        'description' => $info['description'],
                        'author'      => VettingService::normalizeAuthor($info['author']),
                        'pv_safe'     => null,
                    ], $metaFields, $clearDisabled));
                } elseif ($info['name'] !== $existing->name || $info['description'] !== $existing->description) {
                    $model->update($existing->id, array_merge([
                        'name'        => $info['name'],
                        'description' => $info['description'],
                        'author'      => VettingService::normalizeAuthor($info['author']),
                    ], $metaFields, $clearDisabled));
                } elseif ($existing->support_url === null || $existing->author_url === null || ! empty($existing->disabled)) {
                    $model->update($existing->id, array_merge($metaFields, $clearDisabled));
                }

                continue;
            }

            // New plugin — insert as inactive, pv_safe NULL triggers API check
            $model->insert(array_merge([
                'folder'      => $folder,
                'name'        => $info['name'],
                'version'     => $info['version'],
                'description' => $info['description'],
                'author'      => VettingService::normalizeAuthor($info['author']),
                'is_active'   => 0,
                'pv_safe'     => null,
            ], $metaFields));

            $discovered[] = $folder;
        }

        // Poll pubvana.net for approval status of unchecked plugins (only if there are any)
        if (! empty($discovered)) {
            (new VettingService())->checkApproval();
        }

        return ['discovered' => $discovered, 'warnings' => $warnings];
    }

    /**
     * Activate a plugin by folder name.
     *
     * Returns a status string:
     *   'activated'              — plugin is now active
     *   'not_found'             — no such plugin in the DB
     *   'requires_confirmation' — pv_safe is not 1, caller must confirm
     *   'already_active'        — plugin was already active
     *
     * Pass $force = true to skip the approval check (after user confirms).
     */
    public function activate(string $folder, bool $force = false): string
    {
        $model  = model(PluginModel::class);
        $plugin = $model->where('folder', $folder)->first();

        if (! $plugin) {
            return 'not_found';
        }

        if ($plugin->is_active) {
            return 'already_active';
        }

        if (! empty($plugin->disabled)) {
            return 'disabled';
        }

        $isPubvana      = in_array($plugin->author ?? '', ['pubvana', 'pubvana_team'], true);
        $isBundled      = ! empty($plugin->bundled);
        $isFree         = ! empty($plugin->free);
        $hasLicenseUrls = ! empty($plugin->license_validate_url) || ! empty($plugin->item_url);

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
            // Bundled Pubvana — skip license check
        } elseif ($isPubvana) {
            $license = $plugin->store_product_id
                ? (new MarketplaceLicenseModel())->where('store_product_id', $plugin->store_product_id)->first()
                : null;
            if (! $license || (int) ($license->license_valid ?? -1) !== 1) {
                return 'invalid_license';
            }
        } elseif ($isFree) {
            // Third party free — skip license check
        } elseif ($hasLicenseUrls) {
            $license = $plugin->store_product_id
                ? (new MarketplaceLicenseModel())->where('store_product_id', $plugin->store_product_id)->first()
                : null;
            if (! $license || (int) ($license->license_valid ?? -1) !== 1) {
                return 'invalid_license';
            }
        } else {
            return 'tampered_no_urls';
        }

        // If not Pubvana-approved, require explicit confirmation
        if (! $force && (int) $plugin->pv_safe !== 1) {
            return 'requires_confirmation';
        }

        // Register namespace so migrations and Installer can be resolved
        $namespace = 'Plugins\\' . $folder;
        service('autoloader')->addNamespace($namespace, PLUGINS_PATH . $folder . '/');

        // Run any pending migrations for the plugin's namespace
        try {
            $migrate = \Config\Services::migrations();
            $migrate->setNamespace($namespace)->latest();
        } catch (\Throwable $e) {
            log_message('error', "PluginManager: migration failed for {$namespace} — {$e->getMessage()}");
            return 'migration_failed';
        }

        // Run seeders if the plugin ships any
        $seedsPath = PLUGINS_PATH . $folder . '/Database/Seeds/';
        if (is_dir($seedsPath)) {
            $seeder = new \CodeIgniter\Database\Seeder(config('Database'));
            foreach (glob($seedsPath . '*.php') as $seedFile) {
                $seedClass = $namespace . '\\Database\\Seeds\\' . pathinfo($seedFile, PATHINFO_FILENAME);
                try {
                    $seeder->call($seedClass);
                } catch (\Throwable $e) {
                    log_message('error', "PluginManager: seeder failed for {$seedClass} — {$e->getMessage()}");
                    return 'seed_failed';
                }
            }
        }

        // Run Installer::up() if the plugin ships one
        $installerPath = PLUGINS_PATH . $folder . '/Installer.php';
        if (is_file($installerPath)) {

            $installerClass = 'Plugins\\' . $folder . '\\Installer';
            try {
                $installer = new $installerClass();
                $installer->up();
            } catch (\Throwable $e) {
                // Roll back — call down() then fail activation
                try {
                    $installer->down();
                } catch (\Throwable $rollbackError) {
                    log_message('error', "PluginManager: installer rollback failed for {$folder} — {$rollbackError->getMessage()}");
                }
                log_message('error', "PluginManager: installer failed for {$folder} — {$e->getMessage()}");
                return 'install_failed';
            }
        }

        $model->update($plugin->id, ['is_active' => 1]);

        // If this plugin declares 'core' email capability, check whether it's the
        // first such plugin. If so, prompt the admin to choose the email provider.
        if ($this->pluginDeclaresCorEmail($plugin)) {
            $existingCoreEmailPlugins = $model
                ->where('is_active', 1)
                ->where('folder !=', $folder)
                ->findAll();

            $hasOtherCoreEmailPlugin = false;
            foreach ($existingCoreEmailPlugins as $other) {
                if ($this->pluginDeclaresCorEmail($other)) {
                    $hasOtherCoreEmailPlugin = true;
                    break;
                }
            }

            if (! $hasOtherCoreEmailPlugin) {
                return 'needs_email_provider';
            }
        }

        return 'activated';
    }

    /**
     * Check whether a plugin row declares 'core' in its email capabilities.
     */
    private function pluginDeclaresCorEmail(object $plugin): bool
    {
        if (empty($plugin->capabilities)) {
            return false;
        }

        $caps = json_decode($plugin->capabilities, true);

        return is_array($caps['email'] ?? null)
            && in_array('core', $caps['email'], true);
    }

    /**
     * Deactivate a plugin by folder name.
     */
    public function deactivate(string $folder): bool
    {
        $model  = model(PluginModel::class);
        $plugin = $model->where('folder', $folder)->first();

        if (! $plugin) {
            return false;
        }

        return $model->update($plugin->id, ['is_active' => 0]);
    }

    /**
     * Get Config/Routes.php file paths for all loaded (active) plugins.
     *
     * @return string[]
     */
    public function getRouteFiles(): array
    {
        $files = [];

        foreach ($this->pluginPaths as $basePath) {
            $routeFile = $basePath . 'Config/Routes.php';
            if (is_file($routeFile)) {
                $files[] = $routeFile;
            }
        }

        return $files;
    }

    /**
     * Admin sidebar sections from all loaded plugins.
     *
     * Each plugin returns one top-level section with children.
     * Plugins that return an empty array are skipped.
     *
     * @return array[] Array of section arrays, each with label, icon, children.
     */
    public function getMenuItems(): array
    {
        $sections = [];

        foreach ($this->plugins as $plugin) {
            $menu = $plugin->getMenuItems();
            if (! empty($menu)) {
                $sections[] = $menu;
            }
        }

        return $sections;
    }

    /**
     * Public-facing routes from all loaded plugins.
     *
     * @return array<array{label: string, url: string}>
     */
    public function getPublicRoutes(): array
    {
        $routes = [];

        foreach ($this->plugins as $plugin) {
            $pluginRoutes = $plugin->getPublicRoutes();
            if (! empty($pluginRoutes)) {
                $routes = array_merge($routes, $pluginRoutes);
            }
        }

        return $routes;
    }

    /**
     * All loaded plugin instances, keyed by slug.
     *
     * @return PluginInterface[]
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Run pending migrations for all active plugins.
     * Safe to call on every admin request — the migration runner
     * skips anything already in the migrations table.
     */
    public function runPendingMigrations(): void
    {
        try {
            $activePlugins = model(PluginModel::class)
                ->where('is_active', 1)
                ->where('disabled IS NULL')
                ->findAll();
        } catch (\Throwable $e) {
            return;
        }

        $migrate = \Config\Services::migrations();

        foreach ($activePlugins as $row) {
            $namespace = 'Plugins\\' . $row->folder;
            try {
                $migrate->setNamespace($namespace)->latest();
            } catch (\Throwable $e) {
                log_message('error', "PluginManager: migration failed for {$row->folder} — {$e->getMessage()}");
            }
        }
    }
}
