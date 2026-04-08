<?php

namespace App\Models\Traits;

trait AddonModelTrait
{
    /**
     * Find an addon by its folder name.
     */
    public function findByFolder(string $folder): ?object
    {
        return $this->where('folder', $folder)->first();
    }

    /**
     * Find an addon by its store product ID.
     */
    public function findByStoreProductId(int $productId): ?object
    {
        return $this->where('store_product_id', $productId)->first();
    }

    /**
     * Get all addons ordered by name.
     */
    public function getAllOrdered(string $orderBy = 'name'): array
    {
        return $this->orderBy($orderBy)->findAll();
    }

    /**
     * Update an addon row by folder name.
     */
    public function updateByFolder(string $folder, array $data): bool
    {
        return $this->where('folder', $folder)->set($data)->update();
    }

    /**
     * Update an addon row by store product ID.
     */
    public function updateByStoreProductId(int $productId, array $data): bool
    {
        return $this->where('store_product_id', $productId)->set($data)->update();
    }

    /**
     * Get all addons that have an update URL and store product ID (for update checks).
     */
    public function getUpdateCheckable(): array
    {
        return $this->where('store_product_id IS NOT NULL')
                    ->where('update_url IS NOT NULL')
                    ->where('update_url !=', '')
                    ->findAll();
    }

    /**
     * Get addons with a latest_version set (update available).
     */
    public function getWithUpdates(): array
    {
        return $this->where('latest_version IS NOT NULL')->findAll();
    }

    /**
     * Get Pubvana-authored addons missing a store product ID.
     */
    public function getUnresolvedPubvana(): array
    {
        return $this->where('store_product_id IS NULL')
                    ->where('bundled', 0)
                    ->whereIn('author', ['pubvana', 'pubvana_team'])
                    ->where('folder !=', '')
                    ->findAll();
    }

    /**
     * Get third-party paid addons missing a store product ID.
     */
    public function getUnresolvedThirdParty(): array
    {
        return $this->where('store_product_id IS NULL')
                    ->where('bundled', 0)
                    ->where('free', 0)
                    ->whereNotIn('author', ['pubvana', 'pubvana_team'])
                    ->where('item_url IS NOT NULL')
                    ->where('item_url !=', '')
                    ->where('folder !=', '')
                    ->findAll();
    }

    /**
     * Toggle auto_update flag for an addon by folder.
     */
    public function toggleAutoUpdate(string $folder, bool $enabled): bool
    {
        return $this->where('folder', $folder)
                    ->set('auto_update', $enabled ? 1 : 0)
                    ->update();
    }
}
