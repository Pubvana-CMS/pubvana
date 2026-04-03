<?php

namespace App\Models;

use CodeIgniter\Model;

class PluginModel extends Model
{
    protected $table         = 'plugins';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $createdField  = 'installed_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'folder',
        'name',
        'slug',
        'version',
        'description',
        'author',
        'is_active',
        'pv_approved',
        'pv_warning_note',
        'latest_version',
        'changelog',
        'auto_update',
        'last_update_check',
        'last_update_attempt',
        'last_update_error',
        'last_updated_at',
    ];
}
