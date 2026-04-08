<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\Traits\AddonModelTrait;

class WidgetModel extends Model
{
    use AddonModelTrait;
    protected $table      = 'widgets';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'folder', 'bundled', 'support_url',
        'author_url', 'free',
        'items_url', 'item_url', 'categories_url', 'categories_all_url',
        'category_url', 'featured_url', 'license_validate_url', 'license_check_url',
        'update_url', 'update_check_url', 'download_url', 'store_url',
        'description', 'version', 'is_active', 'disabled', 'disabled_reason', 'author',
        'store_product_id', 'pv_safe', 'pv_warning_note',
        'latest_version', 'changelog', 'auto_update',
        'last_update_check', 'last_update_attempt', 'last_update_error', 'last_updated_at',
    ];

    /**
     * Register a new widget from a folder scan. Returns the insert ID.
     */
    public function registerWidget(array $data): int
    {
        $this->insert($data);
        return $this->getInsertID();
    }
}
