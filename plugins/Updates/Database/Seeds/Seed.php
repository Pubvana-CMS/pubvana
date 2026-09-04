<?php

declare(strict_types=1);

/**
 * Seeds for the Updates plugin.
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */

return [
    [
        'table' => 'auth_permissions',
        'rows'  => [
            ['alias' => 'updates.manage', 'description' => 'Check for and apply Pubvana updates'],
        ],
    ],
];
