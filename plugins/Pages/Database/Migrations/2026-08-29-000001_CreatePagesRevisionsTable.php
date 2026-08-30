<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreatePagesRevisionsTable - Revision history for pages.
 *
 * @package Pubvana\Plugins\Pages\Database\Migrations
 */
class CreatePagesRevisionsTable extends Migration
{
    public function up(): void
    {
        $this->table('pages_revisions')
            ->addColumn('id', 'primary', [])
            ->addColumn('page_id', 'integer', [])
            ->addColumn('author_id', 'integer', [])
            ->addColumn('title', 'string', ['length' => 255])
            ->addColumn('content', 'longtext', ['nullable' => true, 'default' => null])
            ->addColumn('status', 'string', ['length' => 20])
            ->addColumn('allow_comments', 'tinyint', ['default' => 0])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['page_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('pages_revisions')->drop();
    }
}
