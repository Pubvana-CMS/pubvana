<?php

namespace App\Services;

class RollbackService
{
    protected BackupService  $backupService;
    protected ExtractService $extractService;
    protected ApplyService   $applyService;

    protected array $restoreDirs = ['app', 'public', 'vendor', 'themes', 'widgets', 'plugins'];

    protected array $protectedConfigs = [
        '.env',
        'app/Config/App.php',
        'app/Config/Database.php',
    ];

    public function __construct()
    {
        $this->backupService  = new BackupService();
        $this->extractService = new ExtractService();
        $this->applyService   = new ApplyService();
    }

    /**
     * Perform a full rollback from a backup file.
     *
     * Flow:
     *   1. Backup current state (trigger: pre-rollback)
     *   2. Restore files + DB from selected backup
     *   3. Backup restored state (trigger: post-rollback)
     *
     * @param string $backupFilename  Basename of the backup zip
     * @param string $triggeredBy     Email of the admin
     * @param callable|null $onProgress  fn(int $step, int $total, string $label, string $detail)
     */
    public function rollback(string $backupFilename, string $triggeredBy, ?callable $onProgress = null): void
    {
        $backupPath = $this->backupService->getBackupPath($backupFilename);
        if (! $backupPath) {
            throw new \RuntimeException('Backup not found: ' . $backupFilename);
        }

        $totalSteps = 5; // pre-backup, extract, restore files, restore db, post-backup
        $step = 0;

        // Step 1: Backup current state
        $step++;
        if ($onProgress) $onProgress($step, $totalSteps, 'Backing up current state...', 'pre-rollback');
        $this->backupService->createBackup('pre-rollback', $triggeredBy);

        // Step 2: Extract the backup zip
        $step++;
        if ($onProgress) $onProgress($step, $totalSteps, 'Extracting backup...', $backupFilename);
        $extractDir = WRITEPATH . 'updates/rollback_' . time() . '/';
        if (! $this->extractService->extract($backupPath, $extractDir)) {
            throw new \RuntimeException('Failed to extract backup zip.');
        }

        try {
            // Step 3: Restore files
            $step++;
            if ($onProgress) $onProgress($step, $totalSteps, 'Restoring files...', '');
            $this->restoreFiles($extractDir);

            // Step 4: Restore database
            $step++;
            if ($onProgress) $onProgress($step, $totalSteps, 'Restoring database...', '');
            $sqlGzPath = $extractDir . 'database.sql.gz';
            if (is_file($sqlGzPath)) {
                $sqlGzData = file_get_contents($sqlGzPath);
                $this->backupService->restoreDatabase($sqlGzData);
            }

            // Step 5: Backup restored state
            $step++;
            if ($onProgress) $onProgress($step, $totalSteps, 'Backing up restored state...', 'post-rollback');
            $this->backupService->createBackup('post-rollback', $triggeredBy);

        } finally {
            // Cleanup
            $this->removeDirectory($extractDir);
        }

        // Clear cache
        cache()->clean();
    }

    /**
     * Restore directories from the extracted backup, preserving protected configs.
     */
    private function restoreFiles(string $extractDir): void
    {
        foreach ($this->restoreDirs as $dir) {
            $src  = $extractDir . $dir . '/';
            $dest = ROOTPATH . $dir . '/';

            if (! is_dir($src)) {
                continue;
            }

            $this->copyDirectory($src, $dest);
        }
    }

    private function copyDirectory(string $src, string $dest): void
    {
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $items = array_diff(scandir($src), ['.', '..']);
        foreach ($items as $item) {
            $srcPath  = $src . $item;
            $destPath = $dest . $item;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath . '/', $destPath . '/');
            } else {
                $relPath = str_replace(ROOTPATH, '', $destPath);
                $normalized = str_replace('\\', '/', $relPath);
                $skip = false;
                foreach ($this->protectedConfigs as $protected) {
                    if ($normalized === $protected || str_ends_with($normalized, '/' . $protected)) {
                        $skip = true;
                        break;
                    }
                }
                if (! $skip) {
                    copy($srcPath, $destPath);
                }
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . $item;
            is_dir($path) ? $this->removeDirectory($path . '/') : @unlink($path);
        }
        @rmdir($dir);
    }
}
