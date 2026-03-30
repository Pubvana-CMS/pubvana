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
        'is_active',
        'pv_approved',
        'pv_warning_note',
    ];
}
