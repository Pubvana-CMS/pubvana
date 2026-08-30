<?php

/**
 * CREDENTIALS fallback for enlivenapp/migrations.
 *
 * This is the ConfigLoader's lower, file-based cascade tier. Web requests and
 * the app's own ./runway script both load services.php (via app/config/
 * services.php), which registers a PDO as Flight::get('db') and the enabled-
 * gated migration set as Flight::get('migrations'). ConfigLoader prefers that
 * Flight tier, so THIS file is only reached as a fallback when Flight is not
 * bootstrapped (e.g. a standalone invocation of the command tool).
 *
 * If it IS reached, use core-only migration paths (never a blanket glob),
 * matching the enabled-gated set the app would otherwise provide.
 *
 * @package Pubvana\config
 */

if (!defined('RUNWAY_PROJECT_ROOT') && !defined('PROJECT_ROOT')) {
    define('RUNWAY_PROJECT_ROOT', getcwd());
}

$root = defined('RUNWAY_PROJECT_ROOT') ? RUNWAY_PROJECT_ROOT
    : (defined('PROJECT_ROOT') ? PROJECT_ROOT : getcwd());

// Load .env so CLI resolves the same credentials as web. dotenv never
// overrides already-set environment variables, so it is safe to load here
// every time.
$_envFile = $root . '/.env';
if (is_file($_envFile)) {
    try {
        \Dotenv\Dotenv::createImmutable($root)->load();
    } catch (\Throwable $e) {
        error_log('migrations.php: could not load .env - ' . $e->getMessage());
    }
}

/** @return array<string, mixed> Flat DB credentials from environment (.env / process env) */
$config = [
    'driver'   => $_ENV['DB_DRIVER']   ?? $_SERVER['DB_DRIVER']    ?? getenv('DB_DRIVER')    ?: 'mysql',
    'host'     => $_ENV['DB_HOST']     ?? $_SERVER['DB_HOST']     ?? getenv('DB_HOST')     ?: 'localhost',
    'port'     => $_ENV['DB_PORT']     ?? $_SERVER['DB_PORT']     ?? getenv('DB_PORT')     ?: 3306,
    'dbname'   => $_ENV['DB_NAME']     ?? $_SERVER['DB_NAME']     ?? getenv('DB_NAME')     ?: '',
    'user'     => $_ENV['DB_USER']     ?? $_SERVER['DB_USER']     ?? getenv('DB_USER')     ?: '',
    'password' => $_ENV['DB_PASS']     ?? $_SERVER['DB_PASS']     ?? getenv('DB_PASS')     ?: '',
    'charset'  => $_ENV['DB_CHARSET'] ?? $_SERVER['DB_CHARSET'] ?? getenv('DB_CHARSET') ?? 'utf8mb4',
];

// When reached (no Flight tier available), provide core-only migration paths,
// never a blanket glob. The app's other plugins supply their gated set via the
// Flight store in services.php.
$config['migrations'] = [
    'paths'        => ['app/Database/Migrations'],
    'seeds'        => ['paths' => []],
    'versions'     => ['pubvana/pubvana' => '3.0.0'],
    'module_names' => ['app/Database/Migrations' => 'pubvana/pubvana'],
];

return $config;
