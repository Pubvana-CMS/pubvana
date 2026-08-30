<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateRedirectLinksTable extends Migration
{
    public function up(): void
    {
        $this->table('redirects_links')
            ->addColumn('id', 'primary', [])
            ->addColumn('source_path', 'string', ['length' => 255])
            ->addColumn('hit_count', 'integer', ['default' => 0])
            ->addColumn('last_query_string', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('last_referrer', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('last_user_agent', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('ignored', 'boolean', ['default' => false])
            ->addColumn('resolved_redirect_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('resolved_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('first_seen_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('last_seen_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['source_path'], ['unique' => true])
            ->addIndex(['ignored'])
            ->addIndex(['resolved_redirect_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('redirects_links')->drop();
    }
}
