<?php

namespace App\Controllers\Admin;

use App\Services\UpdateService;
use App\Services\BackupService;
use App\Services\DownloadService;
use App\Services\ExtractService;
use App\Services\ApplyService;
use App\Services\ProgressReporter;
use App\Services\ActivityLogger;
use App\Services\WidgetService;
use App\Services\ThemeService;
use App\Services\PluginManager;

class Updates extends BaseAdminController
{
    protected UpdateService $updateService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        if (! auth()->user()->can('admin.settings')) {
            redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'))->send();
            exit;
        }

        $this->updateService = new UpdateService();
    }

    public function index(): string
    {
        // Sync extension registries so the DB matches the filesystem
        (new WidgetService())->sync();
        (new ThemeService())->sync();
        PluginManager::instance()->discover();

        // Always fetch fresh on the updates page
        $this->updateService->clearCache();
        $update  = $this->updateService->checkForUpdate();
        $changes = [];
        $checks  = [];

        $effectiveTarget = $update['safe_target'] ?? $update['latest_version'];
        $incompatible    = $update['capped_by'] ?? [];

        if (! empty($update['available'])) {
            $changes = $this->updateService->getChanges(APP_VERSION, $effectiveTarget, $update['versions_data'] ?? []);
            $checks  = $this->updateService->preFlightChecks($changes, $effectiveTarget, $incompatible);
        }

        // Collect breaking changes from all applicable versions
        $breakingChanges = [];
        $migrationNotes  = [];
        $notices         = [];
        foreach ($changes as $entry) {
            foreach ($entry['breaking_changes'] ?? [] as $bc) $breakingChanges[] = $bc;
            foreach ($entry['migration_notes'] ?? [] as $mn)  $migrationNotes[]  = $mn;
            foreach ($entry['notices'] ?? [] as $n)            $notices[]         = $n;
        }

        $allHardPass = empty(array_filter($checks, fn($c) => $c['hard'] && ! $c['pass']));
        $download    = new DownloadService();

        $db = db_connect();
        $themes  = $db->table('themes')->orderBy('name')->get()->getResultObject();
        $widgets = $db->table('widgets')->orderBy('name')->get()->getResultObject();
        $plugins = $db->table('plugins')->orderBy('name')->get()->getResultObject();

        // Read update_url and support_url from local info files for each extension
        $extensionMeta = [];
        foreach ($themes as $t) {
            $infoFile = THEMES_PATH . $t->folder . '/theme_info.json';
            $info = is_file($infoFile) ? json_decode(file_get_contents($infoFile), true) : [];
            $extensionMeta[$t->folder] = [
                'update_url'  => $info['update_url'] ?? null,
                'support_url' => $info['support_url'] ?? $info['author_url'] ?? null,
                'bundled'     => ! empty($info['bundled']),
            ];
        }
        foreach ($widgets as $w) {
            $infoFile = WIDGETS_PATH . $w->folder . '/widget_info.json';
            $info = is_file($infoFile) ? json_decode(file_get_contents($infoFile), true) : [];
            $extensionMeta[$w->folder] = [
                'update_url'  => $info['update_url'] ?? null,
                'support_url' => $info['support_url'] ?? $info['author_url'] ?? null,
                'bundled'     => ! empty($info['bundled']),
            ];
        }
        foreach ($plugins as $p) {
            $infoFile = PLUGINS_PATH . $p->folder . '/plugin_info.json';
            $info = is_file($infoFile) ? json_decode(file_get_contents($infoFile), true) : [];
            $extensionMeta[$p->folder] = [
                'update_url'  => $info['update_url'] ?? null,
                'support_url' => $info['support_url'] ?? $info['author_url'] ?? null,
                'bundled'     => ! empty($info['bundled']),
            ];
        }

        $cappedByExt = $update['safe_target'] !== null
            && $update['safe_target'] !== $update['latest_version'];

        return $this->adminView('updates/index', array_merge(
            $this->baseData('Updates', 'updates'),
            [
                'update'              => $update,
                'effective_target'    => $effectiveTarget,
                'capped_by_extensions'=> $cappedByExt,
                'checks'              => $checks,
                'all_hard_pass'       => $allHardPass,
                'breaking_changes'    => $breakingChanges,
                'migration_notes'     => $migrationNotes,
                'notices'             => $notices,
                'can_download'        => $download->canDownload(),
                'is_locked'           => ProgressReporter::isLocked(),
                'incompatible'        => $incompatible,
                'ext_themes'          => $themes,
                'ext_widgets'         => $widgets,
                'ext_plugins'         => $plugins,
                'ext_meta'            => $extensionMeta,
                'auto_update'         => (bool) setting('App.autoUpdate'),
                'check_method'        => setting('App.updateCheckMethod') ?? 'pageload',
            ]
        ));
    }

    public function check()
    {
        $this->updateService->clearCache();
        return redirect()->to(base_url('admin/updates'))->with('success', lang('Admin.updatesCacheCleared'));
    }

    public function apply()
    {
        if (ProgressReporter::isLocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Another operation is already in progress.']);
        }

        // Pre-check: is there actually an update available?
        $update = $this->updateService->checkForUpdate();
        if (empty($update['available'])) {
            return $this->response->setJSON(['status' => 'completed', 'message' => 'Already up to date.']);
        }

        $email = auth()->user()->email;

        @set_time_limit(300);
        $reporter = new ProgressReporter('update');
        if (! $reporter->acquireLock()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Another operation is already in progress.']);
        }

        try {
            $update = $this->updateService->checkForUpdate();
            if (empty($update['available'])) {
                $reporter->releaseLock();
                return $this->response->setJSON(['status' => 'completed', 'message' => 'Already up to date.']);
            }

            // Step 1: Backup
            $reporter->update(1, 6, 'Creating backup...');
            $backupService = new BackupService();
            $backupService->createBackup('pre-update', $email);

            // Step 2: Download
            $reporter->update(2, 6, 'Downloading update...');
            $downloadService = new DownloadService();
            $zipPath = WRITEPATH . 'updates/pubvana-' . $update['latest_version'] . '.zip';
            $zipUrl  = $update['zipball_url'];
            if (! $downloadService->download($zipUrl, $zipPath)) {
                throw new \RuntimeException('Failed to download update.');
            }

            // Step 3: Extract
            $reporter->update(3, 6, 'Extracting files...');
            $extractService = new ExtractService();
            $extractDir = WRITEPATH . 'updates/pubvana-' . $update['latest_version'] . '/';
            if (! $extractService->extract($zipPath, $extractDir)) {
                throw new \RuntimeException('Failed to extract update.');
            }
            $innerDir = $extractService->detectInnerDir($extractDir);

            // Step 4: Apply
            $reporter->update(4, 6, 'Applying update...');
            $applyService = new ApplyService();
            $applyService->applyFiles($innerDir);

            // Step 5: Migrations
            $reporter->update(5, 6, 'Running migrations...');
            $applyService->runMigrations();

            // Step 6: Cleanup
            $reporter->update(6, 6, 'Cleaning up...');
            @unlink($zipPath);
            $this->removeDirectory($extractDir);

            $reporter->complete(['version' => $update['latest_version']]);
            $reporter->releaseLock();

            cache()->clean();

            // Sync extension registries so new/updated extensions from the release are registered
            (new WidgetService())->sync();
            (new ThemeService())->sync();
            PluginManager::instance()->discover();

            // Trigger extension update check + auto-updates after CMS version change
            try {
                $extService = new \App\Services\ExtensionUpdateService();
                $extService->clearCache();
                $extResults = $extService->checkAllAddons();
                $extService->runAutoUpdates($extResults);
            } catch (\Throwable $e) {
                log_message('error', 'Post-CMS-update extension check failed: ' . $e->getMessage());
            }

            ActivityLogger::log('settings.updated', 'setting', null, 'Updated Pubvana to v' . $update['latest_version']);

            return $this->response->setJSON(['status' => 'completed', 'message' => 'Updated to Pubvana v' . $update['latest_version']]);
        } catch (\Throwable $e) {
            $reporter->error('update', $e->getMessage());
            $reporter->releaseLock();
            return $this->response->setJSON(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    public function stream()
    {
        $reporter = new ProgressReporter('update');

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        ob_end_clean();

        $lastData = '';
        $timeout  = time() + 300;

        while (time() < $timeout) {
            $data = $reporter->read();
            $json = json_encode($data ?? ['status' => 'waiting']);

            if ($json !== $lastData) {
                echo "event: progress\ndata: {$json}\n\n";
                $lastData = $json;

                if ($data && in_array($data['status'] ?? '', ['completed', 'error'])) {
                    echo "event: {$data['status']}\ndata: {$json}\n\n";
                    break;
                }
            }

            if (connection_aborted()) break;
            ob_flush();
            flush();
            usleep(500000);
        }
        exit;
    }

    public function status()
    {
        $reporter = new ProgressReporter('update');
        return $this->response->setJSON($reporter->read() ?? ['status' => 'idle']);
    }

    // ---------------------------------------------------------------
    // Extension addon update endpoints (AJAX, JSON responses)
    // ---------------------------------------------------------------

    public function checkAllAddons()
    {
        $service = new \App\Services\ExtensionUpdateService();
        $service->clearCache();
        $results = $service->checkAllAddons();

        return $this->response->setJSON(['status' => 'ok', 'results' => $results]);
    }

    public function checkAddon()
    {
        $type = $this->request->getPost('type');
        $slug = $this->request->getPost('slug');

        if (! $type || ! $slug) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'type and slug required.']);
        }

        $service = new \App\Services\ExtensionUpdateService();
        $results = $service->checkSingleAddon($type, $slug);

        return $this->response->setJSON(['status' => 'ok', 'results' => $results]);
    }

    public function updateAddon()
    {
        $type = $this->request->getPost('type');
        $slug = $this->request->getPost('slug');

        if (! $type || ! $slug) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'type and slug required.']);
        }

        $service = new \App\Services\ExtensionUpdateService();
        $ok = $service->updateAddon($type, $slug);

        // Read current state from DB for the UI
        $table = match ($type) {
            'theme' => 'themes', 'widget' => 'widgets', 'plugin' => 'plugins', default => null
        };
        $row = $table ? db_connect()->table($table)->where('folder', $slug)->get()->getRowObject() : null;

        return $this->response->setJSON([
            'status'  => $ok ? 'ok' : 'error',
            'message' => $ok ? 'Updated successfully.' : ($row->last_update_error ?? 'Update failed.'),
            'row'     => $row,
        ]);
    }

    public function updateAllAddons()
    {
        $service = new \App\Services\ExtensionUpdateService();
        $service->clearCache();
        $checkResults = $service->checkAllAddons();

        $results = [];
        $db = db_connect();
        foreach ($checkResults['updates'] ?? [] as $upd) {
            $table = match ($upd['type'] ?? '') {
                'theme' => 'themes', 'widget' => 'widgets', 'plugin' => 'plugins', default => null
            };
            if (! $table) continue;

            $row = $db->table($table)->where('store_product_id', $upd['product_id'])->get()->getRowObject();
            if (! $row) continue;

            $ok = $service->updateAddon($upd['type'], $row->folder);
            $results[] = [
                'folder' => $row->folder,
                'type'   => $upd['type'],
                'status' => $ok ? 'success' : 'fail',
            ];
        }

        return $this->response->setJSON(['status' => 'ok', 'results' => $results]);
    }

    public function saveUpdateSettings()
    {
        $autoUpdate      = (bool) $this->request->getPost('auto_update');
        $checkMethod     = $this->request->getPost('check_method');

        if (! in_array($checkMethod, ['pageload', 'cron'], true)) {
            $checkMethod = 'pageload';
        }

        setting()->set('App.autoUpdate', $autoUpdate);
        setting()->set('App.updateCheckMethod', $checkMethod);

        // Clear the chain cache so the new settings take effect on the next check
        $this->updateService->clearChainCache();

        return $this->response->setJSON([
            'status'  => 'ok',
            'message' => lang('Admin.updatesSettingsSaved'),
        ]);
    }

    public function toggleAutoUpdate()
    {
        $type    = $this->request->getPost('type');
        $slug    = $this->request->getPost('slug');
        $enabled = (int) $this->request->getPost('enabled');

        if (! $type || ! $slug) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'type and slug required.']);
        }

        $table = match ($type) {
            'theme' => 'themes', 'widget' => 'widgets', 'plugin' => 'plugins', default => null
        };

        if (! $table) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid type.']);
        }

        db_connect()->table($table)->where('folder', $slug)->update([
            'auto_update' => $enabled ? 1 : 0,
        ]);

        return $this->response->setJSON(['status' => 'ok']);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) return;
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . $item;
            is_dir($path) ? $this->removeDirectory($path . '/') : @unlink($path);
        }
        @rmdir($dir);
    }
}
