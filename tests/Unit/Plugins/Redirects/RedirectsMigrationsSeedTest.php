<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Redirects;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * Redirects migrations (pretend-mode guards) + seed structure.
 *
 * Migration filenames carry a date prefix, so Composer's PSR-4 loader
 * cannot find them; files are required by path.
 */
#[CoversNothing]
final class RedirectsMigrationsSeedTest extends TestCase
{
    /** @var array<string, string> */
    private const FILES = [
        'redirects' => '2026-08-28-100001_CreateRedirectsTable.php',
        'redirects_links' => '2026-08-28-100002_CreateRedirectLinksTable.php',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        foreach (self::FILES as $file) {
            require_once PROJECT_ROOT . '/plugins/Redirects/Database/Migrations/' . $file;
        }
    }

    public function testUpCreatesExpectedTables(): void
    {
        $classes = [
            'redirects' => \Pubvana\Plugins\Redirects\Database\Migrations\CreateRedirectsTable::class,
            'redirects_links' => \Pubvana\Plugins\Redirects\Database\Migrations\CreateRedirectLinksTable::class,
        ];

        foreach ($classes as $table => $class) {
            $sql = implode("\n", $this->pretendSql(new $class(), 'up'));
            self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql, $class);
            self::assertStringContainsString($table, $sql, $class);
        }
    }

    public function testUpCarriesKeyColumns(): void
    {
        $expect = [
            \Pubvana\Plugins\Redirects\Database\Migrations\CreateRedirectsTable::class => ['source_path', 'target_url', 'status_code', 'enabled', 'hit_count'],
            \Pubvana\Plugins\Redirects\Database\Migrations\CreateRedirectLinksTable::class => ['source_path', 'hit_count', 'ignored', 'resolved_redirect_id', 'first_seen_at', 'last_seen_at'],
        ];

        foreach ($expect as $class => $columns) {
            $sql = implode("\n", $this->pretendSql(new $class(), 'up'));
            foreach ($columns as $column) {
                self::assertStringContainsString($column, $sql, "{$class}::{$column}");
            }
        }
    }

    public function testDownDropsTables(): void
    {
        $classes = [
            'redirects' => \Pubvana\Plugins\Redirects\Database\Migrations\CreateRedirectsTable::class,
            'redirects_links' => \Pubvana\Plugins\Redirects\Database\Migrations\CreateRedirectLinksTable::class,
        ];

        foreach ($classes as $table => $class) {
            $sql = implode("\n", $this->pretendSql(new $class(), 'down'));
            self::assertStringContainsStringIgnoringCase('DROP TABLE', $sql, $class);
            self::assertStringContainsString($table, $sql, $class);
        }
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/plugins/Redirects/Database/Seeds/Seed.php';

        self::assertArrayHasKey('install', $seed);
        $tables = array_column($seed['install'], 'table');
        self::assertContains('redirects', $tables);

        foreach ($seed['install'] as $entry) {
            self::assertArrayHasKey('table', $entry);
            self::assertArrayHasKey('rows', $entry);
            self::assertNotEmpty($entry['rows']);
        }

        $rows = null;
        foreach ($seed['install'] as $entry) {
            if ($entry['table'] === 'redirects') {
                $rows = $entry['rows'];
            }
        }
        self::assertNotNull($rows);
        self::assertSame('/wp-login.php', $rows[0]['source_path']);
        self::assertSame('/page/not-wordpress', $rows[0]['target_url']);
        foreach ($rows as $row) {
            self::assertArrayHasKey('source_path', $row);
            self::assertArrayHasKey('target_url', $row);
            self::assertArrayHasKey('status_code', $row);
            self::assertArrayHasKey('enabled', $row);
        }
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
