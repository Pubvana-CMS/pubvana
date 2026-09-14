<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\ActivityLog;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * ActivityLog migration (pretend-mode guard) + seed structure.
 *
 * Migration filename carries a date prefix, so Composer's PSR-4 loader
 * cannot find it; the file is required by path.
 */
#[CoversNothing]
final class ActivityLogMigrationsSeedTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once PROJECT_ROOT . '/plugins/ActivityLog/Database/Migrations/2026-09-02-000001_CreateActivityLogsTable.php';
    }

    public function testUpCreatesExpectedTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\ActivityLog\Database\Migrations\CreateActivityLogsTable(),
            'up'
        ));
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql);
        self::assertStringContainsString('activity_logs', $sql);
        foreach (['user_id', 'user_name', 'action', 'entity_type', 'entity_id', 'entity_name', 'details', 'ip', 'user_agent', 'created_at'] as $column) {
            self::assertStringContainsString($column, $sql, "activity_logs::{$column}");
        }
    }

    public function testDownDropsTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\ActivityLog\Database\Migrations\CreateActivityLogsTable(),
            'down'
        ));
        self::assertStringContainsStringIgnoringCase('DROP TABLE', $sql);
        self::assertStringContainsString('activity_logs', $sql);
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/plugins/ActivityLog/Database/Seeds/Seed.php';

        self::assertNotEmpty($seed);
        $tables = array_column($seed, 'table');
        self::assertContains('auth_permissions', $tables);

        foreach ($seed as $entry) {
            self::assertArrayHasKey('table', $entry);
            self::assertArrayHasKey('rows', $entry);
            self::assertNotEmpty($entry['rows']);
        }

        $perms = null;
        foreach ($seed as $entry) {
            if ($entry['table'] === 'auth_permissions') {
                $perms = $entry['rows'];
            }
        }
        self::assertNotNull($perms);
        self::assertContains('activity_log.view', array_column($perms, 'alias'));
    }

    /** @return list<string> */
    private function pretendSql(object $migration, string $method): array
    {
        $pdo = new \PDO('sqlite::memory:');
        $builder = new SchemaBuilder($pdo);
        $migration->setSchemaBuilder($builder);
        $builder->setPretendMode(true);
        $migration->{$method}();

        return $builder->getPretendedSql();
    }
}
