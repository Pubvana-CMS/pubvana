<?php

namespace App\Services;

class ApplyService
{
    /** Config files that must never be overwritten. */
    protected array $protectedConfigs = [
        '.env',
        'app/Config/App.php',
        'app/Config/Database.php',
    ];

    /** Directories to copy from the release. */
    protected array $copyDirs = ['app', 'public', 'vendor', 'themes', 'widgets', 'plugins'];

    /**
     * Apply the extracted release files to the live site.
     *
     * @param string $sourceDir  Path to extracted release root (with trailing slash)
     * @param callable|null $onProgress  fn(string $label, string $detail)
     */
    public function applyFiles(string $sourceDir, ?callable $onProgress = null): void
    {
        foreach ($this->copyDirs as $dir) {
            $src  = $sourceDir . $dir . '/';
            $dest = ROOTPATH . $dir . '/';

            if (! is_dir($src)) {
                continue;
            }

            if ($onProgress) {
                $onProgress("Applying {$dir}/", '');
            }

            $this->copyDirectory($src, $dest);
        }

        // Also copy root-level files (spark, CHANGES.json, etc.) — but NOT .env
        $rootFiles = array_diff(scandir($sourceDir), ['.', '..']);
        foreach ($rootFiles as $item) {
            $srcPath  = $sourceDir . $item;
            $destPath = ROOTPATH . $item;

            if (is_dir($srcPath)) {
                continue; // Dirs handled above
            }

            if ($this->isProtected($item)) {
                continue;
            }

            copy($srcPath, $destPath);
        }
    }

    /**
     * Run database migrations.
     *
     * Fallback chain:
     *   1. In-process: service('migrations')->latest()
     *   2. exec('php spark migrate --all')
     *   3. Returns false — caller should display manual instructions
     */
    public function runMigrations(): bool
    {
        // Method 1: in-process
        try {
            $migrate = \Config\Services::migrations();
            $migrate->latest();
            return true;
        } catch (\Throwable $e) {
            log_message('warning', 'ApplyService: in-process migration failed: ' . $e->getMessage());
        }

        // Method 2: exec
        if ($this->execAvailable()) {
            $cmd = sprintf('php %s migrate --all 2>&1', escapeshellarg(ROOTPATH . 'spark'));
            exec($cmd, $output, $code);
            if ($code === 0) {
                return true;
            }
            log_message('warning', 'ApplyService: exec migration failed: ' . implode("\n", $output));
        }

        return false;
    }

    /**
     * Check if a path is a protected config file.
     */
    private function isProtected(string $relativePath): bool
    {
        $normalized = str_replace('\\', '/', $relativePath);
        foreach ($this->protectedConfigs as $protected) {
            if ($normalized === $protected || str_ends_with($normalized, '/' . $protected)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Recursively copy a directory, skipping protected config files.
     */
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
                if ($this->isProtected($relPath)) {
                    continue;
                }
                copy($srcPath, $destPath);
            }
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
