<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateAiKeysTable extends Migration
{
    public function up(): void
    {
        $this->table('ai_keys')
            ->addColumn('id', 'primary', [])
            ->addColumn('name', 'string', ['length' => 120])
            ->addColumn('key_hash', 'string', ['length' => 64])
            ->addColumn('key_prefix', 'string', ['length' => 16])
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->addColumn('failed_attempts', 'integer', ['default' => 0])
            ->addColumn('blocked_until', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('last_used_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['key_hash'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('ai_keys')->drop();
    }
}