<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCapabilitiesToPlugins extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('plugins', [
            'capabilities' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'folder',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('plugins', 'capabilities');
    }
}
