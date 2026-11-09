<?php

declare(strict_types=1);

/**
 * Page cache: answer from a stored file when one exists.
 *
 * services.php includes this file before the plugin loader, so a request that
 * comes from the cache returns before the sessions plugin starts, before any
 * plugin loads, and before routing. On a miss this file registers the service
 * and returns, and bootstrap writes the finished page to disk afterwards.
 *
 * The code reads configuration from .env through env-overrides.php with
 * $app->get(), like every other deployment value. It never touches the
 * settings table.
 *
 * @package Pubvana\Config
 */

use Pubvana\Services\PageCacheService;
use Pubvana\Services\PublicCsrf;

/** @var \flight\Engine $app */

$pageCachePlugins = $app->get('plugins');
$pageCacheSession = is_array($pageCachePlugins)
    ? (array) ($pageCachePlugins['enlivenapp/flight-sessions'] ?? [])
    : [];

$pageCache = PageCacheService::fromEnv([
    'enabled'            => $app->get('pageCache.enabled') === true,
    'ttl'                => (int) ($app->get('pageCache.ttl') ?? 600),
    'edge_ttl'           => (int) ($app->get('pageCache.edgeTtl') ?? 0),
    'cloudflare_zone_id' => $app->get('cloudflare.zoneId'),
    'cloudflare_token'   => $app->get('cloudflare.token'),
    'session_cookie'     => (string) ($pageCacheSession['cookie_name'] ?? 'flight_session'),
    'session_key'        => (string) ($pageCacheSession['encryption_key'] ?? ''),
    'host'               => (string) ($_SERVER['HTTP_HOST'] ?? ''),
], $app->db());

$app->map('pageCache', fn(): PageCacheService => $pageCache);
$app->set('pageCache.instance', $pageCache);

if (!$pageCache->isEligibleRequest()) {
    return;
}

if (!$pageCache->sessionIsClean()) {
    return;
}

$pageCacheKey = $pageCache->keyFor();
$pageCacheEntry = $pageCache->read($pageCacheKey);

if ($pageCacheEntry === null) {
    // A miss: the response callback in bootstrap writes the finished page out.
    $app->set('pageCache.store', true);
    return;
}

$pageCacheCsrf = new PublicCsrf($app);
$pageCacheToken = $pageCacheCsrf->tokenForResponse();

// The token is 64 hex characters, so escaping changes nothing. It runs
// because this value goes inside an HTML attribute in a body we echo.
$pageCacheTokenHtml = htmlspecialchars($pageCacheToken, ENT_QUOTES, 'UTF-8');

$pageCacheBody = str_contains($pageCacheEntry['body'], PageCacheService::TOKEN_PLACEHOLDER)
    ? str_replace(PageCacheService::TOKEN_PLACEHOLDER, $pageCacheTokenHtml, $pageCacheEntry['body'])
    : $pageCacheEntry['body'];

if (str_contains($pageCacheEntry['body'], PageCacheService::TOKEN_PLACEHOLDER)) {
    $pageCacheCsrf->sendCookie($pageCacheToken);
}

if (php_sapi_name() !== 'cli' && !headers_sent()) {
    http_response_code($pageCacheEntry['status']);

    foreach ($pageCacheEntry['headers'] as $pageCacheName => $pageCacheValue) {
        header($pageCacheName . ': ' . $pageCacheValue, true);
    }

    header('X-Pubvana-Cache: hit');
}

$pageCache->logHit();

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD') {
    exit;
}

echo $pageCacheBody;

exit;
