<?php

declare(strict_types=1);

namespace Pubvana\Plugins\ActivityLog\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateActivityLogsTable extends Migration
{
    public function up(): void
    {
        $this->table('activity_logs')
            ->addColumn('id', 'primary', [])
            ->addColumn('user_id', 'integer', ['signed' => false, 'nullable' => true])
            ->addColumn('user_name', 'string', ['length' => 255])
            ->addColumn('action', 'string', ['length' => 50])
            ->addColumn('entity_type', 'string', ['length' => 100])
            ->addColumn('entity_id', 'integer', ['signed' => false, 'nullable' => true])
            ->addColumn('entity_name', 'string', ['length' => 255])
            ->addColumn('details', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('ip', 'string', ['length' => 45])
            ->addColumn('user_agent', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['user_id'])
            ->addIndex(['action'])
            ->addIndex(['entity_type', 'entity_id'])
            ->addIndex(['created_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('activity_logs')->drop();
    }
}