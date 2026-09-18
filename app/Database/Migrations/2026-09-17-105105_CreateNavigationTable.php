<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreateNavigationTable - Migration for the navigation menu system.
 *
 * Stores navigation items organized into logical groups (primary, footer,
 * sidebar, etc.). Items can be nested via parent_id to form tree
 * structures for dropdown menus.
 *
 * Schema:
 *   id         - Auto-increment primary key
 *   label      - Display text for the link (max 100 chars)
 *   url        - Target URL (max 500 chars)
 *   parent_id  - FK to self for nesting (null = top-level)
 *   sort_order - Position within the group/level (lower = first)
 *   target     - Link target attribute (_self or _blank)
 *   nav_group  - Logical group this item belongs to
 *   created_at - Row creation timestamp
 *   updated_at - Last modification timestamp
 *
 * @package Pubvana\Database\Migrations
 */
class CreateNavigationTable extends Migration
{
    public function up(): void
    {
        $this->table('navigation')
            ->addColumn('id', 'primary')
            ->addColumn('label', 'string', ['length' => 100])
            ->addColumn('url', 'string', ['length' => 500])
            ->addColumn('parent_id', 'integer', ['unsigned' => true, 'nullable' => true, 'default' => null])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('target', 'string', ['length' => 20, 'default' => '_self'])
            ->addColumn('nav_group', 'string', ['length' => 50, 'default' => 'primary'])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['nav_group'])
            ->addIndex(['parent_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('navigation')->drop();
    }
}
