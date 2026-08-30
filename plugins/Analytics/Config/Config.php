<?php

return [
    'routePrepend' => 'analytics',
    'tracking' => [
        'skip_prefixes' => [
            '/admin',
            '/api',
            '/assets',
        ],
        'skip_paths' => [
            '/feed',
            '/rss',
            '/atom.xml',
            '/sitemap.xml',
            '/robots.txt',
            '/llms.txt',
        ],
    ],
    'rollup' => [
        'hot_days' => 30,
    ],
];