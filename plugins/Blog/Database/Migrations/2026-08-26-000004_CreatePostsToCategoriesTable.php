<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreatePostsToCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->table('posts_to_categories')
            ->addColumn('post_id', 'integer', [])
            ->addColumn('category_id', 'integer', [])
            ->addIndex(['post_id', 'category_id'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('posts_to_categories')->drop();
    }
}
