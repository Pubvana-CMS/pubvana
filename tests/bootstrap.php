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
