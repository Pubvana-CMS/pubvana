<?php

namespace App\Services;

use App\Interfaces\PluginInterface;
use App\Models\PluginModel;

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
        $plugin->register();

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
            $info   = json_decode(file_get_contents($infoFile), true);

            // Reject plugins with invalid or incomplete plugin_info.json
            $required = ['name', 'slug', 'version', 'description', 'author'];
            $missing  = array_diff($required, array_keys($info ?? []));

            if (! is_array($info) || ! empty($missing)) {
                $msg = "Plugin '{$folder}' skipped — plugin_info.json missing required fields: " . implode(', ', $missing);
                log_message('warning', 'PluginManager: ' . $msg);
                $warnings[] = $msg;
                continue;
            }

            $existing = $model->where('folder', $folder)->first();

            if ($existing) {
                $newVersion = $info['version'];
                if ($newVersion !== $existing->version) {
                    // Version changed — update and reset approval (new code needs re-vetting)
                    $model->update($existing->id, [
                        'version'     => $newVersion,
                        'name'        => $info['name'],
                        'description' => $info['description'],
                        'pv_approved' => null,
                    ]);
                } elseif (($info['name'] ?? '') !== $existing->name || ($info['description']) !== $existing->description) {
                    $model->update($existing->id, [
                        'name'        => $info['name'],
                        'description' => $info['description'],
                    ]);
                }
                continue;
            }

            // New plugin — insert as inactive, pv_approved NULL triggers API check
            $model->insert([
                'folder'      => $folder,
                'name'        => $info['name'],
                'slug'        => $info['slug'],
                'version'     => $info['version'],
                'description' => $info['description'],
                'is_active'   => 0,
                'pv_approved' => null,
            ]);

            $discovered[] = $folder;
        }

        // Poll pubvana.net for approval status of unchecked plugins (only if there are any)
        if (! empty($discovered)) {
            $this->checkApproval();
        }

        return ['discovered' => $discovered, 'warnings' => $warnings];
    }

    /**
     * Check approval status for plugins with pv_approved IS NULL.
     *
     * Calls the pubvana.net store API with unchecked slugs.
     * Sets pv_approved to 1 (approved) or 0 (not approved).
     * Leaves NULL if the API is unreachable (retries on next discover).
     */
    public function checkApproval(): void
    {
        $model     = model(PluginModel::class);
        $unchecked = $model->where('pv_approved', null)->findAll();

        if (empty($unchecked)) {
            return;
        }

        $slugs = array_map(static fn ($p) => $p->slug, $unchecked);

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            // TODO: Update route once vetting plugin slug is finalized
            $response = $client->post(PUBVANA_API_BASE . 'vetted/v1/plugins/approved', [
                'json'        => ['slugs' => $slugs],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return;
            }

            $body = json_decode($response->getBody(), true);

            if (! is_array($body) || ! isset($body['approved'])) {
                return;
            }

            $approved = $body['approved'] ?? [];  // array of approved slugs
            $warnings = $body['warnings'] ?? [];  // slug => warning note

            foreach ($unchecked as $plugin) {
                $isApproved = in_array($plugin->slug, $approved, true) ? 1 : 0;
                $model->update($plugin->id, [
                    'pv_approved'     => $isApproved,
                    'pv_warning_note' => $warnings[$plugin->slug] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            // API unreachable — leave as NULL, retry on next discover
            log_message('debug', 'PluginManager::checkApproval unreachable: ' . $e->getMessage());
        }
    }

    /**
     * Activate a plugin by folder name.
     *
     * Returns a status string:
     *   'activated'              — plugin is now active
     *   'not_found'             — no such plugin in the DB
     *   'requires_confirmation' — pv_approved is not 1, caller must confirm
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

        // If not Pubvana-approved, require explicit confirmation
        if (! $force && (int) $plugin->pv_approved !== 1) {
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
        return 'activated';
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
}
