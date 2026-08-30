<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateThemeOptionsTable extends Migration
{
    public function change(): void
    {
        $this->table('theme_options')
            ->addColumn('id', 'primary', [])
            ->addColumn('theme_id', 'integer', ['unsigned' => true])
            ->addColumn('option_key', 'string', ['length' => 100])
            ->addColumn('option_value', 'text', ['nullable' => true, 'default' => null])
            ->addIndex(['theme_id'])
            ->addIndex(['theme_id', 'option_key'], ['unique' => true])
            ->create();
    }
}
