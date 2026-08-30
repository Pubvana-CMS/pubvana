<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * NavigationItem - ActiveRecord model for the navigation table.
 *
 * Each row represents one link in a navigation menu. Items are organized
 * into groups (primary, footer, sidebar, etc.) and can be nested via
 * parent_id to form tree structures (dropdown menus, subnavs).
 *
 * Fields:
 *   - id: Auto-increment primary key
 *   - label: Display text for the link (max 100 chars)
 *   - url: Target URL (max 500 chars)
 *   - parent_id: FK to self for nesting (null = top-level)
 *   - sort_order: Position within the group/level (lower = first)
 *   - target: Link target attribute (_self or _blank)
 *   - nav_group: Logical group this item belongs to (primary, footer, sidebar)
 *   - created_at: Row creation timestamp
 *   - updated_at: Last modification timestamp
 *
 * @package Pubvana\Models
 */
class NavigationItem extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'navigation', $config);
    }

    public int $id;
    public string $label = '';
    public string $url = '/';
    public ?int $parent_id = null;
    public int $sort_order = 0;
    public string $target = '_self';
    public string $nav_group = 'primary';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // -----------------------------------------------------------------
    // Query Methods
    // -----------------------------------------------------------------

    /**
     * Get all items for a specific navigation group, ordered by sort_order.
     *
     * @param string $group Navigation group identifier (e.g. 'primary', 'footer')
     * @return self[] Items ordered by sort_order ascending
     */
    public function getByGroup(string $group): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->eq('nav_group', $group)
                     ->order('sort_order ASC')
                     ->findAll();
    }

    /**
     * Find a single item by ID.
     *
     * @param int $id Navigation item primary key
     * @return self|null Hydrated model, or null if not found
     */
    public function findById(int $id): ?self
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('id', $id)->find();

        return $model->isHydrated() ? $model : null;
    }

    /**
     * Calculate the next sort_order value for a group.
     *
     * Returns one more than the current highest sort_order in the group,
     * or 0 if the group is empty.
     *
     * @param string $group Navigation group identifier
     * @return int Next sort_order value
     */
    public function nextSortOrder(string $group): int
    {
        $model = new self($this->getDatabaseConnection());
        $result = $model->select('MAX(sort_order) as max_sort')
                        ->eq('nav_group', $group)
                        ->find();

        return ((int) ($result->max_sort ?? 0)) + 1;
    }

    /**
     * Get all direct children of a specific parent within a group.
     *
     * @param int    $parentId Parent item ID (0 for top-level)
     * @param string $group    Navigation group identifier
     * @return self[] Child items ordered by sort_order ascending
     */
    public function getChildren(int $parentId, string $group): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->eq('parent_id', $parentId)
                     ->eq('nav_group', $group)
                     ->order('sort_order ASC')
                     ->findAll();
    }
}
