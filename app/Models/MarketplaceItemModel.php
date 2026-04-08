<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketplaceItemModel extends Model
{
    protected $table      = 'marketplace_items';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'item_type', 'name', 'slug', 'description', 'version', 'price',
        'is_free', 'download_url', 'store_url', 'screenshot_url', 'author', 'installed_version',
        'license_key',
    ];

    /**
     * Find a marketplace item by slug.
     */
    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * Get all items with an available update.
     * Note: returns arrays to match the existing checkUpdates() behavior.
     */
    public function getUpdatable(): array
    {
        return $this->db->table($this->table)
                    ->where('installed_version IS NOT NULL')
                    ->where('installed_version != version')
                    ->get()->getResultArray();
    }
}
