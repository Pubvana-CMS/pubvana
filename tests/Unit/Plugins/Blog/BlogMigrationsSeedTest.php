<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * Blog migrations (up/down pretend-mode guards) + seed structure.
 *
 * Migration filenames carry a date prefix, so Composer's PSR-4 loader
 * cannot find them; files are required by path.
 */
#[CoversNothing]
final class BlogMigrationsSeedTest extends TestCase
{
    /** @var array<string, string> */
    private const FILES = [
        'posts' => '2026-08-26-000001_CreatePostsTable.php',
        'categories' => '2026-08-26-000002_CreateCategoriesTable.php',
        'tags' => '2026-08-26-000003_CreateTagsTable.php',
        'posts_to_categories' => '2026-08-26-000004_CreatePostsToCategoriesTable.php',
        'tags_to_posts' => '2026-08-26-000005_CreateTagsToPostsTable.php',
        'post_revisions' => '2026-08-26-000006_CreatePostRevisionsTable.php',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        foreach (self::FILES as $file) {
            require_once PROJECT_ROOT . '/plugins/Blog/Database/Migrations/' . $file;
        }
    }

    public function testUpCreatesExpectedTables(): void
    {
        $classes = [
            'posts' => \Pubvana\Plugins\Blog\Database\Migrations\CreatePostsTable::class,
            'categories' => \Pubvana\Plugins\Blog\Database\Migrations\CreateCategoriesTable::class,
            'tags' => \Pubvana\Plugins\Blog\Database\Migrations\CreateTagsTable::class,
            'posts_to_categories' => \Pubvana\Plugins\Blog\Database\Migrations\CreatePostsToCategoriesTable::class,
            'tags_to_posts' => \Pubvana\Plugins\Blog\Database\Migrations\CreateTagsToPostsTable::class,
            'post_revisions' => \Pubvana\Plugins\Blog\Database\Migrations\CreatePostRevisionsTable::class,
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
            \Pubvana\Plugins\Blog\Database\Migrations\CreatePostsTable::class => ['slug', 'author_id', 'preview_token'],
            \Pubvana\Plugins\Blog\Database\Migrations\CreateCategoriesTable::class => ['name', 'slug'],
            \Pubvana\Plugins\Blog\Database\Migrations\CreateTagsTable::class => ['name', 'slug'],
            \Pubvana\Plugins\Blog\Database\Migrations\CreatePostsToCategoriesTable::class => ['post_id', 'category_id'],
            \Pubvana\Plugins\Blog\Database\Migrations\CreateTagsToPostsTable::class => ['tag_id', 'post_id'],
            \Pubvana\Plugins\Blog\Database\Migrations\CreatePostRevisionsTable::class => ['post_id', 'author_id'],
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
            'posts' => \Pubvana\Plugins\Blog\Database\Migrations\CreatePostsTable::class,
            'categories' => \Pubvana\Plugins\Blog\Database\Migrations\CreateCategoriesTable::class,
            'tags' => \Pubvana\Plugins\Blog\Database\Migrations\CreateTagsTable::class,
            'posts_to_categories' => \Pubvana\Plugins\Blog\Database\Migrations\CreatePostsToCategoriesTable::class,
            'tags_to_posts' => \Pubvana\Plugins\Blog\Database\Migrations\CreateTagsToPostsTable::class,
            'post_revisions' => \Pubvana\Plugins\Blog\Database\Migrations\CreatePostRevisionsTable::class,
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
        $seed = require PROJECT_ROOT . '/plugins/Blog/Database/Seeds/Seed.php';

        self::assertArrayHasKey('install', $seed);
        $tables = array_column($seed['install'], 'table');
        self::assertContains('auth_permissions', $tables);
        self::assertContains('posts', $tables);

        foreach ($seed['install'] as $entry) {
            self::assertArrayHasKey('table', $entry);
            self::assertArrayHasKey('rows', $entry);
            self::assertNotEmpty($entry['rows']);
        }

        $perms = null;
        $posts = null;
        foreach ($seed['install'] as $entry) {
            if ($entry['table'] === 'auth_permissions') {
                $perms = $entry['rows'];
            }
            if ($entry['table'] === 'posts') {
                $posts = $entry['rows'];
            }
        }
        self::assertNotNull($perms);
        self::assertContains('posts.create', array_column($perms, 'alias'));
        self::assertNotNull($posts);
        self::assertSame('welcome-to-pubvana-cms', $posts[0]['slug']);
        self::assertSame('published', $posts[0]['status']);
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
