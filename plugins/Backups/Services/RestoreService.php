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

    /** @var list<string> Top-level directories restored from the archive */
    protected array $restoreDirs;

    /** @var list<string> Config files whose credentials are never overwritten */
    protected array $protectedConfigs;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(BackupService $backupService, array $config)
    {
        $this->backupService   = $backupService;
        $this->restoreDirs     = $config['backup_dirs'] ?? ['app', 'plugins', 'public', 'vendor', 'themes'];
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

        // The caller holds the operation lock, so any extract dir on disk is
        // left over from a run that died before its finally block ran.
        $this->cleanStaleExtractDirs();

        $totalSteps = 5;
        $step = 0;

        // Step 1: Backup current state
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Backing up current state...', 'pre-rollback');
        }
        $this->backupService->createBackup('pre-rollback', $triggeredBy, null, $backupPath);

        // Step 2: Extract the backup zip
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Extracting backup...', $backupFilename);
        }
        $extractDir = $this->backupService->getBackupDir() . 'restore_' . time() . '/';

        // The extraction directory is always removed, including when the
        // archive is rejected before a single entry is written.
        try {
            if (!$this->extract($backupPath, $extractDir)) {
                throw new \RuntimeException('Failed to extract backup zip.');
            }

            // Fail before the first file is written rather than partway
            // through and leaving a half-restored tree.
            $this->assertTargetsWritable($extractDir);

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
            if (!is_file($sqlPath)) {
                throw new \RuntimeException('The backup archive has no database.sql.');
            }
            $sqlData = file_get_contents($sqlPath);
            if ($sqlData === false) {
                throw new \RuntimeException('Unable to read database.sql from the archive.');
            }
            $this->backupService->restoreDatabase($sqlData);

            // Step 5: Backup restored state
            $step++;
            if ($onProgress) {
                $onProgress($step, $totalSteps, 'Backing up restored state...', 'post-rollback');
            }
            $this->backupService->createBackup('post-rollback', $triggeredBy, null, $backupPath);

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
     * Validate that zip entry paths are safe and carry no symlink entries.
     *
     * ZipArchive is required: it is what creates the backups in the first
     * place, and the unzip/PharData fallbacks must never run without a
     * validated entry list.
     */
    private function validateZipContents(string $zipPath): bool
    {
        if (!class_exists(\ZipArchive::class)) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name)) {
                $names[] = $name;
            }
        }

        $safe = self::zipEntriesAreSafe($names)
            && self::zipEntriesAreFreeOfSymlinks($zip);
        $zip->close();

        return $safe;
    }

    /**
     * True when every zip entry path is safe to extract.
     *
     * Rejects traversal (".."), absolute paths, drive letters, and NUL
     * bytes; backslash separators are normalized first so "\..\evil"
     * cannot sneak through.
     *
     * @param list<string> $names
     */
    public static function zipEntriesAreSafe(array $names): bool
    {
        foreach ($names as $name) {
            if ($name === '' || str_contains($name, "\0")) {
                return false;
            }

            $normalized = str_replace('\\', '/', $name);

            if (str_starts_with($normalized, '/') || preg_match('#^[a-zA-Z]:#', $normalized) === 1) {
                return false;
            }

            $segments = explode('/', $normalized);
            if (in_array('..', $segments, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * True when no zip entry carries the Unix symlink mode bit (0120000).
     *
     * Extraction restores symlinks; a crafted backup could otherwise plant
     * a link that the restore copy/cleanup recursion would follow out of
     * the site tree. Entries without Unix external attributes pass: they
     * carry no mode and therefore no symlink bit.
     */
    public static function zipEntriesAreFreeOfSymlinks(\ZipArchive $zip): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $opsys = 0;
            $attr  = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attr)
                && $opsys === \ZipArchive::OPSYS_UNIX
                && (($attr >> 16) & 0170000) === 0120000
            ) {
                return false;
            }
        }

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
        if (!is_dir($dest) && !@mkdir($dest, 0755, true) && !is_dir($dest)) {
            throw new \RuntimeException('Failed to create directory "' . $dest . '".');
        }

        $items    = array_values(array_diff(scandir($src) ?: [], ['.', '..']));
        $inSource = array_flip($items);

        // Remove live entries the snapshot does not contain, so a restore
        // returns the tree to the snapshot instead of only overlaying it.
        // Protected config files are never touched.
        foreach (array_diff(scandir($dest) ?: [], ['.', '..']) as $stale) {
            if (isset($inSource[$stale])) {
                continue;
            }
            $stalePath = $dest . $stale;
            if ($this->isProtectedConfig($stalePath)) {
                continue;
            }
            $this->removePath($stalePath);
        }

        foreach ($items as $item) {
            $srcPath  = $src . $item;
            $destPath = $dest . $item;

            // Naive copy: replicate a link, never traverse it. is_dir()
            // would follow the link and copy the target tree from outside
            // the extract dir into the live site.
            if (is_link($srcPath)) {
                $target = @readlink($srcPath);
                if (is_string($target)) {
                    @symlink($target, $destPath);
                }
                continue;
            }

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath . '/', $destPath . '/');
                continue;
            }

            if ($this->isProtectedConfig($destPath)) {
                continue;
            }

            // A failed copy (unwritable target, full disk) stops the restore.
            // Swallowing it leaves files silently un-restored.
            if (!@copy($srcPath, $destPath)) {
                throw new \RuntimeException(
                    'Failed to write "' . $destPath . '". The web server user needs write access to the site files.'
                );
            }
        }
    }

    /**
     * Fail before touching the site when a file the snapshot must overwrite
     * is not writable by the process running the restore.
     */
    private function assertTargetsWritable(string $extractDir): void
    {
        $blocked = [];

        foreach ($this->restoreDirs as $dir) {
            $srcRoot = $extractDir . $dir . '/';
            if (!is_dir($srcRoot)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($srcRoot, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    continue;
                }

                $relative = substr($item->getPathname(), strlen($srcRoot));
                if ($relative === '') {
                    continue;
                }

                $target = PROJECT_ROOT . '/' . $dir . '/' . $relative;
                if (file_exists($target) && !is_writable($target)) {
                    $blocked[] = $dir . '/' . $relative;
                }
            }
        }

        if ($blocked === []) {
            return;
        }

        $preview = implode(', ', array_slice($blocked, 0, 5));
        $more    = count($blocked) > 5 ? ' (and ' . (count($blocked) - 5) . ' more)' : '';

        throw new \RuntimeException(
            'The restore was not started: the web server user cannot write to '
            . $preview . $more
            . '. Fix the file ownership (see INSTALL.md) or run the restore from the command line.'
        );
    }

    /**
     * Remove extraction directories left by a run that died before cleanup.
     */
    private function cleanStaleExtractDirs(): void
    {
        $pattern = rtrim($this->backupService->getBackupDir(), '/') . '/restore_*';

        foreach (glob($pattern) ?: [] as $stale) {
            if (is_dir($stale)) {
                $this->removeDirectory(rtrim($stale, '/') . '/');
            }
        }
    }

    /**
     * True when a destination path is one of the protected config files.
     * Compared against the path relative to PROJECT_ROOT, so a protected
     * file is skipped wherever the restore walk reaches it.
     */
    private function isProtectedConfig(string $destPath): bool
    {
        $relative = str_replace('\\', '/', str_replace(PROJECT_ROOT . '/', '', $destPath));

        foreach ($this->protectedConfigs as $protected) {
            if ($relative === $protected || str_ends_with($relative, '/' . $protected)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a file, link, or directory without following links.
     */
    private function removePath(string $path): void
    {
        if (is_link($path)) {
            @unlink($path);
            return;
        }

        if (is_dir($path)) {
            $this->removeDirectory($path . '/');
            return;
        }

        @unlink($path);
    }

    private function removeDirectory(string $dir): void
    {
        // A top-level link is unlinked, not entered: is_dir() would follow
        // it and let a crafted link turn cleanup into a delete outside the
        // extract tree.
        if (is_link($dir)) {
            @unlink($dir);
            return;
        }
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . $item;
            is_link($path) ? @unlink($path) : (is_dir($path) ? $this->removeDirectory($path . '/') : @unlink($path));
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