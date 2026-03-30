<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsDismissableToAdminNotifications extends Migration
{
    public function up(): void
    {
        $fields = [
            'is_dismissable' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'action_label',
            ],
        ];

        $this->forge->addColumn('admin_notifications', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('admin_notifications', 'is_dismissable');
    }
}
