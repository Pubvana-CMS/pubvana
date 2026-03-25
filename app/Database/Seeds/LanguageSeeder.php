<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $languages = [
            ['code' => 'en',      'name' => 'English',              'native_name' => 'English',             'direction' => 'ltr', 'is_default' => 1, 'is_active' => 1, 'sort_order' => 1],
            ['code' => 'fr',      'name' => 'French',               'native_name' => 'Français',            'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 2],
            ['code' => 'es',      'name' => 'Spanish',              'native_name' => 'Español',             'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 3],
            ['code' => 'pt',      'name' => 'Portuguese',           'native_name' => 'Português',           'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 4],
            ['code' => 'id',      'name' => 'Indonesian',           'native_name' => 'Bahasa Indonesia',    'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 5],
            ['code' => 'sk',      'name' => 'Slovak',               'native_name' => 'Slovenčina',          'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 6],
            ['code' => 'de',      'name' => 'German',               'native_name' => 'Deutsch',             'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 7],
            ['code' => 'it',      'name' => 'Italian',              'native_name' => 'Italiano',            'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 8],
            ['code' => 'ja',      'name' => 'Japanese',             'native_name' => '日本語',              'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 9],
            ['code' => 'ru',      'name' => 'Russian',              'native_name' => 'Русский',             'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 10],
            ['code' => 'ar',      'name' => 'Arabic',               'native_name' => 'العربية',             'direction' => 'rtl', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 11],
            ['code' => 'bg',      'name' => 'Bulgarian',            'native_name' => 'Български',           'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 12],
            ['code' => 'cs',      'name' => 'Czech',                'native_name' => 'Čeština',             'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 13],
            ['code' => 'hu',      'name' => 'Hungarian',            'native_name' => 'Magyar',              'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 14],
            ['code' => 'lv',      'name' => 'Latvian',              'native_name' => 'Latviešu',            'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 15],
            ['code' => 'no',      'name' => 'Norwegian',            'native_name' => 'Norsk',               'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 16],
            ['code' => 'pl',      'name' => 'Polish',               'native_name' => 'Polski',              'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 17],
            ['code' => 'sl',      'name' => 'Slovenian',            'native_name' => 'Slovenščina',         'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 18],
            ['code' => 'tr',      'name' => 'Turkish',              'native_name' => 'Türkçe',              'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 19],
            ['code' => 'uk',      'name' => 'Ukrainian',            'native_name' => 'Українська',          'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 20],
            ['code' => 'zh-Hans', 'name' => 'Chinese (Simplified)', 'native_name' => '简体中文',            'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 21],
            ['code' => 'zh-Hant', 'name' => 'Chinese (Traditional)','native_name' => '繁體中文',            'direction' => 'ltr', 'is_default' => 0, 'is_active' => 0, 'sort_order' => 22],
        ];

        foreach ($languages as $lang) {
            $lang['created_at'] = $now;
            $lang['updated_at'] = $now;
            $this->db->table('languages')->ignore(true)->insert($lang);
        }
    }
}
