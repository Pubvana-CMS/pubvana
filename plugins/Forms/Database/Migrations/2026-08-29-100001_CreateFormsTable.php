<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateFormsTable extends Migration
{
    public function up(): void
    {
        $this->table('forms')
            ->addColumn('id', 'primary', [])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('slug', 'string', ['length' => 255])
            ->addColumn('description', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published'], 'default' => 'draft'])
            ->addColumn('submit_label', 'string', ['length' => 100, 'default' => 'Submit'])
            ->addColumn('success_message', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('notification_emails', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('deleted_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])
            ->create();
    }

    public function down(): void
    {
        $this->table('forms')->drop();
    }
}
