<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminNotificationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'source' => [
                'type'       => 'ENUM',
                'constraint' => ['system', 'theme', 'widget', 'plugin'],
                'null'       => false,
            ],
            'source_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'severity' => [
                'type'       => 'ENUM',
                'constraint' => ['info', 'warning', 'error', 'success'],
                'null'       => false,
            ],
            'message' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'action_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'action_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'dismissed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('dismissed_at');

        $this->forge->createTable('admin_notifications', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_notifications', true);
    }
}
