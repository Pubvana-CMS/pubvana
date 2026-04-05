<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMediaIdToPosts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('posts', [
            'media_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'featured_image',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('posts', 'media_id');
    }
}
