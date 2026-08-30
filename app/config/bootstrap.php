<?php

/**
 * Bootstrap: application initialization for web requests.
 *
 * This file is the second step after index.php. It:
 *   1. Loads Composer's autoloader
 *   2. Checks that config.php exists (user copies config_sample.php)
 *   3. Creates the FlightPHP app instance
 *   4. Loads config values into the app
 *   5. Applies .env overrides and derives the HTTPS policy (env-overrides.php,
 *      shared with the CLI boot path so both resolve identical config)
 *   6. Enforces HTTPS when the policy demands it (before anything else runs,
 *      Shield and sessions read this same flag later in boot)
 *   7. Loads services.php (DB, auth, settings, plugin loader, etc.)
 *   8. Loads core-admin.php + routes.php, registers stored routes
 *   9. Starts FlightPHP to process the request
 *
 * @package Pubvana\config
 */

// Guarded: commands like `runway routes` include this bootstrap from within
// a CLI process that already defined PROJECT_ROOT via services.php.
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}
$ds = DIRECTORY_SEPARATOR;

// Composer autoloader: PSR-4 namespacing plus every vendor package
require(PROJECT_ROOT . $ds . 'vendor' . $ds . 'autoload.php');

// Config is no longer a file. app/config/env-overrides.php seeds environment,
// database, CMS.siteName, CMS.adminEmail, flight.debug, flight.force_https
// from .env — web and CLI share that one path, identical values.
$app = Flight::app();

// .env overrides + HTTPS policy derivation (idempotent, shared with CLI).
// Seeds: environment, database (defaults via env-overrides), flight.debug,
// CMS.* , DB_* folds, and session encryption key.
require(__DIR__ . $ds . 'env-overrides.php');

// Enforce the HTTPS policy before services and plugins run. Shield throws a
// SecurityException on insecure requests when force_https is true; visitors
// get a clean 308 upgrade instead. Request::secure honours X-Forwarded-Proto.
// redirect() does NOT halt execution (start() would dispatch over it), so
// exit immediately after the response is sent.
if (php_sapi_name() !== 'cli'
    && $app->get('flight.force_https') === true
    && !$app->request()->secure) {
    $app->redirect('https://' . $app->request()->host . $app->request()->url, 308);
    exit;
}

// Services: DB connection, session, auth, settings, plugin loader, error handler
require(__DIR__ . $ds . 'services.php');

// Core admin: registers users/groups/permissions via adext (menu items + routes)
require(__DIR__ . $ds . 'core-admin.php');

// Timezone: after core-admin so declaredFields() cache isn't poisoned early.
// Tolerant of fresh installs: falls back to config/env tier when the
// settings table doesn't exist yet.
$tz = $app->settings()->get('CMS.defaultTimezone', 'UTC');
try {
    new \DateTimeZone((string) $tz);
} catch (\Throwable) {
    $tz = 'UTC';
}
date_default_timezone_set((string) $tz);

// Routes: URL patterns mapped to controllers/closures
require(__DIR__ . $ds . 'routes.php');

// Register all adext-stored routes with Flight's router (core + plugins)
$app->adext()->registerRoutes($app);

// Fire it up. Flight processes the matched route and sends the response
$app->start();
