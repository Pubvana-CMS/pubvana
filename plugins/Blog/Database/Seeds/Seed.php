<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'posts.create', 'description' => 'Create blog posts'],
                ['alias' => 'posts.edit.own', 'description' => 'Edit own blog posts'],
                ['alias' => 'posts.edit.any', 'description' => 'Edit any blog post'],
                ['alias' => 'posts.delete', 'description' => 'Delete blog posts'],
                ['alias' => 'categories.manage', 'description' => 'Manage blog categories'],
                ['alias' => 'tags.manage', 'description' => 'Manage blog tags'],
            ],
        ],
    ],
];
