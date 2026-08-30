<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreatePageViewsTable extends Migration
{
    public function up(): void
    {
        $this->table('analytics_page_views')
            ->addColumn('id', 'primary', [])
            ->addColumn('page_path', 'string', ['length' => 255])
            ->addColumn('page_group', 'string', ['length' => 50])
            ->addColumn('referrer_domain', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('viewed_at', 'datetime')
            ->addIndex(['page_path'])
            ->addIndex(['page_group'])
            ->addIndex(['viewed_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('analytics_page_views')->drop();
    }
}