<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateAiKeyGrantsTable extends Migration
{
    public function up(): void
    {
        $this->table('ai_key_grants')
            ->addColumn('id', 'primary', [])
            ->addColumn('key_id', 'integer', [])
            ->addColumn('permission', 'string', ['length' => 100])
            ->addIndex(['key_id'])
            ->addIndex(['key_id', 'permission'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('ai_key_grants')->drop();
    }
}