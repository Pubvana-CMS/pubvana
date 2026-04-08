<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\Traits\AddonModelTrait;

class PluginModel extends Model
{
    use AddonModelTrait;
    protected $table         = 'plugins';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $createdField  = 'installed_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'folder',
        'name',
        'bundled',
        'support_url',
        'author_url',
        'free',
        'items_url',
        'item_url',
        'categories_url',
        'categories_all_url',
        'category_url',
        'featured_url',
        'license_validate_url',
        'license_check_url',
        'update_url',
        'update_check_url',
        'download_url',
        'store_url',
        'version',
        'description',
        'author',
        'is_active',
        'disabled',
        'disabled_reason',
        'pv_safe',
        'pv_warning_note',
        'latest_version',
        'changelog',
        'auto_update',
        'store_product_id',
        'last_update_check',
        'last_update_attempt',
        'last_update_error',
        'last_updated_at',
    ];
}
