<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVettingColumns extends Migration
{
    public function up(): void
    {
        // Add author to plugins table
        $this->forge->addColumn('plugins', [
            'author' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'description',
            ],
        ]);

        // Add author, pv_approved, pv_warning_note to themes table
        $this->forge->addColumn('themes', [
            'author' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'version',
            ],
            'pv_approved' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'after'      => 'author',
            ],
            'pv_warning_note' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'pv_approved',
            ],
        ]);

        // Add author, pv_approved, pv_warning_note to widgets table
        $this->forge->addColumn('widgets', [
            'author' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'version',
            ],
            'pv_approved' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'after'      => 'author',
            ],
            'pv_warning_note' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'pv_approved',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('plugins', 'author');
        $this->forge->dropColumn('themes', ['author', 'pv_approved', 'pv_warning_note']);
        $this->forge->dropColumn('widgets', ['author', 'pv_approved', 'pv_warning_note']);
    }
}
