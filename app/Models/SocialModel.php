<?php

namespace App\Models;

use CodeIgniter\Model;

class SocialModel extends Model
{
    protected $table      = 'social';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['platform', 'url', 'icon', 'sort_order', 'is_active'];

    public function getActive(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('sort_order')
            ->findAll();
    }
}
