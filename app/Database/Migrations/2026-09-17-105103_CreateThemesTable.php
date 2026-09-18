<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateThemesTable extends Migration
{
    public function change(): void
    {
        $this->table('themes')
            ->addColumn('id', 'primary', [])
            ->addColumn('name', 'string', ['length' => 100])
            ->addColumn('folder', 'string', ['length' => 100])
            ->addColumn('description', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('version', 'string', ['length' => 20, 'nullable' => true, 'default' => null])
            ->addColumn('author', 'string', ['length' => 100, 'nullable' => true, 'default' => null])
            ->addColumn('screenshot', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('is_active', 'tinyint', ['default' => 0])
            ->addColumn('disabled', 'tinyint', ['nullable' => true, 'default' => null])
            ->addColumn('disabled_reason', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('installed_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['folder'], ['unique' => true])
            ->addIndex(['is_active'])
            ->create();
    }
}
