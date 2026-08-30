<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Comments\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreateCommentsTable - Migration for the comments table.
 *
 * Supports nested (threaded) comments via parent_id, attribution to either
 * a registered user (user_id) or a guest (guest_name/guest_email/guest_website),
 * and moderation via the status column.
 */
class CreateCommentsTable extends Migration
{
    public function up(): void
    {
        $this->table('comments')
            ->addColumn('id', 'primary', [])
            ->addColumn('commentable_type', 'string', ['length' => 50])
            ->addColumn('commentable_id', 'integer', [])
            ->addColumn('parent_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('user_id', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('guest_name', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('guest_email', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('guest_website', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('body', 'text', [])
            ->addColumn('status', 'string', ['length' => 20, 'default' => 'pending'])
            ->addColumn('ip_address', 'string', ['length' => 45, 'nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['commentable_type', 'commentable_id'])
            ->addIndex(['parent_id'])
            ->addIndex(['status'])
            ->addIndex(['user_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('comments')->drop();
    }
}
