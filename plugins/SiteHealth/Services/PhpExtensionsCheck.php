<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class PhpExtensionsCheck implements CheckInterface
{
    /** @var array<string, string> extension => why it's needed */
    private array $required = [
        'mbstring'  => 'String encoding and manipulation',
        'json'      => 'JSON encoding/decoding',
        'pdo'       => 'Database connectivity',
        'openssl'   => 'Encryption, HTTPS verification, token generation',
        'curl'      => 'HTTP requests to external services',
        'fileinfo'  => 'MIME type detection for uploads',
        'dom'       => 'HTML parsing and sanitization',
        'tokenizer' => 'Template engine support',
    ];

    /** @var array<string, string> extension => why it's recommended */
    private array $recommended = [
        'intl'    => 'Internationalization and locale formatting',
        'imagick' => 'Advanced image processing (fallback: GD)',
        'gd'      => 'Image resizing and thumbnails',
        'zip'     => 'Backup archive creation and extraction',
        'opcache' => 'PHP opcode caching for performance',
    ];

    /**
     * An extension can load under more than one name. OPcache registers as
     * "Zend OPcache", so a short-name lookup reports it missing on a server
     * that has it. Every known name is checked before an extension is called
     * absent.
     *
     * @var array<string, list<string>>
     */
    private array $aliases = [
        'opcache' => ['opcache', 'Zend OPcache'],
    ];

    /**
     * Configured database driver => the PDO extension that backs it. A host
     * can have pdo without the driver the site needs; that must fail here,
     * not later as an opaque connection error.
     *
     * @var array<string, string>
     */
    private array $driverExtensions = [
        'mysql'    => 'pdo_mysql',
        'mariadb'  => 'pdo_mysql',
        'pgsql'    => 'pdo_pgsql',
        'postgres' => 'pdo_pgsql',
        'sqlite'   => 'pdo_sqlite',
    ];

    /**
     * @param string|null $driver Configured database driver (mysql, mariadb,
     *                            pgsql, sqlite). When set, the matching PDO
     *                            extension is required.
     */
    public function __construct(private ?string $driver = null) {}

    public function run(): CheckResult
    {
        $required = $this->required;

        if ($this->driver !== null && $this->driver !== '') {
            $driver = strtolower($this->driver);
            $extension = $this->driverExtensions[$driver] ?? 'pdo_' . $driver;
            $required[$extension] = "PDO driver for the configured database ({$this->driver})";
        }

        $missing = [];
        foreach ($required as $ext => $reason) {
            if (!$this->isLoaded($ext)) {
                $missing[] = "{$ext} ({$reason})";
            }
        }

        if (!empty($missing)) {
            return new CheckResult(
                id: 'php-extensions',
                name: 'Required PHP Extensions',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::CRITICAL,
                message: 'Missing required extensions: ' . implode(', ', $missing),
                remediation: 'Install the missing extensions and restart PHP. Via command line: apt install php-<ext>, or enable them through your hosting control panel.',
            );
        }

        $missingRec = [];
        foreach ($this->recommended as $ext => $reason) {
            if (!$this->isLoaded($ext)) {
                $missingRec[] = "{$ext} ({$reason})";
            }
        }

        if (!empty($missingRec)) {
            return new CheckResult(
                id: 'php-extensions',
                name: 'Required PHP Extensions',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::WARNING,
                message: 'All required extensions present. Missing recommended: ' . implode(', ', $missingRec),
                remediation: 'Consider installing these extensions for full functionality.',
            );
        }

        return new CheckResult(
            id: 'php-extensions',
            name: 'Required PHP Extensions',
            category: CheckResult::CAT_ENVIRONMENT,
            status: CheckResult::PASS,
            message: 'All required and recommended extensions are installed.',
        );
    }

    /**
     * Whether an extension is loaded, by any of the names it registers under.
     */
    private function isLoaded(string $extension): bool
    {
        foreach ($this->aliases[$extension] ?? [$extension] as $name) {
            if (extension_loaded($name)) {
                return true;
            }
        }

        return false;
    }
}
