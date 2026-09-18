<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateSeoMetaTable extends Migration
{
    public function up(): void
    {
        $this->table('seo_meta')
            ->addColumn('id', 'primary', [])
            ->addColumn('content_type', 'string', ['length' => 50])
            ->addColumn('content_id', 'integer', ['unsigned' => true])
            ->addColumn('meta_title', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('meta_description', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('canonical_url', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('robots_directive', 'string', ['length' => 50, 'nullable' => true, 'default' => null])
            ->addColumn('focus_keywords', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('og_title', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('og_description', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('og_image', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('og_type', 'string', ['length' => 50, 'nullable' => true, 'default' => null])
            ->addColumn('twitter_card', 'string', ['length' => 50, 'nullable' => true, 'default' => null])
            ->addColumn('schema_type', 'string', ['length' => 50, 'nullable' => true, 'default' => null])
            ->addColumn('seo_score', 'integer', ['nullable' => true, 'default' => null])
            ->addColumn('hreflang', 'string', ['length' => 10, 'nullable' => true, 'default' => 'en'])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['content_type', 'content_id'], ['unique' => true])
            ->addIndex(['content_type'])
            ->create();
    }

    public function down(): void
    {
        $this->table('seo_meta')->drop();
    }
}
