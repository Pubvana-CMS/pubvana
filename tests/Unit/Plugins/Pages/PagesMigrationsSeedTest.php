<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * Pages migrations (pretend-mode guards) + seed structure.
 *
 * Migration filenames carry a date prefix, so Composer's PSR-4 loader
 * cannot find them; files are required by path.
 */
#[CoversNothing]
final class PagesMigrationsSeedTest extends TestCase
{
    /** @var array<string, string> */
    private const FILES = [
        'pages' => '2026-08-22-000002_CreatePagesTable.php',
        'pages_revisions' => '2026-08-29-000001_CreatePagesRevisionsTable.php',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        foreach (self::FILES as $file) {
            require_once PROJECT_ROOT . '/plugins/Pages/Database/Migrations/' . $file;
        }
    }

    public function testChangeCreatesPagesTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Pages\Database\Migrations\CreatePagesTable(),
            'change'
        ));
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql);
        self::assertStringContainsString('pages', $sql);
        foreach (['title', 'slug', 'content', 'status', 'allow_comments', 'created_by', 'deleted_at'] as $column) {
            self::assertStringContainsString($column, $sql, "pages::{$column}");
        }
    }

    public function testUpCreatesRevisionsTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Pages\Database\Migrations\CreatePagesRevisionsTable(),
            'up'
        ));
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql);
        self::assertStringContainsString('pages_revisions', $sql);
        foreach (['page_id', 'author_id', 'title', 'content', 'status'] as $column) {
            self::assertStringContainsString($column, $sql, "pages_revisions::{$column}");
        }
    }

    public function testDownDropsRevisionsTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Pages\Database\Migrations\CreatePagesRevisionsTable(),
            'down'
        ));
        self::assertStringContainsStringIgnoringCase('DROP TABLE', $sql);
        self::assertStringContainsString('pages_revisions', $sql);
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/plugins/Pages/Database/Seeds/Seed.php';

        self::assertArrayHasKey('install', $seed);
        $tables = array_column($seed['install'], 'table');
        self::assertContains('auth_permissions', $tables);
        self::assertContains('pages', $tables);

        foreach ($seed['install'] as $entry) {
            self::assertArrayHasKey('table', $entry);
            self::assertArrayHasKey('rows', $entry);
            self::assertNotEmpty($entry['rows']);
        }

        $perms = null;
        $pages = null;
        foreach ($seed['install'] as $entry) {
            if ($entry['table'] === 'auth_permissions') {
                $perms = $entry['rows'];
            }
            if ($entry['table'] === 'pages') {
                $pages = $entry['rows'];
            }
        }
        self::assertNotNull($perms);
        self::assertContains('pages.manage', array_column($perms, 'alias'));
        self::assertNotNull($pages);
        self::assertSame('welcome-to-pubvana-cms', $pages[0]['slug']);
        self::assertSame('published', $pages[0]['status']);
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
