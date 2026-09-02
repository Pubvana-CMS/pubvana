<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use Enlivenapp\Migrations\Services\MigrationSetup;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class PluginMigrationsCheck implements CheckInterface
{
    /**
     * @param array<string, mixed> $migrationConfig
     */
    public function __construct(private \PDO $pdo, private array $migrationConfig = []) {}

    public function run(): CheckResult
    {
        if (!class_exists(MigrationSetup::class)) {
            return new CheckResult(
                id: 'plugin-migrations',
                name: 'Plugin Migrations',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::WARNING,
                message: 'Migration system not installed. Cannot verify database schema status.',
                remediation: 'Install enlivenapp/migrations if your plugins require database tables.',
            );
        }

        try {
            $setup = new MigrationSetup($this->pdo, $this->migrationConfig);
            $pending = $setup->getPendingMigrations();
        } catch (\Throwable $e) {
            return new CheckResult(
                id: 'plugin-migrations',
                name: 'Plugin Migrations',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::WARNING,
                message: 'Could not check migration status: ' . $e->getMessage(),
                remediation: 'Run pending migrations. Via command line: php runway migrate, or trigger from your deployment process.',
            );
        }

        if (!empty($pending)) {
            $count = count($pending);
            $modules = array_values(array_unique(array_column($pending, 'module')));
            return new CheckResult(
                id: 'plugin-migrations',
                name: 'Plugin Migrations',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::CRITICAL,
                message: "{$count} pending migration(s) in: " . implode(', ', $modules),
                remediation: 'Run pending migrations. Via command line: php runway migrate, or trigger from your deployment process.',
            );
        }

        return new CheckResult(
            id: 'plugin-migrations',
            name: 'Plugin Migrations',
            category: CheckResult::CAT_PLUGINS,
            status: CheckResult::PASS,
            message: 'All plugin migrations are up to date.',
        );
    }
}
