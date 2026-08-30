<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreatePostsTable extends Migration
{
    public function up(): void
    {
        $this->table('posts')
            ->addColumn('id', 'primary', [])
            ->addColumn('title', 'string', ['length' => 255])
            ->addColumn('slug', 'string', ['length' => 255])
            ->addColumn('content', 'longtext', ['nullable' => true, 'default' => null])
            ->addColumn('excerpt', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('status', 'string', ['length' => 20, 'default' => 'draft'])
            ->addColumn('featured_image', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('media_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('author_id', 'integer', [])
            ->addColumn('published_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('views', 'integer', ['default' => 0])
            ->addColumn('is_featured', 'tinyint', ['default' => 0])
            ->addColumn('allow_comments', 'tinyint', ['default' => 1])
            ->addColumn('ai_generated', 'integer', ['default' => 0])
            ->addColumn('preview_token', 'string', ['length' => 64, 'nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('deleted_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['preview_token'], ['unique' => true])
            ->addIndex(['author_id'])
            ->addIndex(['status'])
            ->create();
    }

    public function down(): void
    {
        $this->table('posts')->drop();
    }
}
