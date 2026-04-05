<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SwitchMarketplaceLicensesToProductId extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('marketplace_licenses', [
            'store_product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'product_slug',
            ],
        ]);

        // No backfill needed - store isn't selling yet
        $this->forge->dropColumn('marketplace_licenses', 'product_slug');
    }

    public function down(): void
    {
        $this->forge->addColumn('marketplace_licenses', [
            'product_slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'store_product_id',
            ],
        ]);
        $this->forge->dropColumn('marketplace_licenses', 'store_product_id');
    }
}
