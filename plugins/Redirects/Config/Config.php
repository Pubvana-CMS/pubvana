<?php

return [
    'routePrepend' => 'redirects',
    'skip_prefixes' => [
        '/admin',
        '/api',
    ],
    'incoming_404s' => [
        'skip_prefixes' => [
            '/admin',
            '/api',
        ],
    ],
];
