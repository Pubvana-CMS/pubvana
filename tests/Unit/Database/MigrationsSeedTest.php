<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Database;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * app/Database migration + seed guards.
 *
 * Each migration runs in pretend mode against SQLite (SQL captured, nothing
 * executed) to lock the schema: table created, key columns present. The
 * navigation migration uses up()/down() explicitly, so both directions are
 * captured. The seed file is a pure data array: structure + required fields.
 */
#[CoversNothing]
final class MigrationsSeedTest extends TestCase
{
    /** @var array<string, class-string> */
    private const MIGRATIONS = [
        'block_placements' => \Pubvana\Database\Migrations\CreateBlockPlacementsTable::class,
        'settings' => \Pubvana\Database\Migrations\CreateSettingsTable::class,
        'themes' => \Pubvana\Database\Migrations\CreateThemesTable::class,
        'theme_options' => \Pubvana\Database\Migrations\CreateThemeOptionsTable::class,
        'mail_logs' => \Pubvana\Database\Migrations\CreateMailLogsTable::class,
        'plugin_state' => \Pubvana\Database\Migrations\CreatePluginStateTable::class,
        'trust_cache' => \Pubvana\Database\Migrations\CreateTrustCacheTable::class,
    ];

    /** @var array<string, string> */
    private const FILES = [
        'block_placements' => '2026-09-17-105101_CreateBlockPlacementsTable.php',
        'settings' => '2026-09-17-105102_CreateSettingsTable.php',
        'themes' => '2026-09-17-105103_CreateThemesTable.php',
        'theme_options' => '2026-09-17-105104_CreateThemeOptionsTable.php',
        'navigation' => '2026-09-17-105105_CreateNavigationTable.php',
        'mail_logs' => '2026-09-17-105106_CreateMailLogsTable.php',
        'plugin_state' => '2026-09-17-105107_CreatePluginStateTable.php',
        'trust_cache' => '2026-09-17-105108_CreateTrustCacheTable.php',
    ];

    /**
     * Migration filenames carry a date prefix, so Composer's PSR-4 loader
     * cannot find them. Require each file once by path instead.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        foreach (self::FILES as $file) {
            require_once PROJECT_ROOT . '/app/Database/Migrations/' . $file;
        }
    }

    /** @return list<string> */
    private function pretendSql(object $migration, string $method = 'change'): array
    {
        $pdo = new \PDO('sqlite::memory:');
        $builder = new SchemaBuilder($pdo);
        $migration->setSchemaBuilder($builder);
        $builder->setPretendMode(true);
        $migration->{$method}();
        // Read before disabling: setPretendMode(false) clears the capture.
        return $builder->getPretendedSql();
    }

    public function testChangeMigrationsCreateExpectedTables(): void
    {
        foreach (self::MIGRATIONS as $table => $class) {
            $sql = implode("\n", $this->pretendSql(new $class()));
            self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql, $class);
            self::assertStringContainsString($table, $sql, $class);
        }
    }

    public function testChangeMigrationsCarryKeyColumns(): void
    {
        $expect = [
            'block_placements' => ['region_id', 'block_key', 'sort_order'],
            'settings' => ['`key`', 'value', 'autoload'],
            'themes' => ['folder', 'is_active'],
            'theme_options' => ['theme_id', 'option_key'],
            'mail_logs' => ['to_address', 'subject', 'status'],
            'plugin_state' => ['plugin_id', 'enabled', 'priority'],
            'trust_cache' => ['slug', 'version', 'status'],
        ];

        foreach ($expect as $table => $columns) {
            $sql = implode("\n", $this->pretendSql(new (self::MIGRATIONS[$table])()));
            foreach ($columns as $column) {
                self::assertStringContainsString($column, $sql, "{$table}.{$column}");
            }
        }
    }

    public function testNavigationUpAndDown(): void
    {
        $up = implode("\n", $this->pretendSql(new \Pubvana\Database\Migrations\CreateNavigationTable(), 'up'));
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $up);
        self::assertStringContainsString('navigation', $up);
        self::assertStringContainsString('nav_group', $up);

        $down = implode("\n", $this->pretendSql(new \Pubvana\Database\Migrations\CreateNavigationTable(), 'down'));
        self::assertStringContainsStringIgnoringCase('DROP TABLE', $down);
        self::assertStringContainsString('navigation', $down);
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/app/Database/Seeds/Seed.php';

        self::assertArrayHasKey('install', $seed);
        self::assertNotEmpty($seed['install']);

        $tables = [];
        foreach ($seed['install'] as $entry) {
            self::assertArrayHasKey('table', $entry);
            self::assertArrayHasKey('rows', $entry);
            self::assertNotEmpty($entry['rows']);
            $tables[] = $entry['table'];
        }
        foreach (['auth_permissions', 'navigation', 'settings', 'themes', 'plugin_state'] as $table) {
            self::assertContains($table, $tables, $table);
        }
    }

    public function testSeedRequiredFields(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/app/Database/Seeds/Seed.php';
        $byTable = [];
        foreach ($seed['install'] as $entry) {
            $byTable[$entry['table']] = $entry['rows'];
        }

        foreach ($byTable['auth_permissions'] as $row) {
            self::assertArrayHasKey('alias', $row);
        }
        self::assertContains('plugins.manage', array_column($byTable['auth_permissions'], 'alias'));
        self::assertContains('navigation.edit', array_column($byTable['auth_permissions'], 'alias'));

        foreach ($byTable['navigation'] as $row) {
            foreach (['label', 'url', 'nav_group'] as $key) {
                self::assertArrayHasKey($key, $row, "navigation.{$key}");
            }
        }

        foreach ($byTable['settings'] as $row) {
            foreach (['key', 'value', 'type'] as $key) {
                self::assertArrayHasKey($key, $row, "settings.{$key}");
            }
        }
        self::assertContains('CMS.siteName', array_column($byTable['settings'], 'key'));

        foreach ($byTable['themes'] as $row) {
            foreach (['name', 'folder', 'is_active'] as $key) {
                self::assertArrayHasKey($key, $row, "themes.{$key}");
            }
        }
        self::assertContains('default', array_column($byTable['themes'], 'folder'));
        self::assertContains(1, array_column($byTable['themes'], 'is_active'), 'the default theme ships active');

        foreach ($byTable['plugin_state'] as $row) {
            foreach (['plugin_id', 'enabled', 'priority'] as $key) {
                self::assertArrayHasKey($key, $row, "plugin_state.{$key}");
            }
        }
    }
}
