<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * AllowRepeatedBlocksPerRegion - drops the one-block-per-type limit.
 *
 * CreateBlockPlacementsTable put a unique index on (region_id, block_key),
 * so a block type could appear once per region. A placement is already a
 * full instance with its own sort_order and options JSON, so repeats
 * render and configure independently; the unique index is the only thing
 * that stops a second instance. Nothing replaces it: region reads ride
 * the region_id index.
 */
class AllowRepeatedBlocksPerRegion extends Migration
{
    public function up(): void
    {
        $this->table('block_placements')->dropIndex(['region_id', 'block_key']);
    }

    public function down(): void
    {
        $this->table('block_placements')->statement(
            'ALTER TABLE `block_placements` ADD UNIQUE KEY `idx_block_placements_region_id_block_key` (`region_id`, `block_key`)'
        );
    }
}
