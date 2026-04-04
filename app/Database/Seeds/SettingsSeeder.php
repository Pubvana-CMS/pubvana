<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            // General
            ['class' => 'App',    'key' => 'siteName',          'value' => 'Pubvana CMS',          'type' => 'string'],
            ['class' => 'App',    'key' => 'siteTagline',       'value' => 'A modern CMS',         'type' => 'string'],
            ['class' => 'App',    'key' => 'siteEmail',         'value' => 'admin@example.com',     'type' => 'string'],
            ['class' => 'App',    'key' => 'postsPerPage',      'value' => '10',                    'type' => 'integer'],
            ['class' => 'App',    'key' => 'commentsEnabled',   'value' => '1',                     'type' => 'boolean'],
            ['class' => 'App',    'key' => 'commentModeration', 'value' => '1',                     'type' => 'boolean'],
            ['class' => 'App',    'key' => 'frontPageType',     'value' => 'blog',                  'type' => 'string'],
            ['class' => 'App',    'key' => 'frontPageId',       'value' => null,                    'type' => 'NULL'],
            ['class' => 'App',    'key' => 'frontPageRoute',    'value' => null,                    'type' => 'NULL'],
            ['class' => 'App',    'key' => 'maintenanceMode',   'value' => '0',                     'type' => 'boolean'],
            ['class' => 'App',    'key' => 'pageCacheTtl',      'value' => '120',                   'type' => 'integer'],
            ['class' => 'App',    'key' => 'autoUpdate',        'value' => '0',                     'type' => 'boolean'],
            ['class' => 'App',    'key' => 'updateCheckMethod', 'value' => 'pageload',              'type' => 'string'],

            // SEO
            ['class' => 'Seo',   'key' => 'metaDescription',    'value' => '',                     'type' => 'string'],
            ['class' => 'Seo',   'key' => 'googleAnalytics',    'value' => '',                     'type' => 'string'],
            ['class' => 'Seo',   'key' => 'sitemapEnabled',     'value' => '1',                    'type' => 'boolean'],
            ['class' => 'Seo',   'key' => 'newsSitemapEnabled', 'value' => '0',                    'type' => 'boolean'],

            // Email
            ['class' => 'Email', 'key' => 'fromName',           'value' => 'Pubvana CMS',         'type' => 'string'],
            ['class' => 'Email', 'key' => 'fromAddress',        'value' => 'no-reply@example.com', 'type' => 'string'],
            ['class' => 'Email', 'key' => 'protocol',           'value' => 'mail',                 'type' => 'string'],
            ['class' => 'Email', 'key' => 'SMTPHost',           'value' => '',                     'type' => 'string'],
            ['class' => 'Email', 'key' => 'SMTPPort',           'value' => '587',                  'type' => 'integer'],
            ['class' => 'Email', 'key' => 'SMTPCrypto',         'value' => '',                     'type' => 'string'],
            ['class' => 'Email', 'key' => 'SMTPUser',           'value' => '',                     'type' => 'string'],
            ['class' => 'Email', 'key' => 'SMTPPass',           'value' => '',                     'type' => 'string'],

            // Spam protection
            ['class' => 'App',    'key' => 'hcaptchaSiteKey',   'value' => '',                     'type' => 'string'],
            ['class' => 'App',    'key' => 'hcaptchaSecretKey',  'value' => '',                    'type' => 'string'],

            // Social login
            ['class' => 'Social', 'key' => 'googleClientId',       'value' => '',                  'type' => 'string'],
            ['class' => 'Social', 'key' => 'googleClientSecret',   'value' => '',                  'type' => 'string'],
            ['class' => 'Social', 'key' => 'facebookClientId',     'value' => '',                  'type' => 'string'],
            ['class' => 'Social', 'key' => 'facebookClientSecret', 'value' => '',                  'type' => 'string'],

            // Social sharing
            ['class' => 'Social', 'key' => 'twitterApiKey',       'value' => '',                   'type' => 'string'],
            ['class' => 'Social', 'key' => 'twitterApiSecret',    'value' => '',                   'type' => 'string'],
            ['class' => 'Social', 'key' => 'twitterAccessToken',  'value' => '',                   'type' => 'string'],
            ['class' => 'Social', 'key' => 'twitterAccessSecret', 'value' => '',                   'type' => 'string'],
            ['class' => 'Social', 'key' => 'facebookPageId',      'value' => '',                   'type' => 'string'],
            ['class' => 'Social', 'key' => 'facebookPageToken',   'value' => '',                   'type' => 'string'],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($settings as $s) {
            $s['created_at'] = $now;
            $s['updated_at'] = $now;
            $this->db->table('settings')->ignore(true)->insert($s);
        }
    }
}
