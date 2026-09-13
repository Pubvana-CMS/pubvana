<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for Pubvana.
 *
 * Loads the Composer autoloader and establishes the runtime constants the
 * application expects (PROJECT_ROOT). The SQLite in-memory database used by
 * DB-backed tests is created lazily through Pubvana\Tests\Support\Sqlite
 * rather than here, so fast, pure-logic unit tests never pay for schema
 * setup they do not need.
 */

use Pubvana\Tests\Support\Sqlite;

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

require __DIR__ . '/../vendor/autoload.php';

if (getenv('PUBVANA_TESTS_USE_SQLITE')) {
    Sqlite::connection();
}

// PHPUnit buffers stdout per-test but cannot buffer error_log(), which in CLI
// lands straight on stderr and interleaves with the progress dots. Expected
// diagnostics (e.g. ExtensionRegistry rejection messages exercised by
// negative-path tests) should be silent while tests pass, so redirect the PHP
// error log to a per-run scratch file in the system temp dir.
ini_set('error_log', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pubvana-phpunit-errorlog-' . getmypid() . '.log');
