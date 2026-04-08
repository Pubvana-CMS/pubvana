<?php

namespace App\Models;

use CodeIgniter\Model;

class WidgetInstanceModel extends Model
{
    protected $table      = 'widget_instances';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['widget_id', 'widget_area_id', 'sort_order', 'options_json'];

    /**
     * Get all widget instances for given area slugs, with widget and area info.
     */
    public function getForAreas(array $areaSlugs): array
    {
        if (empty($areaSlugs)) return [];

        return $this->db->table('widget_instances wi')
            ->select('wi.*, w.name AS widget_name, w.folder, wa.slug AS area_slug')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->whereIn('wa.slug', $areaSlugs)
            ->where('w.is_active', 1)
            ->where('w.disabled IS NULL')
            ->orderBy('wa.slug')
            ->orderBy('wi.sort_order', 'ASC')
            ->get()->getResultObject();
    }

    /**
     * Get a single instance with its widget folder and name.
     */
    public function getWithWidget(int $instanceId): ?object
    {
        return $this->db->table('widget_instances wi')
            ->select('wi.*, w.folder, w.name AS widget_name')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();
    }

    /**
     * Get the area slug for a given instance.
     */
    public function getAreaSlug(int $instanceId): ?string
    {
        $row = $this->db->table('widget_instances wi')
            ->select('wa.slug')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();
        return $row ? $row->slug : null;
    }

    /**
     * Get instance with widget folder (for config save).
     */
    public function getWidgetFolder(int $instanceId): ?string
    {
        $row = $this->db->table('widget_instances wi')
            ->select('w.folder')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();
        return $row ? $row->folder : null;
    }
}
