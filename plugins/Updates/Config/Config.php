<?php

/**
 * Updates plugin configuration.
 *
 * @package   Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license   MIT
 */

return [
    'routePrepend'  => 'updates',

    // Machine-readable release feed fetched by installed sites.
    'releases_url'  => 'https://raw.githubusercontent.com/Pubvana-CMS/pubvana/main/releases.json',

    // HTTP client behavior.
    'user_agent'        => 'Pubvana-Updates/3.0',
    'check_timeout'     => 5,
    'download_timeout'  => 300,

    // Where downloaded zips, extraction dirs, the lock and progress files live.
    'updates_path'  => PROJECT_ROOT . '/writable/updates',

    // Paths (relative to the project root) never overwritten by an update.
    // 'writable' is a directory and is skipped whole; the rest are exact files.
    'protected_paths' => [
        '.env',
        'app/config/shield.php',
        'writable',
    ],

    // Preflight: minimum free disk (MB) on the project partition.
    'min_free_disk_mb' => 500,

    // How long a release check result is trusted before re-fetching.
    'check_cache_hours' => 24,

    // Where to read the installed version from (overridable for testing).
    'manifest_path' => PROJECT_ROOT . '/pubvana.json',
];
