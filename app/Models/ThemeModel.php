<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\Traits\AddonModelTrait;

class ThemeModel extends Model
{
    use AddonModelTrait;
    protected $table      = 'themes';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'folder', 'description', 'bundled', 'support_url',
        'author_url', 'free',
        'items_url', 'item_url', 'categories_url', 'categories_all_url',
        'category_url', 'featured_url', 'license_validate_url', 'license_check_url',
        'update_url', 'update_check_url', 'download_url', 'store_url',
        'is_active', 'disabled', 'disabled_reason',
        'version', 'installed_at', 'author',
        'store_product_id', 'pv_safe', 'pv_warning_note',
        'latest_version', 'changelog', 'auto_update',
        'last_update_check', 'last_update_attempt', 'last_update_error', 'last_updated_at',
    ];

    /**
     * Deactivate a theme and fall back to default.
     */
    public function deactivateAndFallback(int $id): void
    {
        $this->update($id, ['is_active' => 0]);
        $this->where('folder', 'default')->set('is_active', 1)->update();
    }
}
