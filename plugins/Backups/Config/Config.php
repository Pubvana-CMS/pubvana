<?php

/**
 * Backups plugin configuration.
 *
 * @package   Pubvana\Plugins\Backups
 * @copyright 2026 enlivenapp
 * @license   MIT
 */

return [
    'routePrepend' => 'backups',
    'max_backups'  => 15,
    'backup_path'  => PROJECT_ROOT . '/writable/backups',
    'backup_dirs'  => ['app', 'public', 'vendor', 'themes'],
    'protected_configs' => [
        '.env',
        'app/config/services.php',
        'app/config/env-overrides.php',
        'app/config/shield.php',
    ],
];