<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreatePagesTable - Migration for the pages plugin.
 *
 * Creates the pages table for static page content.
 *
 * @package Pubvana\Plugins\Pages\Database\Migrations
 */
class CreatePagesTable extends Migration
{
    public function change(): void
    {
        $this->table('pages')
            ->addColumn('id', 'primary')
            ->addColumn('title', 'string', ['length' => 255])
            ->addColumn('slug', 'string', ['length' => 255])
            ->addColumn('content', 'text', ['nullable' => true])
            ->addColumn('status', 'string', ['length' => 20, 'default' => 'draft'])
            ->addColumn('allow_comments', 'tinyint', ['default' => 0])
            ->addColumn('ai_generated', 'integer', ['default' => 0])
            ->addColumn('created_by', 'integer', ['default' => 0])
            ->addColumn('created_at', 'datetime', ['nullable' => true])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addColumn('deleted_at', 'datetime', ['nullable' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])
            ->addIndex(['deleted_at'])
            ->create();
    }
}
