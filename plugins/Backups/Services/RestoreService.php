<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Backups\Services;

/**
 * Restores a site from a backup zip.
 *
 * Flow: backup current state -> extract zip -> restore files -> restore DB -> backup restored state.
 *
 * @package  Pubvana\Plugins\Backups
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class RestoreService
{
    protected BackupService $backupService;
    protected array $restoreDirs;
    protected array $protectedConfigs;

    public function __construct(BackupService $backupService, array $config)
    {
        $this->backupService   = $backupService;
        $this->restoreDirs     = $config['backup_dirs'] ?? ['app', 'public', 'vendor', 'themes'];
        $this->protectedConfigs = $config['protected_configs'] ?? ['app/config/services.php'];
    }

    /**
     * Perform a full restore from a backup file.
     *
     * @param string        $backupFilename Basename of the backup zip
     * @param string        $triggeredBy    Email/identifier of the admin
     * @param callable|null $onProgress     fn(int $step, int $total, string $label, string $detail)
     */
    public function restore(string $backupFilename, string $triggeredBy, ?callable $onProgress = null): void
    {
        $backupPath = $this->backupService->getBackupPath($backupFilename);
        if (!$backupPath) {
            throw new \RuntimeException('Backup not found: ' . $backupFilename);
        }

        $totalSteps = 5;
        $step = 0;

        // Step 1: Backup current state
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Backing up current state...', 'pre-rollback');
        }
        $this->backupService->createBackup('pre-rollback', $triggeredBy);

        // Step 2: Extract the backup zip
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Extracting backup...', $backupFilename);
        }
        $extractDir = $this->backupService->getBackupDir() . 'restore_' . time() . '/';
        if (!$this->extract($backupPath, $extractDir)) {
            throw new \RuntimeException('Failed to extract backup zip.');
        }

        try {
            // Step 3: Restore files
            $step++;
            if ($onProgress) {
                $onProgress($step, $totalSteps, 'Restoring files...', '');
            }
            $this->restoreFiles($extractDir);

            // Step 4: Restore database
            $step++;
            if ($onProgress) {
                $onProgress($step, $totalSteps, 'Restoring database...', '');
            }
            $sqlPath = $extractDir . 'database.sql';
            if (is_file($sqlPath)) {
                $sqlData = file_get_contents($sqlPath);
                $this->backupService->restoreDatabase($sqlData);
            }

            // Step 5: Backup restored state
            $step++;
            if ($onProgress) {
                $onProgress($step, $totalSteps, 'Backing up restored state...', 'post-rollback');
            }
            $this->backupService->createBackup('post-rollback', $triggeredBy);

        } finally {
            $this->removeDirectory($extractDir);
        }
    }

    // ------------------------------------------------------------------
    // Extraction
    // ------------------------------------------------------------------

    /**
     * Extract a zip file to a destination directory with path traversal validation.
     */
    private function extract(string $zipPath, string $destDir): bool
    {
        if (!is_file($zipPath)) {
            return false;
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!$this->validateZipContents($zipPath)) {
            return false;
        }

        // Method 1: ZipArchive
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $ok = $zip->extractTo($destDir);
                $zip->close();
                if ($ok) {
                    return true;
                }
            }
        }

        // Method 2: exec unzip
        if ($this->execAvailable()) {
            $cmd = sprintf(
                'unzip -o %s -d %s 2>/dev/null',
                escapeshellarg($zipPath),
                escapeshellarg($destDir)
            );
            exec($cmd, $output, $code);
            if ($code === 0) {
                return true;
            }
        }

        // Method 3: PharData
        if (class_exists(\PharData::class)) {
            try {
                $phar = new \PharData($zipPath);
                $phar->extractTo($destDir, null, true);
                return true;
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return false;
    }

    /**
     * Validate that no zip entries contain path traversal.
     */
    private function validateZipContents(string $zipPath): bool
    {
        if (!class_exists(\ZipArchive::class)) {
            return true;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '..')) {
                $zip->close();
                return false;
            }
        }

        $zip->close();
        return true;
    }

    // ------------------------------------------------------------------
    // File restoration
    // ------------------------------------------------------------------

    /**
     * Restore directories from the extracted backup, preserving protected configs.
     */
    private function restoreFiles(string $extractDir): void
    {
        foreach ($this->restoreDirs as $dir) {
            $src  = $extractDir . $dir . '/';
            $dest = PROJECT_ROOT . '/' . $dir . '/';

            if (!is_dir($src)) {
                continue;
            }

            $this->copyDirectory($src, $dest);
        }
    }

    private function copyDirectory(string $src, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $items = array_diff(scandir($src), ['.', '..']);
        foreach ($items as $item) {
            $srcPath  = $src . $item;
            $destPath = $dest . $item;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath . '/', $destPath . '/');
            } else {
                $relPath = str_replace(PROJECT_ROOT . '/', '', $destPath);
                $normalized = str_replace('\\', '/', $relPath);
                foreach ($this->protectedConfigs as $protected) {
                    if ($normalized === $protected || str_ends_with($normalized, '/' . $protected)) {
                        continue 2;
                    }
                }
                copy($srcPath, $destPath);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . $item;
            is_dir($path) ? $this->removeDirectory($path . '/') : @unlink($path);
        }
        @rmdir($dir);
    }

    private function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }
        $disabled = ini_get('disable_functions') ?: '';
        return !in_array('exec', array_map('trim', explode(',', $disabled)), true);
    }
}