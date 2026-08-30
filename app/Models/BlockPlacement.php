<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * BlockPlacement - ActiveRecord model for the block_placements table.
 *
 * Represents a block placed in a specific region. Regions are string
 * identifiers defined by the platform (header, footer, sidebar, etc.)
 * or by themes. Blocks are registered via adext with type 'block'.
 *
 * Each placement stores:
 *   - region_id: which region this block lives in
 *   - block_key: which block (matches adext registration key)
 *   - sort_order: display position within the region
 *   - options: JSON configuration specific to this block instance
 *
 * @package Pubvana\Models
 */
class BlockPlacement extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'block_placements', $config);
    }

    public int $id;
    public string $region_id;
    public string $block_key;
    public int $sort_order = 0;
    public ?string $options = null;
    public ?string $created_at = null;

    // -----------------------------------------------------------------
    // Query Methods
    // -----------------------------------------------------------------

    /**
     * Get all placements for a specific region, ordered by sort_order.
     *
     * @param string $regionId Region identifier (e.g. 'sidebar')
     * @return self[] Placements ordered by sort_order ascending
     */
    public function getForRegion(string $regionId): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->eq('region_id', $regionId)
                     ->order('sort_order ASC')
                     ->findAll();
    }

    /**
     * Get all placements, ordered by region and sort order.
     *
     * @return self[] Flat list ordered by region_id ASC, sort_order ASC
     */
    public function getAll(): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->order('region_id ASC, sort_order ASC')->findAll();
    }

    /**
     * Find a specific placement by region and block key.
     *
     * @param string $regionId Region identifier
     * @param string $blockKey Block key (e.g. 'pubvana.blog.recent-posts')
     * @return self|null The placement, or null if not found
     */
    public function findPlacement(string $regionId, string $blockKey): ?self
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('region_id', $regionId)
              ->eq('block_key', $blockKey)
              ->find();

        return $model->isHydrated() ? $model : null;
    }

    /**
     * Get the next sort_order value for a region.
     *
     * Returns one more than the current highest sort_order in the region,
     * or 0 if the region is empty.
     *
     * @param string $regionId Region identifier
     * @return int Next sort_order value
     */
    public function nextSortOrder(string $regionId): int
    {
        $placements = $this->getForRegion($regionId);
        if (empty($placements)) {
            return 0;
        }
        return end($placements)->sort_order + 1;
    }

    // -----------------------------------------------------------------
    // Options (JSON)
    // -----------------------------------------------------------------

    /**
     * Decode and return the options JSON as an array.
     *
     * @return array<string, mixed> Decoded options, or empty array if null/invalid
     */
    public function getOptions(): array
    {
        if ($this->options === null || $this->options === '') {
            return [];
        }
        $decoded = json_decode($this->options, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode an array and store it as the options JSON.
     *
     * @param array<string, mixed> $options Options to store
     */
    public function setOptions(array $options): void
    {
        $this->options = json_encode($options);
    }
}
