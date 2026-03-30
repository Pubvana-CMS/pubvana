<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use App\Services\UpdateService;
use App\Services\BackupService;
use App\Services\DownloadService;
use App\Services\ExtractService;
use App\Services\ApplyService;
use App\Services\ProgressReporter;

class UpdateJob extends BaseJob
{
    public function process()
    {
        $email = $this->data['email'] ?? 'cli';

        $reporter = new ProgressReporter('update');

        if (! $reporter->acquireLock()) {
            throw new \RuntimeException('Another operation is already in progress.');
        }

        try {
            $updateService = new UpdateService();
            $update = $updateService->checkForUpdate();

            if (! $update['available']) {
                $reporter->complete(['message' => 'Already up to date.']);
                return;
            }

            // Backup
            $reporter->update(1, 6, 'Creating backup...');
            (new BackupService())->createBackup('pre-update', $email);

            // Download
            $reporter->update(2, 6, 'Downloading update...');
            $downloadService = new DownloadService();
            $zipPath = WRITEPATH . 'updates/pubvana-' . $update['latest_version'] . '.zip';
            if (! $downloadService->download($update['zipball_url'], $zipPath)) {
                throw new \RuntimeException('Failed to download update.');
            }

            // Extract
            $reporter->update(3, 6, 'Extracting files...');
            $extractService = new ExtractService();
            $extractDir = WRITEPATH . 'updates/pubvana-' . $update['latest_version'] . '/';
            if (! $extractService->extract($zipPath, $extractDir)) {
                throw new \RuntimeException('Failed to extract update.');
            }
            $innerDir = $extractService->detectInnerDir($extractDir);

            // Apply
            $reporter->update(4, 6, 'Applying update...');
            $applyService = new ApplyService();
            $applyService->applyFiles($innerDir);

            // Migrations
            $reporter->update(5, 6, 'Running migrations...');
            $applyService->runMigrations();

            // Cleanup
            $reporter->update(6, 6, 'Cleaning up...');
            @unlink($zipPath);
            $this->removeDir($extractDir);
            cache()->clean();

            $reporter->complete(['version' => $update['latest_version']]);
        } catch (\Throwable $e) {
            $reporter->error('update', $e->getMessage());
            throw $e;
        } finally {
            $reporter->releaseLock();
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) return;
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . $item;
            is_dir($path) ? $this->removeDir($path . '/') : @unlink($path);
        }
        @rmdir($dir);
    }
}
