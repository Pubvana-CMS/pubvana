<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreatePostRevisionsTable extends Migration
{
    public function up(): void
    {
        $this->table('post_revisions')
            ->addColumn('id', 'primary', [])
            ->addColumn('post_id', 'integer', [])
            ->addColumn('author_id', 'integer', [])
            ->addColumn('title', 'string', ['length' => 255])
            ->addColumn('content', 'longtext', ['nullable' => true, 'default' => null])
            ->addColumn('excerpt', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('status', 'string', ['length' => 20])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['post_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('post_revisions')->drop();
    }
}
