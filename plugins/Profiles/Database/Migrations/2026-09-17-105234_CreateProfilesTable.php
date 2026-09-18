<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateProfilesTable extends Migration
{
    public function up(): void
    {
        $this->table('profiles')
            ->addColumn('id', 'primary', [])
            ->addColumn('user_id', 'integer', ['unsigned' => true])
            ->addColumn('display_name', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('bio', 'text', ['nullable' => true, 'default' => null])
            ->addColumn('avatar', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('website', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('twitter', 'string', ['length' => 100, 'nullable' => true, 'default' => null])
            ->addColumn('facebook', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('linkedin', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('job_title', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('works_for', 'string', ['length' => 255, 'nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['user_id'], ['unique' => true])
            ->addForeignKey(['user_id'], 'users', ['id'], ['delete' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('profiles')->drop();
    }
}
