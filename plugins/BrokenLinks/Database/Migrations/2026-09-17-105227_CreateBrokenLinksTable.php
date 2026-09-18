<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateBrokenLinksTable extends Migration
{
    public function up(): void
    {
        $this->table('broken_links')
            ->addColumn('id', 'primary', [])
            ->addColumn('source_type', 'string', ['length' => 10])
            ->addColumn('source_id', 'integer', ['signed' => false])
            ->addColumn('source_title', 'string', ['length' => 255])
            ->addColumn('url', 'text', [])
            ->addColumn('url_hash', 'string', ['length' => 40])
            ->addColumn('http_status', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('error_message', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('dismissed', 'boolean', ['default' => false])
            ->addColumn('last_checked_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['source_type', 'source_id', 'url_hash'], ['unique' => true])
            ->addIndex(['http_status'])
            ->addIndex(['dismissed'])
            ->create();
    }

    public function down(): void
    {
        $this->table('broken_links')->drop();
    }
}
