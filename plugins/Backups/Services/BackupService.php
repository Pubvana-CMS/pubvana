<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Backups\Services;

/**
 * Creates and manages full-site backup zips (files + gzipped database dump).
 *
 * Dual-mode database operations: mysqldump/mysql CLI when available,
 * pure PHP fallback for shared hosting without shell access.
 *
 * @package  Pubvana\Plugins\Backups
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class BackupService
{
    protected string $backupDir;
    protected int    $maxBackups;
    protected array  $backupDirs;
    protected array  $protectedConfigs;
    protected \PDO   $pdo;
    protected array  $dbCredentials;

    /**
     * @param \PDO   $pdo           Database connection
     * @param array  $config        Plugin config from Config.php
     * @param array  $dbCredentials Raw DB credentials ['host','port','dbname','user','password']
     */
    public function __construct(\PDO $pdo, array $config, array $dbCredentials)
    {
        $this->pdo              = $pdo;
        $this->dbCredentials    = $dbCredentials;
        $this->backupDir        = rtrim($config['backup_path'] ?? (PROJECT_ROOT . '/writable/backups'), '/') . '/';
        $this->maxBackups       = (int) ($config['max_backups'] ?? 15);
        $this->backupDirs       = $config['backup_dirs'] ?? ['app', 'public', 'vendor', 'themes'];
        $this->protectedConfigs = $config['protected_configs'] ?? ['app/config/services.php'];

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0775, true);
        }
        @chmod($this->backupDir, 0775);
    }

    // ------------------------------------------------------------------
    // Backup creation
    // ------------------------------------------------------------------

    /**
     * Create a full backup zip.
     *
     * @param string        $trigger     One of: manual, pre-update, pre-rollback, post-rollback
     * @param string        $triggeredBy Email/identifier of the admin who initiated it
     * @param callable|null $onProgress  fn(int $step, int $total, string $label, string $detail)
     * @return string Absolute path to the created zip file
     */
    public function createBackup(string $trigger, string $triggeredBy, ?callable $onProgress = null): string
    {
        $timestamp  = date('Y-m-d_His');
        $zipFile    = $this->backupDir . $timestamp . '-full.zip';
        $totalSteps = count($this->backupDirs) + 3; // dirs + db dump + packaging + cleanup
        $step       = 0;

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
            $fullPath = PROJECT_ROOT . '/' . $dir . '/';
            if (is_dir($fullPath)) {
                $this->zipDirectory($zip, $fullPath, $dir . '/');
            }
        }

        // Database dump
        $step++;
        if ($onProgress) {
            $onProgress($step, $totalSteps, 'Dumping database', '');
        }
        $sqlData = $this->dumpDatabase();
        $zip->addFromString('database.sql', $sqlData);

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
            'date'         => date('c'),
            'trigger'      => $trigger,
            'triggered_by' => $triggeredBy,
            'php_version'  => PHP_VERSION,
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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}-full\.zip$/', $filename)) {
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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}-full\.zip$/', $filename)) {
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
     * Dump the entire database as plain SQL.
     *
     * Fallback chain:
     *   1. shell_exec('mysqldump ...')
     *   2. Pure-PHP row-by-row export
     */
    public function dumpDatabase(): string
    {
        if ($this->execAvailable()) {
            $result = $this->dumpViaMysqldump();
            if ($result !== null) {
                return $result;
            }
        }

        return $this->dumpViaPHP();
    }

    /**
     * Restore a plain SQL dump into the database.
     *
     * Fallback chain:
     *   1. exec('mysql ...' with piped SQL)
     *   2. Pure PHP: split statements + query each
     */
    public function restoreDatabase(string $sqlData): void
    {
        if ($this->execAvailable()) {
            $ok = $this->restoreViaMysql($sqlData);
            if ($ok) {
                return;
            }
        }

        $this->restoreViaPHP($sqlData);
    }

    // ------------------------------------------------------------------
    // Private helpers - database
    // ------------------------------------------------------------------

    private function dumpViaMysqldump(): ?string
    {
        $c = $this->dbCredentials;

        $passArg = ($c['password'] ?? '') !== '' ? '-p' . escapeshellarg($c['password']) : '';
        $cmd = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s 2>/dev/null',
            escapeshellarg($c['host'] ?? 'localhost'),
            escapeshellarg((string) ($c['port'] ?? 3306)),
            escapeshellarg($c['user'] ?? ''),
            $passArg,
            escapeshellarg($c['dbname'] ?? '')
        );

        $result = shell_exec($cmd);
        return ($result !== null && $result !== '') ? $result : null;
    }

    private function dumpViaPHP(): string
    {
        $sql = '';
        $dbName = $this->dbCredentials['dbname'] ?? '';

        $sql .= "-- Pubvana DB Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database:  {$dbName}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $this->getTableNames();

        foreach ($tables as $table) {
            $createSql = $this->getCreateTable($table);

            $sql .= "-- Table: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSql . ";\n\n";

            $rows = $this->getAllRows($table);
            if (!empty($rows)) {
                $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES\n";

                $lastIdx = count($rows) - 1;
                foreach ($rows as $i => $row) {
                    $vals = array_map(function ($v): string {
                        if ($v === null) {
                            return 'NULL';
                        }
                        return $this->pdo->quote((string) $v);
                    }, array_values($row));
                    $sep = ($i === $lastIdx) ? ';' : ',';
                    $sql .= '  (' . implode(', ', $vals) . ')' . $sep . "\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    private function restoreViaMysql(string $sqlData): bool
    {
        $c = $this->dbCredentials;

        $tmpFile = $this->backupDir . 'restore_' . time() . '.sql';
        file_put_contents($tmpFile, $sqlData);

        $passArg = ($c['password'] ?? '') !== '' ? '-p' . escapeshellarg($c['password']) : '';
        $cmd = sprintf(
            'mysql -h %s -P %s -u %s %s %s < %s 2>/dev/null',
            escapeshellarg($c['host'] ?? 'localhost'),
            escapeshellarg((string) ($c['port'] ?? 3306)),
            escapeshellarg($c['user'] ?? ''),
            $passArg,
            escapeshellarg($c['dbname'] ?? ''),
            escapeshellarg($tmpFile)
        );

        $code = 0;
        exec($cmd, $output, $code);
        @unlink($tmpFile);

        return $code === 0;
    }

    private function restoreViaPHP(string $sqlData): void
    {
        $statements = preg_split('/;\s*\n/', $sqlData);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            $this->pdo->exec($stmt);
        }
    }

    // ------------------------------------------------------------------
    // Private helpers - DB introspection
    // ------------------------------------------------------------------

    private function getTableNames(): array
    {
        $tables = [];
        $stmt = $this->pdo->query('SHOW TABLES');
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $tables[] = $row[0];
        }
        return $tables;
    }

    private function getCreateTable(string $table): string
    {
        $stmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row[1];
    }

    private function getAllRows(string $table): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Private helpers - filesystem
    // ------------------------------------------------------------------

    private function zipDirectory(\ZipArchive $zip, string $dirPath, string $zipPrefix): void
    {
        if (!is_dir($dirPath)) {
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
            if ($bytes < 1024) {
                return round($bytes, 1) . ' ' . $unit;
            }
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' TB';
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