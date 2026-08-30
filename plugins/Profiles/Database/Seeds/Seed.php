<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'profile.edit', 'description' => 'Edit own profile'],
                ['alias' => 'profile.edit.any', 'description' => 'Edit any user profile'],
            ],
        ],
    ],
];
