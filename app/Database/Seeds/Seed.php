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
            'table' => 'settings',
            'rows'  => [
                ['key' => 'CMS.siteName',       'value' => 'Pubvana v3',                          'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.siteUrl',        'value' => 'http://localhost',                    'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.adminEmail',     'value' => 'admin@example.com',                   'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.defaultTimezone','value' => 'UTC',                                 'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.siteByline',     'value' => '',                                    'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.logo',           'value' => '',                                    'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.favicon',        'value' => '/favicon.ico',                         'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.copyright',      'value' => '© Pubvana v3',                        'type' => 'string', 'autoload' => true],
            ],
        ],
    ],
];