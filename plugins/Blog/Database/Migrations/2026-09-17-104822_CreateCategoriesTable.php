<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->table('categories')
            ->addColumn('id', 'primary', [])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('slug', 'string', ['length' => 255])
            ->addColumn('description', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('parent_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('categories')->drop();
    }
}
