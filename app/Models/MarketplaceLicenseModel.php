<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketplaceLicenseModel extends Model
{
    protected $table      = 'marketplace_licenses';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'license_key',
        'store_product_id',
        'product_name',
        'author',
        'item_type',
        'registered_domain',
        'is_subscription',
        'expires_at',
        'subscription_renews_at',
        'license_valid',
        'license_last_checked',
        'installed_version',
    ];
}
