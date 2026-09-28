<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'navigation.edit', 'description' => 'Manage navigation menus'],
                ['alias' => 'plugins.manage', 'description' => 'View and manage installed plugins'],
            ],
        ],
        [
            'table' => 'navigation',
            'rows'  => [
                [
                    'label'      => 'Home',
                    'url'        => '/',
                    'sort_order' => 0,
                    'nav_group'  => 'primary',
                    'target'     => '_self',
                ],
                [
                    'label'      => 'Blog',
                    'url'        => '/blog',
                    'sort_order' => 1,
                    'nav_group'  => 'primary',
                    'target'     => '_self',
                ],
            ],
        ],
        [
            // Every declared setting gets a row. A row is the value: no read
            // path falls back to a literal in code, so a key with no row would
            // render as null. Blank string is the value for "not set yet"
            // (Mail.fromEmail, Captcha.site_key), and every value here matches
            // the declaration's own default in app/config/core-admin.php.
            //
            // SITE_URL is absent on purpose: it is deployment config from
            // .env, read as $app->get('siteUrl'), never a settings row.
            'table' => 'settings',
            'rows'  => [
                // Settings > General (Site)
                ['key' => 'CMS.siteName',        'value' => 'Pubvana v3',                 'type' => 'string',  'autoload' => true],
                ['key' => 'CMS.siteByline',      'value' => 'Publishing Nirvana',         'type' => 'string',  'autoload' => true],
                ['key' => 'CMS.logo',            'value' => '/pubvana-nodrop-nobg.png',    'type' => 'string',  'autoload' => true],
                ['key' => 'CMS.favicon',         'value' => '/favicon.ico',               'type' => 'string',  'autoload' => true],
                ['key' => 'CMS.copyright',       'value' => '© Your Site',               'type' => 'string',  'autoload' => true],
                ['key' => 'CMS.adminEmail',      'value' => 'admin@example.com',          'type' => 'string',  'autoload' => true],
                ['key' => 'CMS.defaultTimezone', 'value' => 'UTC',                        'type' => 'string',  'autoload' => true],
                // 'blog' is the Blog plugin's homepage token. Empty resolves to
                // the same provider by priority, so this pins what already wins.
                ['key' => 'CMS.homepageType',    'value' => 'blog',                       'type' => 'string',  'autoload' => true],
                // Field of the homepage picker, declared by the Pages provider.
                ['key' => 'CMS.homepagePageId',  'value' => '0',                          'type' => 'integer', 'autoload' => true],

                // Settings > Email
                ['key' => 'Mail.enabled',        'value' => '0',                          'type' => 'boolean', 'autoload' => true],
                ['key' => 'Mail.host',           'value' => 'localhost',                  'type' => 'string',  'autoload' => true],
                ['key' => 'Mail.port',           'value' => '587',                        'type' => 'integer', 'autoload' => true],
                ['key' => 'Mail.encryption',     'value' => 'tls',                        'type' => 'string',  'autoload' => true],
                ['key' => 'Mail.fromEmail',      'value' => '',                           'type' => 'string',  'autoload' => true],
                ['key' => 'Mail.fromName',       'value' => '',                           'type' => 'string',  'autoload' => true],
                ['key' => 'Mail.username',       'value' => '',                           'type' => 'string',  'autoload' => true],
                // Written encrypted by Mailer::saveSettings(); a blank row means
                // "no password yet", which is what the reader treats it as.
                ['key' => 'Mail.password',       'value' => '',                           'type' => 'string',  'autoload' => true],

                // Settings > Login
                ['key' => 'Shield.allow_registration', 'value' => '0',                    'type' => 'boolean', 'autoload' => true],
                ['key' => 'Shield.magic_link',         'value' => '0',                    'type' => 'boolean', 'autoload' => true],
                ['key' => 'Shield.remember_me',        'value' => '0',                    'type' => 'boolean', 'autoload' => true],
                ['key' => 'Shield.email_2fa',          'value' => '0',                    'type' => 'boolean', 'autoload' => true],
                ['key' => 'Shield.email_activation',   'value' => '0',                    'type' => 'boolean', 'autoload' => true],

                // Settings > Captcha
                ['key' => 'Captcha.provider',    'value' => 'none',                       'type' => 'string',  'autoload' => true],
                ['key' => 'Captcha.site_key',    'value' => '',                           'type' => 'string',  'autoload' => true],
                ['key' => 'Captcha.secret_key',  'value' => '',                           'type' => 'string',  'autoload' => true],
            ],
        ],
        [
            'table' => 'themes',
            'rows'  => [
                [
                    'name'        => 'Default',
                    'folder'      => 'default',
                    'description' => "Pubvana's free default theme built on Bootswatch Flatly (Bootstrap 5). See Tools -> Marketplace -> Catalog for more options",
                    'version'     => '1.4.12',
                    'author'      => 'pubvana',
                    'is_active'   => 1,
                ],
            ],
        ],
        [
            'table' => 'plugin_state',
            'rows'  => [
                // Shipped-active bundled core plugins. Anything not listed here
                // (or explicitly configured) is discovered disabled: its code
                // runs nothing until an admin enables it on the Plugins page.
                // AiAssistant (pubvana/ai) is deliberately absent, so it ships off.
                ['plugin_id' => 'pubvana/activity-log',  'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/ai',            'enabled' => 0, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/analytics',     'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/backups',       'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/blog',          'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/brokenlinks',   'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/comments',      'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/core-blocks',   'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/forms',         'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/marketplace',   'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/media',         'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/pages',         'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/profiles',      'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/redirects',     'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/search',        'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/seo',           'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/sitehealth',    'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/social-links',  'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/updates',       'enabled' => 1, 'priority' => 50, 'required' => 0],
            ],
        ],
    ],
];
