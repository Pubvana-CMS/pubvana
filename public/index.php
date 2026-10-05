<?php

/**
 *
 * This is the file that starts it all from a browser. Served directly or
 *  via Apache/Nginx rewrite rules. It loads the bootstrap which handles 
 * autoloading, config, services, routes, and starts FlightPHP framework.
 *
 * Asset requests interrupt before the framework bootstrap: they are
 * static files resolved from disk by AssetService and don't need the
 * regular full boot.
 *
 * Edit the $projectRoot line below when the web root sits outside the
 * install. Everything else reads from it.
 * https://pubvanacms.com/docs/user/v/3.0/getting-started/installing-pubvana
 *
 * @package Pubvana
 */

// Folder that holds app/, vendor/, and writable/. Defaults to the folder
// above public/. Change it to the absolute install path, no trailing slash,
// when this file is served from a web root outside the install, for example
// '/var/www/pubvana'. Leave the default on a stock install.
// $projectRoot = '/var/www/pubvana';
$projectRoot = dirname(__DIR__);


// Don't edit below here. Bad things will happen

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $projectRoot);
}

// Folder the web server serves. __DIR__ is this file's folder which must be 
// accessible to the web.  stock installation is WEB_ROOT/public (EG: public_html/public)
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', __DIR__);
}

$ds = DIRECTORY_SEPARATOR;

// Assets Server. serves CSS/JS/etc files publically so there's no need for
// fragile copying or symlinks
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI !== 'cli' && str_starts_with((string) $requestPath, '/assets/')) {
    require(PROJECT_ROOT . $ds . 'app' . $ds . 'config' . $ds . 'asset-server.php');
    exit;
}

// Fire up the app and get this party started.
require(PROJECT_ROOT . $ds . 'app' . $ds . 'config' . $ds . 'bootstrap.php');
