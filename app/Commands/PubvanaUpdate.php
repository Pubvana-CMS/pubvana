<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\UpdateService;
use App\Services\BackupService;
use App\Services\DownloadService;
use App\Services\ExtractService;
use App\Services\ApplyService;
use App\Services\ProgressReporter;

class PubvanaUpdate extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'pubvana:update';
    protected $description = 'Download and apply the latest Pubvana CMS release from GitHub.';
    protected $usage       = 'pubvana:update [--dry-run] [--email=admin@example.com]';
    protected $options     = [
        '--dry-run' => 'Show what would be done without modifying any files.',
        '--email'   => 'Email of the admin who initiated the update (default: cli)',
    ];

    public function run(array $params): void
    {
        $dryRun = array_key_exists('dry-run', $params);
        $email  = $params['email'] ?? 'cli';

        if ($dryRun) {
            CLI::write('DRY RUN — no files will be modified.', 'yellow');
        }

        $reporter = new ProgressReporter('update');

        if (! $dryRun && ! $reporter->acquireLock()) {
            CLI::error('Another operation is already in progress.');
            return;
        }

        try {
            // 1. Check for update
            CLI::write('Checking for updates...', 'cyan');
            $reporter->update(0, 6, 'Checking for updates...');

            $updateService = new UpdateService();
            $update = $updateService->checkForUpdate();

            CLI::write('Current: ' . APP_VERSION . '  Latest: ' . $update['latest_version']);

            if (! $update['available']) {
                CLI::write('Already up to date.', 'green');
                if (! $dryRun) $reporter->releaseLock();
                return;
            }

            // Show changes
            $changes = $updateService->getChanges(APP_VERSION, $update['latest_version']);
            foreach ($changes as $entry) {
                foreach ($entry['breaking_changes'] ?? [] as $bc) {
                    CLI::write('  BREAKING: ' . $bc, 'red');
                }
            }

            if ($dryRun) {
                CLI::write('Dry run complete.', 'yellow');
                return;
            }

            // 2. Backup
            $reporter->update(1, 6, 'Creating backup...');
            CLI::write('Creating backup...', 'cyan');
            $backupService = new BackupService();
            $backupService->createBackup('pre-update', $email);

            // 3. Download
            $reporter->update(2, 6, 'Downloading update...');
            CLI::write('Downloading...', 'cyan');
            $downloadService = new DownloadService();
            $zipPath = WRITEPATH . 'updates/pubvana-' . $update['latest_version'] . '.zip';
            if (! $downloadService->download($update['zipball_url'], $zipPath)) {
                throw new \RuntimeException('Failed to download update.');
            }

            // 4. Extract
            $reporter->update(3, 6, 'Extracting...');
            CLI::write('Extracting...', 'cyan');
            $extractService = new ExtractService();
            $extractDir = WRITEPATH . 'updates/pubvana-' . $update['latest_version'] . '/';
            if (! $extractService->extract($zipPath, $extractDir)) {
                throw new \RuntimeException('Failed to extract update.');
            }
            $innerDir = $extractService->detectInnerDir($extractDir);

            // 5. Apply
            $reporter->update(4, 6, 'Applying update...');
            CLI::write('Applying files...', 'cyan');
            $applyService = new ApplyService();
            $applyService->applyFiles($innerDir);

            // 6. Migrations
            $reporter->update(5, 6, 'Running migrations...');
            CLI::write('Running migrations...', 'cyan');
            $applyService->runMigrations();

            // 7. Cleanup
            $reporter->update(6, 6, 'Cleaning up...');
            @unlink($zipPath);
            $this->removeDir($extractDir);
            cache()->clean();

            $reporter->complete(['version' => $update['latest_version']]);
            CLI::write('Updated to v' . $update['latest_version'] . '!', 'green');

        } catch (\Throwable $e) {
            $reporter->error('update', $e->getMessage());
            CLI::error('Update failed: ' . $e->getMessage());
        } finally {
            if (! $dryRun) $reporter->releaseLock();
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
