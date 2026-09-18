<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

class CreateFormSubmissionsTable extends Migration
{
    public function up(): void
    {
        $this->table('form_submissions')
            ->addColumn('id', 'primary', [])
            ->addColumn('form_id', 'integer', ['unsigned' => true])
            ->addColumn('status', 'string', ['length' => 50, 'default' => 'received'])
            ->addColumn('ip_address', 'string', ['length' => 64, 'nullable' => true, 'default' => null])
            ->addColumn('user_agent', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('referrer_url', 'string', ['length' => 500, 'nullable' => true, 'default' => null])
            ->addColumn('payload_json', 'longtext', ['nullable' => true, 'default' => null])
            ->addColumn('submitted_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addColumn('updated_at', 'datetime', ['nullable' => true, 'default' => null])
            ->addIndex(['form_id'])
            ->addIndex(['status'])
            ->create();
    }

    public function down(): void
    {
        $this->table('form_submissions')->drop();
    }
}
