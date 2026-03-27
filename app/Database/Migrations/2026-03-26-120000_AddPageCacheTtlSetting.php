<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPageCacheTtlSetting extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('settings')->insert([
            'class'      => 'App',
            'key'        => 'pageCacheTtl',
            'value'      => '120',
            'type'       => 'integer',
            'context'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $this->db->table('settings')
            ->where('class', 'App')
            ->where('key', 'pageCacheTtl')
            ->delete();
    }
}
