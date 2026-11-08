<?php

declare(strict_types=1);

/**
 * Early asset server for /assets/** requests.
 *
 * Serves theme, plugin, and vendor assets without booting the application:
 * no session, no database, no plugin loading, no route table. AssetService
 * resolves the URL to a file on disk and streams it with ETag and cache
 * headers, identical to the full-app route in app/config/routes.php.
 *
 * public/index.php loads this file before the framework bootstrap. From .env
 * this file needs only SITE_URL and FORCE_HTTPS, so a request on another
 * domain, or over HTTP while forcing HTTPS, gets the same 301 the full boot
 * sends.
 *
 * @package Pubvana\Config
 */

require(PROJECT_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

// HTTPS policy, same rule as app/config/env-overrides.php: only an explicit
// FORCE_HTTPS enables the upgrade. Default off, so http or https both work
// regardless of APP_ENV.
//
// Dotenv::parse reads the file without touching $_ENV or getenv(), so this
// stays a plain read. It also matches the grammar env-overrides.php loads with:
// parse_ini_file() treats '#' as text, not a comment, and returns false on any
// comment line holding a bare '(' such as the key-generation example.
$assetEnv = [];
$envFile = PROJECT_ROOT . '/.env';
if (is_file($envFile)) {
    try {
        $assetEnv = \Dotenv\Dotenv::parse((string) file_get_contents($envFile));
    } catch (\Throwable) {
        // A malformed .env falls back to the defaults below, never a fatal
        // error on an asset request.
        $assetEnv = [];
    }
}

$forceHttps = $assetEnv['FORCE_HTTPS'] ?? null;
if ($forceHttps !== null) {
    $forceHttps = in_array(strtolower((string) $forceHttps), ['1', 'true', 'yes', 'on'], true);
} else {
    $forceHttps = false;
}

// Same rule as app/config/bootstrap.php: the request must arrive on the
// SITE_URL domain, and over HTTPS when FORCE_HTTPS is on. Assets exit before
// the framework boots, so the check runs here too. A real environment
// variable beats .env, matching env-overrides.php. No usable SITE_URL means
// no redirect: the site cannot boot without it anyway.
$processSiteUrl = getenv('SITE_URL');
$siteUrl = is_string($processSiteUrl) && $processSiteUrl !== ''
    ? $processSiteUrl
    : (string) ($assetEnv['SITE_URL'] ?? '');

$redirectTo = \Pubvana\Services\UrlService::siteUrlRedirect($siteUrl, $_SERVER, $forceHttps);

if ($redirectTo !== null) {
    header('Location: ' . $redirectTo, true, 301);
    exit;
}

// URL shape: /assets/{type}/{name}/{path...} (same as the full-app route).
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';
$segments = explode('/', trim($uriPath, '/'));

if (count($segments) < 4 || $segments[0] !== 'assets') {
    http_response_code(404);
    exit;
}

$type = (string) $segments[1];
$name = (string) $segments[2];
$path = implode('/', array_slice($segments, 3));

$assetService = new \Pubvana\Services\AssetService();
$filePath = $assetService->resolve($type, $name, $path);

if ($filePath === null) {
    http_response_code(404);
    exit;
}

$assetService->serve($filePath);
