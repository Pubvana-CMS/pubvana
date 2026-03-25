<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Only seed languages that ship with translation files
        $languages = [
            ['code' => 'en', 'name' => 'English',    'native_name' => 'English',          'direction' => 'ltr', 'is_default' => 1, 'is_active' => 1, 'sort_order' => 1],
            ['code' => 'fr', 'name' => 'French',     'native_name' => 'Français',         'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 2],
            ['code' => 'es', 'name' => 'Spanish',    'native_name' => 'Español',          'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 3],
            ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português',        'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 4],
            ['code' => 'id', 'name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia', 'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 5],
            ['code' => 'sk', 'name' => 'Slovak',     'native_name' => 'Slovenčina',       'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 6],
        ];

        foreach ($languages as $lang) {
            $lang['created_at'] = $now;
            $lang['updated_at'] = $now;
            $this->db->table('languages')->ignore(true)->insert($lang);
        }
    }
}
