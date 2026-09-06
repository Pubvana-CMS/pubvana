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
 * @method self notEq(string $field, mixed $value, string $operator = 'AND')
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
    public ?string $product_name = null;
    public ?string $slug = null;
    public string $item_type = 'plugin';
    public ?string $folder = null;
    public ?string $installed_version = null;
    public ?string $license_key = null;
    public string $license_scope = 'single_site';
    public int $license_valid = 0;
    public ?string $license_last_checked = null;
    public ?string $expires_at = null;
    public ?string $renews_at = null;
    public int $is_subscription = 0;
    public ?string $registered_domain = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function findByProductId(int $storeProductId): ?self
    {
        $this->reset();
        $this->eq('store_product_id', $storeProductId)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findByLicenseKey(string $key): ?self
    {
        $this->reset();
        $this->eq('license_key', $key)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * @return array<int, self>
     */
    public function allLicensed(): array
    {
        $this->reset();
        $this->isNull('license_key', 'OR')->eq('license_key', '');
        $this->reset();
        $this->notEq('license_key', null);
        return $this->findAll();
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
}
