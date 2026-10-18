<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightShield\Result;
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

    /** @var int Longest label the navigation.label column accepts */
    protected const MAX_LABEL = 100;

    /** @var int Longest URL the navigation.url column accepts */
    protected const MAX_URL = 500;

    /** @var list<string> Link targets the admin offers */
    protected const TARGETS = ['_self', '_blank'];

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
        $flat = $this->getByGroup($group);
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
        $items = $this->model()->getByGroup($group);

        // Sanitize on the way out too, so a row stored before the write-side
        // check (or edited by hand) can never reach the theme's href as-is.
        foreach ($items as $item) {
            $item->url = $this->safeUrl($item->url);
        }

        return $items;
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
     * Find one navigation item by its primary key.
     *
     * @param int $id Navigation item primary key
     * @return NavigationItem|null Hydrated item, or null if not found
     */
    public function find(int $id): ?NavigationItem
    {
        return $this->model()->findById($id);
    }

    /**
     * Reduce a stored navigation URL to a safe href.
     *
     * Allows root-relative paths, in-page anchors, and the http, https,
     * mailto, and tel schemes. Anything with another scheme (javascript:,
     * data:, and the rest) becomes '#', so a stored value can never run
     * script when the theme renders it into an href.
     */
    public function safeUrl(?string $url): string
    {
        $candidate = trim((string) ($url ?? ''));

        if ($candidate === '') {
            return '#';
        }

        // Control characters and backslashes have no place in an href.
        if (str_contains($candidate, '\\') || preg_match('/[\x00-\x1f\x7f]/', $candidate) === 1) {
            return '#';
        }

        $scheme = strtolower((string) (parse_url($candidate, PHP_URL_SCHEME) ?? ''));

        // No scheme is a relative URL (/blog, #anchor, blog/post): allowed.
        if ($scheme === '') {
            return $candidate;
        }

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) ? $candidate : '#';
    }

    /**
     * Create a new navigation item.
     *
     * Validates the label, URL, and target, auto-calculates sort_order when
     * it is not supplied, and sets both timestamps.
     *
     * @param array<string, mixed> $data Item data (label, url, nav_group, parent_id, target)
     * @return Result Success carrying the new item, or the first problem found
     */
    public function create(array $data): Result
    {
        $problem = $this->validate($data);
        if ($problem !== null) {
            return $this->deny($problem);
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $group = (string) ($data['nav_group'] ?? 'primary');

        $item = $this->model();
        $item->label      = trim((string) ($data['label'] ?? ''));
        $item->url        = $this->safeUrl((string) ($data['url'] ?? ''));
        $item->parent_id  = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $item->sort_order = (int) ($data['sort_order'] ?? $this->model()->nextSortOrder($group));
        $item->target     = (string) ($data['target'] ?? '_self');
        $item->nav_group  = $group;
        $item->created_at = $now;
        $item->updated_at = $now;
        $item->insert();

        return $this->ok($item);
    }

    /**
     * Update the editable fields of a navigation item.
     *
     * Label, URL, and target change. Parent, group, and sort order keep
     * their stored values.
     *
     * @param int                  $id   Navigation item primary key
     * @param array<string, mixed> $data Submitted fields
     * @return Result Success carrying the saved item, or the first problem found
     */
    public function update(int $id, array $data): Result
    {
        $item = $this->model()->findById($id);
        if ($item === null) {
            return $this->deny('That navigation item no longer exists.');
        }

        $problem = $this->validate($data);
        if ($problem !== null) {
            return $this->deny($problem);
        }

        // Direct writes to the model's typed properties land in update()
        // through syncDirtyFromProperties(), which compares them against the
        // hydrated row.
        $item->label      = trim((string) ($data['label'] ?? ''));
        $item->url        = $this->safeUrl((string) ($data['url'] ?? ''));
        $item->target     = (string) ($data['target'] ?? '_self');
        $item->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $item->save();

        return $this->ok($item);
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
     * Check the fields both write paths share.
     *
     * @param array<string, mixed> $data Submitted fields
     * @return string|null Problem message, or null when the data is usable
     */
    protected function validate(array $data): ?string
    {
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            return 'A label is required.';
        }

        if (mb_strlen($label) > self::MAX_LABEL) {
            return 'The label is limited to ' . self::MAX_LABEL . ' characters.';
        }

        $url = trim((string) ($data['url'] ?? ''));
        if ($url === '') {
            return 'A URL is required.';
        }

        if (mb_strlen($url) > self::MAX_URL) {
            return 'The URL is limited to ' . self::MAX_URL . ' characters.';
        }

        $target = (string) ($data['target'] ?? '_self');
        if (!in_array($target, self::TARGETS, true)) {
            return 'The link target must open in the same window or a new one.';
        }

        return null;
    }

    /**
     * Build a failed Result.
     */
    protected function deny(string $reason): Result
    {
        return (new Result())->setSuccess(false)->setReason($reason);
    }

    /**
     * Build a successful Result.
     *
     * @param NavigationItem|null $item Saved item, when there is one
     */
    protected function ok(?NavigationItem $item = null): Result
    {
        return (new Result())->setSuccess(true)->setExtraInfo($item);
    }

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
