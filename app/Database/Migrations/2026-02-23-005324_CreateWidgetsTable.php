<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWidgetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'folder'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'store_product_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bundled'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
            'support_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'author_url'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'free'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
            'items_url'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'item_url'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'categories_url'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'categories_all_url'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'category_url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'featured_url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'license_validate_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'license_check_url'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'update_url'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'update_check_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'download_url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'store_url'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'version'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'author'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'disabled'    => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => null],
            'disabled_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'pv_safe'     => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'null' => true, 'default' => null],
            'pv_warning_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'latest_version' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'changelog'   => ['type' => 'TEXT', 'null' => true],
            'auto_update' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'last_update_check'   => ['type' => 'DATETIME', 'null' => true],
            'last_update_attempt' => ['type' => 'ENUM', 'constraint' => ['success', 'fail'], 'null' => true],
            'last_update_error'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'last_updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('folder');
        $this->forge->createTable('widgets', true);
    }

    public function down()
    {
        $this->forge->dropTable('widgets', true);
    }
}
