<?php

namespace App\Services;

class ExtractService
{
    /**
     * Extract a zip file to a destination directory.
     *
     * Fallback chain:
     *   1. ZipArchive::extractTo()
     *   2. exec('unzip ...')
     *   3. PharData::extractTo()
     *
     * Returns true on success, false on failure.
     */
    public function extract(string $zipPath, string $destDir): bool
    {
        if (! is_file($zipPath)) {
            return false;
        }

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Validate for path traversal before extracting
        if (! $this->validateZipContents($zipPath)) {
            return false;
        }

        // Method 1: ZipArchive
        if (class_exists(\ZipArchive::class)) {
            $result = $this->extractViaZipArchive($zipPath, $destDir);
            if ($result) {
                return true;
            }
        }

        // Method 2: exec unzip
        if ($this->execAvailable()) {
            $result = $this->extractViaExec($zipPath, $destDir);
            if ($result) {
                return true;
            }
        }

        // Method 3: PharData
        if (class_exists(\PharData::class)) {
            $result = $this->extractViaPhar($zipPath, $destDir);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect the inner wrapper directory if the zip has one (GitHub convention).
     * Returns the path to the actual content root.
     */
    public function detectInnerDir(string $extractDir): string
    {
        $entries = array_diff(scandir($extractDir), ['.', '..']);
        if (count($entries) === 1) {
            $first = reset($entries);
            $path  = rtrim($extractDir, '/') . '/' . $first;
            if (is_dir($path)) {
                return $path . '/';
            }
        }
        return rtrim($extractDir, '/') . '/';
    }

    /**
     * Return which extraction methods are available.
     *
     * @return string[]
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        if (class_exists(\ZipArchive::class)) {
            $methods[] = 'ZipArchive';
        }
        if ($this->execAvailable()) {
            $methods[] = 'exec_unzip';
        }
        if (class_exists(\PharData::class)) {
            $methods[] = 'PharData';
        }
        return $methods;
    }

    /**
     * Validate that no zip entries contain path traversal.
     */
    private function validateZipContents(string $zipPath): bool
    {
        if (! class_exists(\ZipArchive::class)) {
            return true; // Can't validate without ZipArchive; proceed with caution
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

    private function extractViaZipArchive(string $zipPath, string $destDir): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }
        $ok = $zip->extractTo($destDir);
        $zip->close();
        return $ok;
    }

    private function extractViaExec(string $zipPath, string $destDir): bool
    {
        $cmd = sprintf(
            'unzip -o %s -d %s 2>/dev/null',
            escapeshellarg($zipPath),
            escapeshellarg($destDir)
        );
        exec($cmd, $output, $code);
        return $code === 0;
    }

    private function extractViaPhar(string $zipPath, string $destDir): bool
    {
        try {
            $phar = new \PharData($zipPath);
            $phar->extractTo($destDir, null, true);
            return true;
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
