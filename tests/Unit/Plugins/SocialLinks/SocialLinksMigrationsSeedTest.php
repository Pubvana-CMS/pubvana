<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SocialLinks;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * SocialLinks migration (pretend-mode guard) + seed structure.
 *
 * Migration filename carries a date prefix, so Composer's PSR-4 loader
 * cannot find it; the file is required by path.
 */
#[CoversNothing]
final class SocialLinksMigrationsSeedTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once PROJECT_ROOT . '/plugins/SocialLinks/Database/Migrations/2026-09-17-105238_CreateSocialLinksTable.php';
    }

    public function testUpCreatesExpectedTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\SocialLinks\Database\Migrations\CreateSocialLinksTable(),
            'up'
        ));
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql);
        self::assertStringContainsString('social_links', $sql);
        foreach (['platform', 'label', 'url', 'icon', 'sort_order', 'is_active'] as $column) {
            self::assertStringContainsString($column, $sql, "social_links::{$column}");
        }
    }

    public function testDownDropsTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\SocialLinks\Database\Migrations\CreateSocialLinksTable(),
            'down'
        ));
        self::assertStringContainsStringIgnoringCase('DROP TABLE', $sql);
        self::assertStringContainsString('social_links', $sql);
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/plugins/SocialLinks/Database/Seeds/Seed.php';

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
        self::assertContains('social.manage', array_column($perms, 'alias'));
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
