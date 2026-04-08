<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MigrateAndCleanMarketplaceItems extends Migration
{
    public function up(): void
    {
        // Move licensed rows into the new table
        $rows = $this->db->table('marketplace_items')
            ->where('license_key IS NOT NULL')
            ->where('license_key !=', '')
            ->get()->getResult();

        foreach ($rows as $row) {
            $this->db->table('marketplace_licenses')->insert([
                'license_key'          => $row->license_key,
                'product_slug'         => $row->slug,
                'product_name'         => $row->name,
                'item_type'            => $row->item_type,
                'is_subscription'      => 0,
                'license_valid'        => $row->license_valid,
                'license_last_checked' => $row->license_last_checked,
                'installed_version'    => $row->installed_version,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);
        }

        // Drop license columns from marketplace_items
        $this->forge->dropColumn('marketplace_items', ['license_key', 'license_valid', 'license_last_checked']);
    }

    public function down(): void
    {
        // Re-add columns
        $this->forge->addColumn('marketplace_items', [
            'license_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'installed_version',
            ],
            'license_last_checked' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'license_key',
            ],
            'license_valid' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'default'    => null,
                'after'      => 'license_last_checked',
            ],
        ]);

    }
}
