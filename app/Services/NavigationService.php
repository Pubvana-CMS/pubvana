<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Pubvana\Models\NavigationItem;
use flight\Engine;

/**
 * NavigationService - Business logic for navigation menu management.
 *
 * Handles CRUD operations, tree building for nested menus, and
 * linkable item discovery from other plugins. Used by both the admin
 * controller (CRUD) and the public side (rendering).
 *
 * @package Pubvana\Services
 */
class NavigationService
{

    /** @var Engine<object> The FlightPHP app instance */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Get a nested tree of navigation items for a group.
     *
     * Builds a recursive parent/child structure suitable for
     * rendering dropdown menus. Top-level items have parent_id = null,
     * children are nested under ->children arrays.
     *
     * @param string $group Navigation group (e.g. 'primary', 'footer')
     * @return list<\Pubvana\Models\NavigationItem> Nested tree, children attached to each item
     */
    public function getTree(string $group = 'primary'): array
    {
        $flat = $this->model()->getByGroup($group);
        return $this->buildTree($flat);
    }

    /**
     * Get flat list of items for a group.
     *
     * @param string $group Navigation group identifier
     * @return NavigationItem[] Flat list ordered by sort_order
     */
    public function getByGroup(string $group = 'primary'): array
    {
        return $this->model()->getByGroup($group);
    }

    /**
     * Get all navigation groups that have at least one item.
     *
     * @return string[] Unique group identifiers
     */
    public function getGroups(): array
    {
        $model = $this->model();
        $all = $model->order('nav_group ASC')->findAll();

        $groups = [];
        foreach ($all as $item) {
            $groups[$item->nav_group] = true;
        }

        return array_keys($groups);
    }

    /**
     * Create a new navigation item.
     *
     * Auto-calculates sort_order if not provided. Sets timestamps.
     *
     * @param array<string, mixed> $data Item data (label, url, nav_group, parent_id, target)
     * @return \Pubvana\Models\NavigationItem The created item
     */
    public function create(array $data): \Pubvana\Models\NavigationItem
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $group = $data['nav_group'] ?? 'primary';

        $item = $this->model();
        $item->label      = $data['label'] ?? '';
        $item->url        = $data['url'] ?? '/';
        $item->parent_id  = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $item->sort_order = (int) ($data['sort_order'] ?? $this->model()->nextSortOrder($group));
        $item->target     = $data['target'] ?? '_self';
        $item->nav_group  = $group;
        $item->created_at = $now;
        $item->updated_at = $now;
        $item->insert();

        return $item;
    }

    /**
     * Delete a navigation item and re-parent its children to top level.
     *
     * Children of the deleted item are promoted to the same group
     * level (parent_id set to null) so they are not orphaned.
     *
     * @param int $id Navigation item primary key
     * @return bool True if deleted, false if item not found
     */
    public function delete(int $id): bool
    {
        $item = $this->model()->findById($id);
        if ($item === null) {
            return false;
        }

        // Re-parent children to top level within the same group
        $children = $this->model()->getChildren($id, $item->nav_group);
        foreach ($children as $child) {
            $child->parent_id = null;
            $child->save();
        }

        $item->delete();
        return true;
    }

    /**
     * Reorder items by an array of IDs in desired order.
     *
     * Each ID's position in the array becomes its sort_order value.
     *
     * @param int[] $ids Ordered array of item IDs
     */
    public function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            $item = $this->model()->findById((int) $id);
            if ($item !== null) {
                $item->sort_order = $index;
                $item->save();
            }
        }
    }

    /**
     * Get linkable items from all plugins for the Quick Add feature.
     *
     * Collects items from plugins that registered nav.linkable via adext.
     * Always includes a Core group with Home and Blog links.
     *
     * @return array<string, array<int, array{label: string, url: string}>> Grouped linkable items
     */
    public function getLinkableItems(): array
    {
        $result = [];

        // Core routes always available
        $result['Core'] = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Blog', 'url' => '/blog'],
        ];

        // Collect from plugins that registered nav.linkable
        $adext = $this->app->adext();
        $contributions = $adext->get('nav.linkable', 'default');
        foreach ($contributions as $key => $contribution) {
            if (isset($contribution['callable']) && is_callable($contribution['callable'])) {
                $items = ($contribution['callable'])();
                if (!empty($items)) {
                    $groupLabel = $contribution['label'] ?? $key;
                    $result[$groupLabel] = $items;
                }
            }
        }

        return $result;
    }

    // -----------------------------------------------------------------
    // Tree Building
    // -----------------------------------------------------------------

    /**
     * Build a nested tree from a flat array of NavigationItem objects.
     *
     * Items with parent_id = 0 or null are top-level. Each item gets
     * a ->children array of its direct descendants.
     *
     * @param \Pubvana\Models\NavigationItem[] $flat     Flat list of items (already sorted by sort_order)
     * @param int              $parentId Parent ID to filter for (0 = top-level)
     * @return list<\Pubvana\Models\NavigationItem> Nested tree
     */
    protected function buildTree(array $flat, int $parentId = 0): array
    {
        $tree = [];

        foreach ($flat as $item) {
            $pid = $item->parent_id ?? 0;
            if ((int) $pid === $parentId) {
                $clone = clone $item;
                $clone->children = $this->buildTree($flat, (int) $item->id);
                $tree[] = $clone;
            }
        }

        return $tree;
    }

    // -----------------------------------------------------------------
    // Private Helpers
    // -----------------------------------------------------------------

    /**
     * Get a new NavigationItem model instance.
     *
     * @return NavigationItem
     */
    protected function model(): NavigationItem
    {
        return new NavigationItem($this->app->db());
    }
}
