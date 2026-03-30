<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPluginToMarketplaceItemType extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('marketplace_items', [
            'item_type' => [
                'type'       => 'ENUM',
                'constraint' => ['theme', 'widget', 'plugin', 'premium_core'],
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('marketplace_items', [
            'item_type' => [
                'type'       => 'ENUM',
                'constraint' => ['theme', 'widget'],
            ],
        ]);
    }
}
