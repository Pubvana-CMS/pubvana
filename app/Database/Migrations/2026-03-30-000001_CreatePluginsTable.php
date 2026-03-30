<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePluginsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'folder' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '0.0.0',
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'pv_approved' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'default'    => null,
            ],
            'pv_warning_note' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'installed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('folder');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('plugins', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('plugins', true);
    }
}
