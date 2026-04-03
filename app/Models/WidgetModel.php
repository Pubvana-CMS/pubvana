<?php

namespace App\Models;

use CodeIgniter\Model;

class WidgetModel extends Model
{
    protected $table      = 'widgets';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'folder', 'description', 'version', 'is_active', 'author',
        'pv_approved', 'pv_warning_note',
        'latest_version', 'changelog', 'auto_update',
        'last_update_check', 'last_update_attempt', 'last_update_error', 'last_updated_at',
    ];
}
