<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateRedirectsTable extends Migration
{
    public function up(): void
    {
        $this->table('redirects')
            ->addColumn('id', 'primary', [])
            ->addColumn('source_path', 'string', ['length' => 255])
            ->addColumn('target_url', 'string', ['length' => 1000])
            ->addColumn('status_code', 'integer', ['default' => 301])
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->addColumn('notes', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('hit_count', 'integer', ['default' => 0])
            ->addColumn('last_hit_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['source_path'], ['unique' => true])
            ->addIndex(['enabled'])
            ->create();
    }

    public function down(): void
    {
        $this->table('redirects')->drop();
    }
}
