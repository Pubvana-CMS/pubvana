<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateAnalyticsDailyTables extends Migration
{
    public function up(): void
    {
        $this->table('analytics_views_daily')
            ->addColumn('id', 'primary', [])
            ->addColumn('day', 'string', ['length' => 10])
            ->addColumn('page_group', 'string', ['length' => 50])
            ->addColumn('page_path', 'string', ['length' => 255])
            ->addColumn('view_count', 'integer', ['default' => 0])
            ->addIndex(['day', 'page_group', 'page_path'], ['unique' => true])
            ->addIndex(['day'])
            ->create();

        $this->table('analytics_referrers_daily')
            ->addColumn('id', 'primary', [])
            ->addColumn('day', 'string', ['length' => 10])
            ->addColumn('referrer_domain', 'string', ['length' => 255])
            ->addColumn('view_count', 'integer', ['default' => 0])
            ->addIndex(['day', 'referrer_domain'], ['unique' => true])
            ->addIndex(['day'])
            ->create();
    }

    public function down(): void
    {
        $this->table('analytics_referrers_daily')->drop();
        $this->table('analytics_views_daily')->drop();
    }
}