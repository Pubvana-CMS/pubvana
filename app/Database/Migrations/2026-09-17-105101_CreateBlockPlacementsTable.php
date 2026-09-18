<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreateBlockPlacementsTable - Migration for the region/block system.
 *
 * Stores block placement configurations per region. Each row represents
 * one block placed in one region, with a sort order and JSON options.
 * Regions are strings (e.g. 'sidebar', 'footer-col-1') so they can
 * be defined by both the platform and themes without a foreign key.
 *
 * Schema:
 *   id            - Auto-increment primary key
 *   region_id     - Region string identifier (e.g. 'sidebar')
 *   block_key     - Block key from adext (e.g. 'pubvana.blog.recent-posts')
 *   sort_order    - Display order within the region (lower = higher)
 *   options       - JSON blob of block-specific configuration
 *   created_at    - When this placement was created
 *
 * @package Pubvana\Database\Migrations
 */
class CreateBlockPlacementsTable extends Migration
{
    /**
     * Create the block_placements table.
     *
     * UNIQUE constraint on (region_id, block_key) prevents the same block
     * from being placed in the same region twice. INDEX on region_id for
     * fast lookups when rendering a region.
     */
    public function change(): void
    {
        $this->table('block_placements')
            ->addColumn('id', 'primary')
            ->addColumn('region_id', 'string', ['length' => 50])
            ->addColumn('block_key', 'string', ['length' => 100])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('options', 'json', ['nullable' => true])
            ->addColumn('created_at', 'datetime', ['nullable' => true])
            ->addIndex(['region_id', 'block_key'], ['unique' => true])
            ->addIndex(['region_id'])
            ->create();
    }
}
