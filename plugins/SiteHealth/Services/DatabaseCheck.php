<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class DatabaseCheck implements CheckInterface
{
    /** @var array<string, string> */
    private array $minimumVersions = [
        'mysql'   => '5.7.0',
        'mariadb' => '10.3.0',
        'sqlite'  => '3.24.0',
    ];

    public function __construct(private \PDO $pdo) {}

    public function run(): CheckResult
    {
        try {
            $version = $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable $e) {
            return new CheckResult(
                id: 'database',
                name: 'Database Connectivity',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::CRITICAL,
                message: 'Cannot connect to database: ' . $e->getMessage(),
                remediation: 'Check database credentials in your config file. Ensure the database server is running.',
            );
        }

        $isMariaDb = stripos($version, 'mariadb') !== false;
        $engine = $isMariaDb ? 'mariadb' : $driver;
        $cleanVersion = preg_replace('/[^0-9.].*/', '', $version);
        $minimum = $this->minimumVersions[$engine] ?? null;

        if ($minimum !== null && version_compare($cleanVersion, $minimum, '<')) {
            return new CheckResult(
                id: 'database',
                name: 'Database Connectivity',
                category: CheckResult::CAT_ENVIRONMENT,
                status: CheckResult::WARNING,
                message: ucfirst($engine) . " {$cleanVersion} is below recommended minimum {$minimum}.",
                remediation: "Upgrade your database server to at least {$minimum}.",
            );
        }

        return new CheckResult(
            id: 'database',
            name: 'Database Connectivity',
            category: CheckResult::CAT_ENVIRONMENT,
            status: CheckResult::PASS,
            message: ucfirst($engine) . " {$cleanVersion} connected successfully.",
        );
    }
}
