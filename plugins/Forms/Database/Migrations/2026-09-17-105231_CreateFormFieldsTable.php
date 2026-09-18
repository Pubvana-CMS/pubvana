<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateFormFieldsTable extends Migration
{
    public function up(): void
    {
        $this->table('form_fields')
            ->addColumn('id', 'primary', [])
            ->addColumn('form_id', 'integer', ['unsigned' => true])
            ->addColumn('type', 'string', ['length' => 50])
            ->addColumn('name', 'string', ['length' => 100])
            ->addColumn('label', 'string', ['length' => 255])
            ->addColumn('help_text', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('placeholder', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('is_required', 'tinyint', ['default' => 0])
            ->addColumn('width', 'string', ['length' => 20, 'default' => 'full'])
            ->addColumn('options_json', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('sort_order', 'integer', ['default' => 1])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['form_id'])
            ->addIndex(['form_id', 'sort_order'])
            ->create();
    }

    public function down(): void
    {
        $this->table('form_fields')->drop();
    }
}
