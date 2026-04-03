<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUpdateColumnsToExtensionTables extends Migration
{
    private array $tables = ['themes', 'widgets', 'plugins'];

    private array $columns = [
        'latest_version' => [
            'type'       => 'VARCHAR',
            'constraint' => 20,
            'null'       => true,
        ],
        'changelog' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'auto_update' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 0,
        ],
        'last_update_check' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
        'last_update_attempt' => [
            'type'       => 'ENUM',
            'constraint' => ['success', 'fail'],
            'null'       => true,
        ],
        'last_update_error' => [
            'type'       => 'VARCHAR',
            'constraint' => 500,
            'null'       => true,
        ],
        'last_updated_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->forge->addColumn($table, $this->columns);
        }
    }

    public function down(): void
    {
        $columnNames = array_keys($this->columns);
        foreach ($this->tables as $table) {
            $this->forge->dropColumn($table, $columnNames);
        }
    }
}
