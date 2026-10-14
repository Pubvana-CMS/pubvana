<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace\Models;

/**
 * MarketplaceInstall - ActiveRecord model for the marketplace_installs table.
 *
 * Tracks, on the Marketplace site, every item verified for this domain along
 * with its license state as reported by the Digital Store. This is internal
 * bookkeeping: the buyer never types a license key here. The Marketplace
 * verifies purchases back at pubvanacms.com and stores the result.
 *
 * @package Pubvana\Plugins\Marketplace\Models
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEqual(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 */
class MarketplaceInstall extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'marketplace_installs', $config);
    }

    public int $id;
    public int $store_product_id = 0;
    public ?string $package_id = null;
    public ?string $product_name = null;
    public ?string $slug = null;
    public string $item_type = 'plugin';
    public ?string $folder = null;
    public ?string $license_key = null;
    public ?string $tier = null;
    public int $license_valid = 0;
    public ?string $license_last_checked = null;
    public ?string $registered_domain = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function findById(int $id): ?self
    {
        // Fresh instance: see findByProductId() for why $this cannot be reused.
        $query = new self($this->getDatabaseConnection());
        $query->eq('id', $id)->find();
        return $query->isHydrated() ? $query : null;
    }

    public function findByProductId(int $storeProductId): ?self
    {
        // Fresh instance: reset() does not clear declared typed props, so a
        // miss on a reused instance would return stale data as hydrated.
        $query = new self($this->getDatabaseConnection());
        $query->eq('store_product_id', $storeProductId)->find();
        return $query->isHydrated() ? $query : null;
    }

    /**
     * The install record whose package_id matches. package_id is the addon
     * identity: the manifest pubvana.json name ('pubvana/blog').
     */
    public function findByPackageId(string $packageId): ?self
    {
        // Fresh instance: see findByProductId() for why $this cannot be reused.
        $query = new self($this->getDatabaseConnection());
        $query->eq('package_id', $packageId)->find();
        return $query->isHydrated() ? $query : null;
    }

    public function findByLicenseKey(string $key): ?self
    {
        // Fresh instance: see findByProductId() for why $this cannot be reused.
        $query = new self($this->getDatabaseConnection());
        $query->eq('license_key', $key)->find();
        return $query->isHydrated() ? $query : null;
    }

    /**
     * @return array<int, self>
     */
    public function allTracked(): array
    {
        $this->reset();
        $this->order('product_name ASC');
        return $this->findAll();
    }

    /**
     * Bump the verify timestamps for many records in one query.
     *
     * verifyPurchases() runs on every Purchases load and the timestamps are
     * the only thing that changes on a steady state check; one UPDATE beats
     * one save() per row.
     *
     * @param array<int, string> $licenseKeys License keys just verified
     */
    public function stampVerified(array $licenseKeys, string $now): void
    {
        $keys = array_values(array_filter($licenseKeys, static fn (string $key): bool => $key !== ''));
        if ($keys === []) {
            return;
        }

        $pdo = $this->getDatabaseConnection();
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare(
            'UPDATE `marketplace_installs` SET `license_last_checked` = ?, `updated_at` = ? WHERE `license_key` IN (' . $placeholders . ')'
        );
        $stmt->execute(array_merge([$now, $now], $keys));
    }
}
