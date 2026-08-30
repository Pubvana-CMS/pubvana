<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateTagsToPostsTable extends Migration
{
    public function up(): void
    {
        $this->table('tags_to_posts')
            ->addColumn('tag_id', 'integer', [])
            ->addColumn('post_id', 'integer', [])
            ->addIndex(['tag_id', 'post_id'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('tags_to_posts')->drop();
    }
}
