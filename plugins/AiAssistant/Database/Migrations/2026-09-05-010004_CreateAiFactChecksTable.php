<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateAiFactChecksTable extends Migration
{
    public function up(): void
    {
        $this->table('ai_fact_checks')
            ->addColumn('id', 'primary', [])
            ->addColumn('content_type', 'string', ['length' => 10])
            ->addColumn('content_id', 'integer', [])
            ->addColumn('content_title', 'string', ['length' => 255, 'default' => ''])
            ->addColumn('content_slug', 'string', ['length' => 190, 'default' => ''])
            ->addColumn('content_updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('summary', 'text', [])
            ->addColumn('overall_verdict', 'string', ['length' => 24])
            ->addColumn('claim_count', 'integer', ['default' => 0])
            ->addColumn('claims', 'text', [])
            ->addColumn('prompt_version', 'string', ['length' => 24])
            ->addColumn('prompt_interference', 'boolean', ['default' => false])
            ->addColumn('interference_note', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('key_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('key_name', 'string', ['length' => 120, 'nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['content_type', 'content_id'], [])
            ->addIndex(['created_at'], [])
            ->create();
    }

    public function down(): void
    {
        $this->table('ai_fact_checks')->drop();
    }
}
