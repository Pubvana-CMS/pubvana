<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateMediaTable extends Migration
{
    public function up(): void
    {
        $this->table('media')
            ->addColumn('id', 'primary', [])
            ->addColumn('type', 'enum', ['values' => ['image', 'video', 'embed']])
            ->addColumn('filename', 'string', ['length' => 255])
            ->addColumn('path', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('mime_type', 'string', ['length' => 100, 'nullable' => true, 'default' => null])
            ->addColumn('size', 'integer', ['unsigned' => true, 'nullable' => true, 'default' => null])
            ->addColumn('alt_text', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('title', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('embed_url', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('embed_provider', 'string', ['length' => 50, 'nullable' => true, 'default' => null])
            ->addColumn('poster_path', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('uploaded_by', 'integer', ['unsigned' => true])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['type'])
            ->addIndex(['uploaded_by'])
            ->create();
    }

    public function down(): void
    {
        $this->table('media')->drop();
    }
}
