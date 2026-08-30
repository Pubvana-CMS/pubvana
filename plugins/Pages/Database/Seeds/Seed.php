<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'pages.manage', 'description' => 'Create, edit, and delete pages'],
            ],
        ],
        [
            'table' => 'pages',
            'rows'  => [
                [
                    'title'             => 'Not WordPress',
                    'slug'              => 'not-wordpress',
                    'content'           => 'This site is not running WordPress. You were redirected here from a WordPress-specific path (login, admin, plugin, or configuration file) that does not exist on this server. If you are a human looking for something, please use the site navigation or home page.',
                    'status'            => 'published',
                    'allow_comments'    => 0,
                    'ai_generated'      => 0,
                    'created_by'        => 1,
                ],
            ],
        ],
    ],
];
