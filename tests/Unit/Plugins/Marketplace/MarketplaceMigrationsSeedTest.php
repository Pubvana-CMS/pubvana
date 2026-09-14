<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use Enlivenapp\Migrations\Services\SchemaBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pubvana\Tests\Support\TestCase;

/**
 * Marketplace migrations (pretend-mode guards) + seed structure.
 *
 * Migration filenames carry a date prefix, so Composer's PSR-4 loader
 * cannot find them; files are required by path.
 */
#[CoversNothing]
final class MarketplaceMigrationsSeedTest extends TestCase
{
    /** @var array<string, string> */
    private const FILES = [
        'create' => '2026-09-06-000001_CreateMarketplaceInstallsTable.php',
        'package_id' => '2026-09-11-115828_AddPackageIdToMarketplaceInstalls.php',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        foreach (self::FILES as $file) {
            require_once PROJECT_ROOT . '/plugins/Marketplace/Database/Migrations/' . $file;
        }
    }

    public function testCreateBuildsInstallsTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Marketplace\Database\Migrations\CreateMarketplaceInstallsTable(),
            'up'
        ));
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $sql);
        self::assertStringContainsString('marketplace_installs', $sql);
        foreach (['store_product_id', 'product_name', 'item_type', 'folder', 'installed_version', 'license_key', 'license_scope', 'license_valid', 'registered_domain'] as $column) {
            self::assertStringContainsString($column, $sql, "marketplace_installs::{$column}");
        }
    }

    public function testDownDropsInstallsTable(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Marketplace\Database\Migrations\CreateMarketplaceInstallsTable(),
            'down'
        ));
        self::assertStringContainsStringIgnoringCase('DROP TABLE', $sql);
        self::assertStringContainsString('marketplace_installs', $sql);
    }

    public function testPackageIdMigrationAddsColumn(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Marketplace\Database\Migrations\AddPackageIdToMarketplaceInstalls(),
            'up'
        ));
        self::assertStringContainsString('marketplace_installs', $sql);
        self::assertStringContainsString('package_id', $sql);
    }

    public function testPackageIdMigrationRemovesColumnOnDown(): void
    {
        $sql = implode("\n", $this->pretendSql(
            new \Pubvana\Plugins\Marketplace\Database\Migrations\AddPackageIdToMarketplaceInstalls(),
            'down'
        ));
        self::assertStringContainsString('package_id', $sql);
        self::assertStringContainsString('marketplace_installs', $sql);
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/plugins/Marketplace/Database/Seeds/Seed.php';

        self::assertArrayHasKey('install', $seed);
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
        self::assertContains('marketplace.manage', array_column($perms, 'alias'));
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
