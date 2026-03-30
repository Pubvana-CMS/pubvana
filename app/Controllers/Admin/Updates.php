<?php

namespace App\Controllers\Admin;

use App\Services\UpdateService;
use App\Services\BackupService;
use App\Services\DownloadService;
use App\Services\ExtractService;
use App\Services\ApplyService;
use App\Services\ProgressReporter;
use App\Services\ActivityLogger;

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
        $update  = $this->updateService->checkForUpdate();
        $changes = [];
        $checks  = [];

        if (! empty($update['available'])) {
            $changes = $this->updateService->getChanges(APP_VERSION, $update['latest_version']);
            $checks  = $this->updateService->preFlightChecks($changes);
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

        return $this->adminView('updates/index', array_merge(
            $this->baseData('Updates', 'updates'),
            [
                'update'          => $update,
                'checks'          => $checks,
                'all_hard_pass'   => $allHardPass,
                'breaking_changes'=> $breakingChanges,
                'migration_notes' => $migrationNotes,
                'notices'         => $notices,
                'can_download'    => $download->canDownload(),
                'is_locked'       => ProgressReporter::isLocked(),
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

        $email = auth()->user()->email;

        // Try queue first
        if ($this->queueAvailable()) {
            service('queue')->push('pubvana', 'update', ['email' => $email]);
            return $this->response->setJSON(['status' => 'started', 'method' => 'queue']);
        }

        // Try exec (background)
        if ($this->execAvailable()) {
            $cmd = sprintf(
                'php %s pubvana:update --email %s > /dev/null 2>&1 &',
                escapeshellarg(ROOTPATH . 'spark'),
                escapeshellarg($email)
            );
            exec($cmd);
            return $this->response->setJSON(['status' => 'started', 'method' => 'exec']);
        }

        // Synchronous fallback
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

    /**
     * Check if the CI4 queue is available and has a running worker.
     */
    private function queueAvailable(): bool
    {
        if (! class_exists(\CodeIgniter\Queue\Queue::class)) {
            return false;
        }
        // Check if queue table exists
        try {
            $db = db_connect();
            return $db->tableExists('queue_jobs');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function execAvailable(): bool
    {
        if (! function_exists('exec')) return false;
        $disabled = ini_get('disable_functions') ?: '';
        return ! in_array('exec', array_map('trim', explode(',', $disabled)), true);
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
