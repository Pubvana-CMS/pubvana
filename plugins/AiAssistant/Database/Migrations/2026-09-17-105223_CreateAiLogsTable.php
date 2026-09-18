<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateAiLogsTable extends Migration
{
    public function up(): void
    {
        $this->table('ai_logs')
            ->addColumn('id', 'primary', [])
            ->addColumn('key_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('key_name', 'string', ['length' => 120, 'nullable' => true, 'default' => null])
            ->addColumn('method', 'string', ['length' => 10])
            ->addColumn('endpoint', 'string', ['length' => 500])
            ->addColumn('entity_type', 'string', ['length' => 50, 'nullable' => true, 'default' => null])
            ->addColumn('entity_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('outcome', 'string', ['length' => 10])
            ->addColumn('detail', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('ip', 'string', ['length' => 45, 'nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['key_id'])
            ->addIndex(['created_at'])
            ->create();
    }

    public function down(): void
    {
        $this->table('ai_logs')->drop();
    }
}