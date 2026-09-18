<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SocialLinks\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateSocialLinksTable extends Migration
{
    public function up(): void
    {
        $this->table('social_links')
            ->addColumn('id', 'primary', [])
            ->addColumn('platform', 'string', ['length' => 50])
            ->addColumn('label', 'string', ['length' => 100])
            ->addColumn('url', 'string', ['length' => 500])
            ->addColumn('icon', 'string', ['length' => 100])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['is_active'])
            ->create();
    }

    public function down(): void
    {
        $this->table('social_links')->drop();
    }
}