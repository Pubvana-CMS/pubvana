<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStoreProductIdToExtensionTables extends Migration
{
    private array $tables = ['themes', 'widgets', 'plugins'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->forge->addColumn($table, [
                'store_product_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'folder',
                ],
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $this->forge->dropColumn($table, 'store_product_id');
        }
    }
}
