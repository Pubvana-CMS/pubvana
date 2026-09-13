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

    /** @var list<string> Top-level directories included in file backups */
    protected array  $backupDirs;

    /** @var list<string> Config files whose credentials are never backed up */
    protected array  $protectedConfigs;

    protected \PDO   $pdo;

    /** @var array{host: string, port: int, dbname: string, user: string, password: string} */
    protected array  $dbCredentials;

    /**
     * @param \PDO            $pdo           Database connection
     * @param array<string, mixed> $config        Plugin config from Config.php
     * @param array{host: string, port: int, dbname: string, user: string, password: string} $dbCredentials Raw DB credentials
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
        $zipFile    = $this->freshZipPath();
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
        $metaJson = json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($metaJson === false) {
            $zip->close();
            throw new \RuntimeException('Unable to encode backup metadata.');
        }
        $zip->addFromString('backup-meta.json', $metaJson);

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
     *
     * @return array<string, string>
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

    /**
     * Path for a new backup zip that does not collide with an existing file.
     *
     * The timestamp format is fixed by the enforced filename regex
     * (deleteBackup()/getBackupPath()). Two backups in the same second
     * would otherwise silently overwrite each other (CREATE|OVERWRITE),
     * so a colliding name shifts one second until free.
     */
    private function freshZipPath(): string
    {
        $ts = time();
        do {
            $path = $this->backupDir . date('Y-m-d_His', $ts) . '-full.zip';
            if (!is_file($path)) {
                return $path;
            }
            $ts++;
        } while ($ts < time() + 3600);

        throw new \RuntimeException('Unable to find a free backup filename in the backup directory.');
    }

    // ------------------------------------------------------------------
    // Listing / management
    // ------------------------------------------------------------------

    /**
     * List all backups, newest first.
     *
     * @return list<array{filename: string, size: string, created: string, path: string, meta: array<string, mixed>|null}>
     */
    public function listBackups(): array
    {
        $files = glob($this->backupDir . '*-full.zip') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function (string $path): array {
            $size = filesize($path);
            $mtime = filemtime($path);
            return [
                'filename' => basename($path),
                'size'     => $this->humanSize($size === false ? 0 : $size),
                'created'  => date('Y-m-d H:i:s', $mtime === false ? 0 : $mtime),
                'path'     => $path,
                'meta'     => $this->readMeta($path),
            ];
        }, $files);
    }

    /**
     * Read backup-meta.json from inside a backup zip.
     *
     * @return array<string, mixed>|null
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
     *   1. proc_open('mysqldump ...', password via MYSQL_PWD env)
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
     *   1. proc_open('mysql ...' with piped SQL, password via MYSQL_PWD env)
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

        $argv = [
            'mysqldump',
            '-h', $c['host'],
            '-P', (string) $c['port'],
            '-u', $c['user'],
            // Triggers/events (routines) dump with DELIMITER blocks that the
            // pure-PHP restore fallback cannot parse; keep every dump plain.
            '--skip-triggers',
            '--skip-routines',
            '--skip-events',
            $c['dbname'],
        ];

        $stdout = null;
        $stderr = null;
        $code = $this->runProc($argv, $this->mysqlEnv($c), $stdout, $stderr);

        return $code === 0 && $stdout !== null && $stdout !== '' ? $stdout : null;
    }

    private function dumpViaPHP(): string
    {
        $sql = '';
        $dbName = $this->dbCredentials['dbname'];

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

        $argv = [
            'mysql',
            '-h', $c['host'],
            '-P', (string) $c['port'],
            '-u', $c['user'],
            $c['dbname'],
        ];

        $stdout = null;
        $stderr = null;
        $code = $this->runProc($argv, $this->mysqlEnv($c), $stdout, $stderr, $tmpFile);
        @unlink($tmpFile);

        return $code === 0;
    }

    private function restoreViaPHP(string $sqlData): void
    {
        foreach ($this->splitSqlStatements($sqlData) as $stmt) {
            $this->pdo->exec($stmt);
        }
    }

    /**
     * Split a SQL dump into individual statements without corrupting
     * string literals.
     *
     * The previous split on `;\s*\n` broke any stored value containing
     * that sequence (a page body, a comment, a form field), truncating
     * the INSERT or throwing mid-restore. This walks the dump tracking
     * MySQL lexical state instead: single/double-quoted strings, backtick
     * identifiers, line comments (`--` needing trailing whitespace per
     * the MySQL grammar, and `#`) and block comments (`/* ... *\/`). A
     * semicolon inside any of these never splits. Conditional comments
     * (`/*!40101 SET ... *\/`) count as code because MySQL executes them,
     * so those statements survive intact. Each emitted statement starts
     * at its first executable character, so leading dump-header comments
     * are stripped, and comment-only fragments yield nothing.
     *
     * @return list<string> Statements, trimmed, without trailing semicolons
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $len        = strlen($sql);
        $state      = 'code'; // code | single | double | backtick | line | block
        $stmtStart  = null;   // offset of the statement's first executable char

        for ($i = 0; $i < $len; $i++) {
            $ch   = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($state === 'code') {
                if ($ch === "'" || $ch === '"' || $ch === '`') {
                    $stmtStart = $stmtStart ?? $i;
                    $state     = $ch === "'" ? 'single' : ($ch === '"' ? 'double' : 'backtick');
                    continue;
                }
                if ($ch === '-' && $next === '-') {
                    $after = $i + 2 < $len ? $sql[$i + 2] : ' ';
                    if ($after === ' ' || $after === "\t" || $after === "\n" || $after === "\r") {
                        $state = 'line';
                        $i++;
                        continue;
                    }
                }
                if ($ch === '#') {
                    $state = 'line';
                    continue;
                }
                if ($ch === '/' && $next === '*') {
                    // `/*!...*/` is executed by MySQL, so it is code.
                    if (($sql[$i + 2] ?? '') === '!') {
                        $stmtStart = $stmtStart ?? $i;
                    }
                    $state = 'block';
                    $i++;
                    continue;
                }
                if ($ch === ';') {
                    if ($stmtStart !== null) {
                        $statements[] = trim(substr($sql, $stmtStart, $i - $stmtStart));
                        $stmtStart    = null;
                    }
                    continue;
                }
                if (!ctype_space($ch)) {
                    $stmtStart = $stmtStart ?? $i;
                }
                continue;
            }

            if ($state === 'single' || $state === 'double' || $state === 'backtick') {
                $close = $state === 'single' ? "'" : ($state === 'double' ? '"' : '`');
                if ($ch === '\\') {
                    // Backslash escape applies inside quoted strings (MySQL
                    // default mode), not inside backtick identifiers.
                    if ($state !== 'backtick') {
                        $i++;
                    }
                    continue;
                }
                if ($ch === $close) {
                    if ($next === $close) {
                        // Doubled quote / backtick is a literal, not the end.
                        $i++;
                        continue;
                    }
                    $state = 'code';
                }
                continue;
            }

            if ($state === 'line') {
                if ($ch === "\n") {
                    $state = 'code';
                }
                continue;
            }

            // $state === 'block'
            if ($ch === '*' && $next === '/') {
                $state = 'code';
                $i++;
            }
        }

        if ($stmtStart !== null) {
            $statements[] = trim(substr($sql, $stmtStart));
        }

        return $statements;
    }

    // ------------------------------------------------------------------
    // Private helpers - DB introspection
    // ------------------------------------------------------------------

    /**
     * @return array<int, string>
     */
    private function getTableNames(): array
    {
        $tables = [];
        $stmt = $this->pdo->query('SHOW TABLES');
        if ($stmt === false) {
            throw new \RuntimeException('Unable to list database tables for backup.');
        }
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $tables[] = (string) $row[0];
        }
        return $tables;
    }

    private function getCreateTable(string $table): string
    {
        $stmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`");
        if ($stmt === false) {
            throw new \RuntimeException("Unable to read schema for table '{$table}'.");
        }
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row === false || !isset($row[1])) {
            throw new \RuntimeException("No schema returned for table '{$table}'.");
        }
        return (string) $row[1];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAllRows(string $table): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `{$table}`");
        if ($stmt === false) {
            throw new \RuntimeException("Unable to read rows of table '{$table}'.");
        }
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

            // Links are never archived: is_dir()/is_file() would follow the
            // link, and the restore copy walk would then traverse the target.
            if ($file->isLink()) {
                continue;
            }

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
            if ($oldest !== null) {
                @unlink($oldest);
            }
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
        if (!function_exists('proc_open')) {
            return false;
        }
        $disabled = ini_get('disable_functions') ?: '';
        return !in_array('proc_open', array_map('trim', explode(',', $disabled)), true);
    }

    /**
     * Run a binary via proc_open. No shell is involved, so the DB password
     * sits only in the child environment (MYSQL_PWD), never in argv or ps.
     *
     * @param list<string>           $argv   Command and its arguments
     * @param array<string, string>  $env    Full child environment
     * @param ?string                $stdin  Path to feed on stdin, or null for /dev/null
     * @param string|null            $stdout Captured stdout
     * @param string|null            $stderr Captured stderr
     *
     * @return int Exit status, or -1 when proc_open is unavailable or cannot start
     */
    private function runProc(array $argv, array $env, ?string &$stdout, ?string &$stderr, ?string $stdin = null): int
    {
        if (!function_exists('proc_open')) {
            return -1;
        }

        $spec = [
            0 => ($stdin !== null && is_file($stdin)) ? ['file', $stdin, 'r'] : ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($argv, $spec, $pipes, null, $env);
        if (!is_resource($process)) {
            return -1;
        }

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    /**
     * Build the child environment for the mysql clients.
     *
     * The password travels in MYSQL_PWD so it never appears on the command
     * line. The var is unset when no password is set.
     *
     * @param array{host: string, port: int, dbname: string, user: string, password: string} $credentials
     *
     * @return array<string, string>
     */
    private function mysqlEnv(array $credentials): array
    {
        $env = getenv();

        if ($credentials['password'] !== '') {
            $env['MYSQL_PWD'] = $credentials['password'];
        } else {
            unset($env['MYSQL_PWD']);
        }

        return $env;
    }
}