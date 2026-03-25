<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLanguagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 10],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'native_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'direction'   => ['type' => 'ENUM', 'constraint' => ['ltr', 'rtl'], 'default' => 'ltr'],
            'is_default'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('languages', true);
    }

    public function down()
    {
        $this->forge->dropTable('languages', true);
    }
}
