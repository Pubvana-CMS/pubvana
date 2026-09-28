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
 * Reached from public/index.php before the framework bootstrap. The .env
 * file is read only for the HTTPS policy so insecure requests get the same
 * 308 upgrade the full boot applies.
 *
 * @package Pubvana\Config
 */

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

require(PROJECT_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

// HTTPS policy, same rule as app/config/env-overrides.php: only an explicit
// FORCE_HTTPS enables the upgrade. Default off, so http or https both work
// regardless of APP_ENV.
$assetEnv = [];
$envFile = PROJECT_ROOT . '/.env';
if (is_file($envFile)) {
    $assetEnv = parse_ini_file($envFile) ?: [];
}

$forceHttps = $assetEnv['FORCE_HTTPS'] ?? null;
if ($forceHttps !== null) {
    $forceHttps = in_array(strtolower((string) $forceHttps), ['1', 'true', 'yes', 'on'], true);
} else {
    $forceHttps = false;
}

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if ($forceHttps === true && !$isSecure) {
    // The redirect target is SITE_URL, never the request's Host header
    // (request input). A real environment variable beats .env, matching
    // env-overrides.php. No usable SITE_URL means no redirect: the site
    // cannot boot without it anyway.
    $processSiteUrl = getenv('SITE_URL');
    $siteUrl = is_string($processSiteUrl) && $processSiteUrl !== ''
        ? $processSiteUrl
        : (string) ($assetEnv['SITE_URL'] ?? '');
    $siteUrl = rtrim($siteUrl, '/');

    if (preg_match('#^https?://#i', $siteUrl) === 1) {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $location = (string) preg_replace('#^http://#i', 'https://', $siteUrl);
        header('Location: ' . $location . $uri, true, 308);
        exit;
    }
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
