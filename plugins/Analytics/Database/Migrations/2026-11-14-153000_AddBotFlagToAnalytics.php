<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * Splits bot hits from visitor hits so the report can chart them apart.
 *
 * Bot hits were dropped before insert. They are now stored with is_bot set,
 * and rolled into their own daily table, so every human total filters them
 * out and the chart can show them on a line of their own.
 */
class AddBotFlagToAnalytics extends Migration
{
    public function up(): void
    {
        $this->table('analytics_page_views')
            ->addColumn('is_bot', 'tinyint', ['default' => 0])
            ->update();

        $this->table('analytics_bots_daily')
            ->addColumn('id', 'primary', [])
            ->addColumn('day', 'date')
            ->addColumn('view_count', 'integer', ['default' => 0])
            ->addIndex(['day'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('analytics_bots_daily')->drop();
        $this->table('analytics_page_views')->dropColumns(['is_bot']);
    }
}
