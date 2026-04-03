<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMarketplaceLicensesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'license_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'product_slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'product_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'item_type' => [
                'type'       => 'ENUM',
                'constraint' => ['theme', 'widget', 'plugin'],
            ],
            'registered_domain' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'is_subscription' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'expires_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'subscription_renews_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'license_valid' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'default'    => null,
            ],
            'license_last_checked' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'installed_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
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
        $this->forge->addUniqueKey('license_key');
        $this->forge->addKey('product_slug');
        $this->forge->createTable('marketplace_licenses', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('marketplace_licenses', true);
    }
}
