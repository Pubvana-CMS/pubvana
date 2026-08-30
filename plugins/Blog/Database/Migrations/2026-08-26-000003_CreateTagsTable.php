<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateTagsTable extends Migration
{
    public function up(): void
    {
        $this->table('tags')
            ->addColumn('id', 'primary', [])
            ->addColumn('name', 'string', ['length' => 100])
            ->addColumn('slug', 'string', ['length' => 100])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('tags')->drop();
    }
}
