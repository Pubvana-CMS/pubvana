<?php

namespace App\Models;

use CodeIgniter\Model;

class NavigationModel extends Model
{
    protected $table      = 'navigation';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = ['label', 'url', 'parent_id', 'sort_order', 'target', 'nav_group'];

    /**
     * Get all navigation items for a group, ordered by sort_order.
     */
    public function getByGroup(string $group = 'primary'): array
    {
        return $this->where('nav_group', $group)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
    }
}
