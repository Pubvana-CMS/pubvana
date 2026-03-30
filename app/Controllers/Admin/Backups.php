<?php

namespace App\Controllers\Admin;

use App\Services\ActivityLogger;
use App\Services\BackupService;
use App\Services\ProgressReporter;

class Backups extends BaseAdminController
{
    protected BackupService $backupService;

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

        $this->backupService = new BackupService();
    }

    public function index(): string
    {
        return $this->adminView('backups/index', array_merge(
            $this->baseData('Backups', 'backups'),
            [
                'backups'   => $this->backupService->listBackups(),
                'is_locked' => ProgressReporter::isLocked(),
            ]
        ));
    }

    public function create()
    {
        if (ProgressReporter::isLocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Another operation is already in progress.']);
        }

        $email = auth()->user()->email;

        // Try queue first
        if ($this->queueAvailable()) {
            service('queue')->push('pubvana', 'backup', ['trigger' => 'manual', 'email' => $email]);
            return $this->response->setJSON(['status' => 'started', 'method' => 'queue']);
        }

        // Try exec (background process)
        if ($this->execAvailable()) {
            $cmd = sprintf(
                'php %s pubvana:backup --trigger manual --email %s > /dev/null 2>&1 &',
                escapeshellarg(ROOTPATH . 'spark'),
                escapeshellarg($email)
            );
            exec($cmd);
            return $this->response->setJSON(['status' => 'started', 'method' => 'exec']);
        }

        // Synchronous fallback — run inline and return JSON when done
        @set_time_limit(300);
        try {
            $reporter = new ProgressReporter('backup');
            if (! $reporter->acquireLock()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Another operation is already in progress.']);
            }

            $zipPath = $this->backupService->createBackup('manual', $email, function (int $step, int $total, string $label) use ($reporter) {
                $reporter->update($step, $total, $label);
            });

            $reporter->complete(['detail' => basename($zipPath)]);
            $reporter->releaseLock();

            ActivityLogger::log('settings.updated', 'setting', null, 'Created site backup');
            return $this->response->setJSON(['status' => 'completed', 'message' => 'Backup created: ' . basename($zipPath)]);
        } catch (\Throwable $e) {
            if (isset($reporter)) {
                $reporter->error('backup', $e->getMessage());
                $reporter->releaseLock();
            }
            log_message('error', 'Backup failed: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    public function download(string $filename)
    {
        $path = $this->backupService->getBackupPath($filename);
        if (! $path) {
            return redirect()->to('/admin/backups')->with('error', 'Backup not found.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . basename($path) . '"')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setBody(file_get_contents($path));
    }

    public function delete(string $filename)
    {
        if ($this->backupService->deleteBackup($filename)) {
            ActivityLogger::log('settings.updated', 'setting', null, 'Deleted backup: ' . $filename);
            return redirect()->to('/admin/backups')->with('success', 'Backup deleted.');
        }
        return redirect()->to('/admin/backups')->with('error', 'Could not delete backup.');
    }

    public function restore(string $filename)
    {
        if (ProgressReporter::isLocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Another operation is already in progress.']);
        }

        $path = $this->backupService->getBackupPath($filename);
        if (! $path) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Backup not found.']);
        }

        $email = auth()->user()->email;

        // Try queue first
        if ($this->queueAvailable()) {
            service('queue')->push('pubvana', 'rollback', ['filename' => $filename, 'email' => $email]);
            return $this->response->setJSON(['status' => 'started', 'method' => 'queue']);
        }

        // Try exec (background process)
        if ($this->execAvailable()) {
            $cmd = sprintf(
                'php %s pubvana:rollback %s --email %s > /dev/null 2>&1 &',
                escapeshellarg(ROOTPATH . 'spark'),
                escapeshellarg($filename),
                escapeshellarg($email)
            );
            exec($cmd);
            return $this->response->setJSON(['status' => 'started', 'method' => 'exec']);
        }

        // Synchronous fallback
        @set_time_limit(300);
        try {
            $reporter = new ProgressReporter('rollback');
            if (! $reporter->acquireLock()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Another operation is already in progress.']);
            }

            $service = new \App\Services\RollbackService();
            $service->rollback($filename, $email, function (int $step, int $total, string $label) use ($reporter) {
                $reporter->update($step, $total, $label);
            });

            $reporter->complete();
            $reporter->releaseLock();

            cache()->clean();
            ActivityLogger::log('settings.updated', 'setting', null, 'Restored from backup: ' . $filename);
            return $this->response->setJSON(['status' => 'completed', 'message' => 'Rollback complete.']);
        } catch (\Throwable $e) {
            if (isset($reporter)) {
                $reporter->error('rollback', $e->getMessage());
                $reporter->releaseLock();
            }
            return $this->response->setJSON(['status' => 'error', 'message' => 'Rollback failed: ' . $e->getMessage()]);
        }
    }

    public function stream()
    {
        $reporter = new ProgressReporter('backup');

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        ob_end_clean();

        $lastData = '';
        $timeout  = time() + 300; // 5 minute max

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

            if (connection_aborted()) {
                break;
            }

            ob_flush();
            flush();
            usleep(500000); // 500ms
        }

        exit;
    }

    public function status()
    {
        $reporter = new ProgressReporter('backup');
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
        if (! function_exists('exec')) {
            return false;
        }
        $disabled = ini_get('disable_functions') ?: '';
        return ! in_array('exec', array_map('trim', explode(',', $disabled)), true);
    }
}
