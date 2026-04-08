<?php

namespace App\Services;

class BackupService
{
    protected string $backupDir;
    protected int    $maxBackups = 15;

    /** Directories to include in the backup zip (relative to ROOTPATH). */
    protected array $backupDirs = ['app', 'public', 'vendor', 'themes', 'widgets', 'plugins'];

    /** Config files that must never be overwritten during restore. */
    protected array $protectedConfigs = [
        'app/Config/App.php',
        'app/Config/Database.php',
    ];

    public function __construct()
    {
        $this->backupDir = WRITEPATH . 'backups/';
        if (! is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    // ------------------------------------------------------------------
    // Backup creation
    // ------------------------------------------------------------------

    /**
     * Create a full backup zip.
     *
     * @param string $trigger      One of: manual, pre-update, pre-rollback, post-rollback
     * @param string $triggeredBy  Email of the admin who initiated the operation
     * @param callable|null $onProgress  fn(int $step, int $total, string $label, string $detail)
     * @return string  Absolute path to the created zip file
     */
    public function createBackup(string $trigger, string $triggeredBy, ?callable $onProgress = null): string
    {
        $timestamp = date('Y-m-d_His');
        $zipFile   = $this->backupDir . $timestamp . '-full.zip';
        $totalSteps = count($this->backupDirs) + 3; // dirs + db dump + packaging + cleanup
        $step = 0;

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create zip archive at: ' . $zipFile);
        }

        // Add directories
        foreach ($this->backupDirs as $dir) {
            $step++;
            if ($onProgress) {
                $onProgress($step, $totalSteps, "Backing up {$dir}/ directory", '');
            }
            $fullPath = ROOTPATH . $dir . '/';
            if (is_dir($fullPath)) {
                $this->zipDirectory($zip, $fullPath, $dir . '/');
            }
        }

        // Database dump
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Dumping database', '');
        }
        $sqlGzData = $this->dumpDatabase();
        $zip->addFromString('database.sql.gz', $sqlGzData);

        // Metadata
        $meta = $this->buildMeta($trigger, $triggeredBy);
        $zip->addFromString('backup-meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Package
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Packaging zip', '');
        }
        $zip->close();

        // Retention cleanup
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Cleanup (retention check)', '');
        }
        $this->enforceRetention();

        return $zipFile;
    }

    /**
     * Build the backup-meta.json data.
     */
    public function buildMeta(string $trigger, string $triggeredBy): array
    {
        return [
            'version'      => APP_VERSION,
            'date'         => date('c'),
            'trigger'      => $trigger,
            'triggered_by' => $triggeredBy,
            'php_version'  => PHP_VERSION,
            'ci_version'   => \CodeIgniter\CodeIgniter::CI_VERSION,
        ];
    }

    // ------------------------------------------------------------------
    // Listing / management
    // ------------------------------------------------------------------

    /**
     * List all backups, newest first.
     *
     * @return array<array{filename: string, size: string, created: string, path: string, meta: array|null}>
     */
    public function listBackups(): array
    {
        $files = glob($this->backupDir . '*-full.zip') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function (string $path): array {
            return [
                'filename' => basename($path),
                'size'     => $this->humanSize(filesize($path)),
                'created'  => date('Y-m-d H:i:s', filemtime($path)),
                'path'     => $path,
                'meta'     => $this->readMeta($path),
            ];
        }, $files);
    }

    /**
     * Read backup-meta.json from inside a backup zip.
     */
    public function readMeta(string $zipPath): ?array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }
        $json = $zip->getFromName('backup-meta.json');
        $zip->close();
        if ($json === false) {
            return null;
        }
        return json_decode($json, true);
    }

    /**
     * Delete a backup zip by filename (basename only).
     */
    public function deleteBackup(string $filename): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}-full\.zip$/', $filename)) {
            return false;
        }
        $path = $this->backupDir . $filename;
        if (is_file($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Get absolute path to a backup by filename. Returns null if invalid or not found.
     */
    public function getBackupPath(string $filename): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}-full\.zip$/', $filename)) {
            return null;
        }
        $path = $this->backupDir . $filename;
        return is_file($path) ? $path : null;
    }

    /**
     * Return the backup directory path.
     */
    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    // ------------------------------------------------------------------
    // Database operations
    // ------------------------------------------------------------------

    /**
     * Dump the entire database as gzipped SQL.
     *
     * Fallback chain:
     *   1. exec('mysqldump ...') piped through gzip
     *   2. Pure-PHP row-by-row export with gzencode()
     */
    public function dumpDatabase(): string
    {
        // Try exec + mysqldump first
        if ($this->execAvailable()) {
            $result = $this->dumpViaMysqldump();
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback: pure PHP
        return $this->dumpViaPHP();
    }

    /**
     * Restore a gzipped SQL dump into the database.
     *
     * Fallback chain:
     *   1. exec('gunzip -c ... | mysql ...')
     *   2. Pure PHP: gzdecode + split statements + query each
     */
    public function restoreDatabase(string $sqlGzData): void
    {
        // Try exec + mysql CLI first
        if ($this->execAvailable()) {
            $ok = $this->restoreViaMysql($sqlGzData);
            if ($ok) {
                return;
            }
        }

        // Fallback: pure PHP
        $this->restoreViaPHP($sqlGzData);
    }

    // ------------------------------------------------------------------
    // Private helpers — database
    // ------------------------------------------------------------------

    private function dumpViaMysqldump(): ?string
    {
        $creds  = model(\App\Models\BackupModel::class)->getCredentials();
        $dbName = $creds->database;
        $host   = $creds->hostname;
        $user   = $creds->username;
        $pass   = $creds->password;
        $port   = $creds->port;

        $passArg = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
        $cmd = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s 2>/dev/null | gzip',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            $passArg,
            escapeshellarg($dbName)
        );

        $output = [];
        $code   = 0;
        exec($cmd, $output, $code);

        if ($code !== 0) {
            return null;
        }

        // exec() returns lines; for binary we need to capture differently
        // Use shell_exec or popen for binary data
        $result = shell_exec($cmd);
        return $result ?: null;
    }

    private function dumpViaPHP(): string
    {
        $dbModel = model(\App\Models\BackupModel::class);
        $sql = '';

        $dbName = $dbModel->getCredentials()->database;
        $sql .= "-- Pubvana DB Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database:  {$dbName}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $dbModel->getTableNames();

        foreach ($tables as $table) {
            $createSql = $dbModel->getCreateTable($table);

            $sql .= "-- Table: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSql . ";\n\n";

            $rows = $dbModel->getAllRows($table);
            if (! empty($rows)) {
                $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES\n";

                $lastIdx = count($rows) - 1;
                foreach ($rows as $i => $row) {
                    $vals = array_map(function ($v) use ($dbModel): string {
                        if ($v === null) return 'NULL';
                        return $dbModel->escape($v);
                    }, array_values($row));
                    $sep = ($i === $lastIdx) ? ';' : ',';
                    $sql .= '  (' . implode(', ', $vals) . ')' . $sep . "\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return gzencode($sql);
    }

    private function restoreViaMysql(string $sqlGzData): bool
    {
        $creds  = model(\App\Models\BackupModel::class)->getCredentials();
        $dbName = $creds->database;
        $host   = $creds->hostname;
        $user   = $creds->username;
        $pass   = $creds->password;
        $port   = $creds->port;

        // Write gzipped data to temp file
        $tmpFile = WRITEPATH . 'updates/restore_' . time() . '.sql.gz';
        file_put_contents($tmpFile, $sqlGzData);

        $passArg = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
        $cmd = sprintf(
            'gunzip -c %s | mysql -h %s -P %s -u %s %s %s 2>/dev/null',
            escapeshellarg($tmpFile),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            $passArg,
            escapeshellarg($dbName)
        );

        $code = 0;
        exec($cmd, $output, $code);
        @unlink($tmpFile);

        return $code === 0;
    }

    private function restoreViaPHP(string $sqlGzData): void
    {
        $sql = gzdecode($sqlGzData);
        if ($sql === false) {
            throw new \RuntimeException('Failed to decompress database backup.');
        }

        $dbModel = model(\App\Models\BackupModel::class);

        // Split on semicolons that end a line (naive but works for mysqldump output)
        $statements = preg_split('/;\s*\n/', $sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            $dbModel->rawQuery($stmt);
        }
    }

    // ------------------------------------------------------------------
    // Private helpers — filesystem
    // ------------------------------------------------------------------

    private function zipDirectory(\ZipArchive $zip, string $dirPath, string $zipPrefix): void
    {
        if (! is_dir($dirPath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relative = $zipPrefix . str_replace($dirPath, '', $file->getPathname());
            $relative = str_replace('\\', '/', $relative);

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($file->getPathname(), $relative);
            }
        }
    }

    private function enforceRetention(): void
    {
        $files = glob($this->backupDir . '*-full.zip') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        while (count($files) > $this->maxBackups) {
            $oldest = array_pop($files);
            @unlink($oldest);
        }
    }

    private function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) return round($bytes, 1) . ' ' . $unit;
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' TB';
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
