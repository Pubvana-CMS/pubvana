<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUpdateAndHcaptchaSettings extends Migration
{
    private array $settings = [
        ['class' => 'App', 'key' => 'autoUpdate',        'value' => '0',        'type' => 'boolean'],
        ['class' => 'App', 'key' => 'updateCheckMethod', 'value' => 'pageload', 'type' => 'string'],
        ['class' => 'App', 'key' => 'hcaptchaSiteKey',   'value' => '',         'type' => 'string'],
        ['class' => 'App', 'key' => 'hcaptchaSecretKey',  'value' => '',         'type' => 'string'],
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->settings as $s) {
            // Skip if already exists (e.g. from seeder on fresh install)
            $exists = $this->db->table('settings')
                ->where('class', $s['class'])
                ->where('key', $s['key'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('settings')->insert(array_merge($s, [
                    'context'    => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->settings as $s) {
            $this->db->table('settings')
                ->where('class', $s['class'])
                ->where('key', $s['key'])
                ->delete();
        }
    }
}
