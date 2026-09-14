<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * Profiles migration (pretend-mode guard) + seed structure.
 *
 * Migration filename carries a date prefix, so Composer's PSR-4 loader
 * cannot find it; the file is required by path.
 */
#[CoversNothing]
final class ProfilesMigrationsSeedTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once PROJECT_ROOT . '/plugins/Profiles/Database/Migrations/2026-08-26-100001_CreateProfilesTable.php';
    }

    public function testUpCreatesExpectedTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Profiles\Database\Migrations\CreateProfilesTable(),
            'up'
        ));
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql);
        self::assertStringContainsString('profiles', $sql);
        foreach (['user_id', 'display_name', 'bio', 'avatar', 'website', 'twitter', 'facebook', 'linkedin', 'job_title', 'works_for'] as $column) {
            self::assertStringContainsString($column, $sql, "profiles::{$column}");
        }
    }

    public function testDownDropsTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Profiles\Database\Migrations\CreateProfilesTable(),
            'down'
        ));
        self::assertStringContainsStringIgnoringCase('DROP TABLE', $sql);
        self::assertStringContainsString('profiles', $sql);
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/plugins/Profiles/Database/Seeds/Seed.php';

        self::assertArrayHasKey('install', $seed);
        $tables = array_column($seed['install'], 'table');
        self::assertContains('auth_permissions', $tables);

        foreach ($seed['install'] as $entry) {
            self::assertArrayHasKey('table', $entry);
            self::assertArrayHasKey('rows', $entry);
            self::assertNotEmpty($entry['rows']);
        }

        $perms = null;
        foreach ($seed['install'] as $entry) {
            if ($entry['table'] === 'auth_permissions') {
                $perms = $entry['rows'];
            }
        }
        self::assertNotNull($perms);
        self::assertContains('profile.edit', array_column($perms, 'alias'));
        self::assertContains('profile.edit.any', array_column($perms, 'alias'));
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
