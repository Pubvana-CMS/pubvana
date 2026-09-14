<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Marketplace\Models\MarketplaceInstall;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * MarketplaceInstall model finders + ordering.
 */
#[CoversClass(MarketplaceInstall::class)]
final class MarketplaceInstallModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        MarketplaceSchema::create($this->pdo);
    }

    public function testFindersHitAndMiss(): void
    {
        $this->insert(101, 'pubvana/blog', 'LIC-1', 'Blog');

        $byProduct = (new MarketplaceInstall($this->pdo))->findByProductId(101);
        self::assertNotNull($byProduct);
        self::assertSame('Blog', $byProduct->product_name);
        self::assertNull((new MarketplaceInstall($this->pdo))->findByProductId(999));

        $byPackage = (new MarketplaceInstall($this->pdo))->findByPackageId('pubvana/blog');
        self::assertNotNull($byPackage);
        self::assertSame(101, (int) $byPackage->store_product_id);
        self::assertNull((new MarketplaceInstall($this->pdo))->findByPackageId('pubvana/missing'));

        $byKey = (new MarketplaceInstall($this->pdo))->findByLicenseKey('LIC-1');
        self::assertNotNull($byKey);
        self::assertNull((new MarketplaceInstall($this->pdo))->findByLicenseKey('NOPE'));
    }

    public function testAllTrackedOrdersByName(): void
    {
        $this->insert(2, 'pubvana/zulu', null, 'Zulu');
        $this->insert(1, 'pubvana/alpha', null, 'Alpha');

        $all = (new MarketplaceInstall($this->pdo))->allTracked();
        self::assertSame(['Alpha', 'Zulu'], array_map(static fn($r): string => (string) $r->product_name, $all));
    }

    public function testMissOnReusedInstanceReturnsNull(): void
    {
        $this->insert(101, 'pubvana/blog', 'LIC-1', 'Blog');

        // reset() does not clear declared typed props: a miss on the same
        // instance must not return the previous row as hydrated.
        $model = new MarketplaceInstall($this->pdo);
        self::assertNotNull($model->findByProductId(101));
        self::assertNull($model->findByProductId(999));
        self::assertNotNull($model->findByPackageId('pubvana/blog'));
        self::assertNull($model->findByPackageId('pubvana/missing'));
        self::assertNotNull($model->findByLicenseKey('LIC-1'));
        self::assertNull($model->findByLicenseKey('NOPE'));
    }

    private function insert(int $productId, ?string $package, ?string $license, string $name): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO marketplace_installs (store_product_id, package_id, product_name, license_key)
             VALUES (:p, :pkg, :n, :lic)'
        );
        $stmt->execute(['p' => $productId, 'pkg' => $package, 'lic' => $license, 'n' => $name]);
    }
}
