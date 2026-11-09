<?php

/**
 * Bootstrap: application initialization for web requests.
 *
 * This file is the second step after index.php. It:
 *   1. Loads Composer's autoloader
 *   2. Creates the FlightPHP app instance
 *   3. Applies .env overrides and derives the HTTPS policy (env-overrides.php,
 *      shared with the CLI boot path so both resolve identical config)
 *   4. Enforces HTTPS when the policy demands it (before anything else runs,
 *      Shield and sessions read this same flag later in boot)
 *   5. Loads services.php (DB, auth, settings, plugin loader, etc.)
 *   6. Sends users with a pending password reset to the reset page
 *   7. Loads core-admin.php + routes.php, registers stored routes
 *   8. Starts FlightPHP to process the request
 *
 * @package Pubvana\config
 */

$ds = DIRECTORY_SEPARATOR;

// Composer autoloader: PSR-4 namespacing plus every vendor package
require(PROJECT_ROOT . $ds . 'vendor' . $ds . 'autoload.php');

// Config is no longer a file. app/config/env-overrides.php seeds environment,
// database, flight.debug, flight.force_https from .env - web and CLI share
// that one path, identical values.
$app = Flight::app();

// .env overrides + HTTPS policy derivation (idempotent, shared with CLI).
// Seeds: environment, database (defaults via env-overrides), flight.debug,
// SITE_URL, DB_* folds, and session encryption key.
require(__DIR__ . $ds . 'env-overrides.php');

// Every request must arrive on the SITE_URL domain, and over HTTPS when
// FORCE_HTTPS is on. Anything else gets one 301 to SITE_URL with the
// original path and query, so a visitor on an old domain goes to the same
// page of the correct one. The app builds the redirect from SITE_URL, never
// from the request's Host header, so this cannot be pointed elsewhere or
// loop. redirect() does NOT halt execution (start() would dispatch over
// it), so exit right after sending the response.
if (php_sapi_name() !== 'cli') {
    $redirectTo = \Pubvana\Services\UrlService::siteUrlRedirect(
        (string) ($app->get('siteUrl') ?? ''),
        $_SERVER,
        $app->get('flight.force_https') === true
    );

    if ($redirectTo !== null) {
        $app->redirect($redirectTo, 301);
        exit;
    }
}

// Services: DB connection, session, auth, settings, plugin loader, error handler
require(__DIR__ . $ds . 'services.php');

/*
|--------------------------------------------------------------------------
| Forced Password Reset
|--------------------------------------------------------------------------
| Signed-in users whose Shield email identity carries the force_reset flag
| go to /auth/reset-password until they save a new password. This runs
| before routing, so it covers core, adext, and plugin routes. Anonymous
| requests pass straight through.
|
| Web only: the CLI shares this bootstrap and has no session to check.
*/
if (php_sapi_name() !== 'cli') {
    (new \Pubvana\Middleware\ForcedPasswordResetCheck($app))->enforce();
}

/*
|--------------------------------------------------------------------------
| Session User Context
|--------------------------------------------------------------------------
| enlivenapp/flight-sessions writes a session row's user_id only when
| SessionManager::setUserContext() has been called, and every request
| rewrites the row. Binding the id here keeps per-user session lists and
| remote sign-out working. Reading the session field costs no query, and
| the forced-reset check above has already resolved remember-me logins.
*/
if (php_sapi_name() !== 'cli' && $app->session()->isActive()) {
    $shieldConfig = (array) ($app->get('enlivenapp.flight-shield') ?? []);
    $userField = (string) ($shieldConfig['session']['field'] ?? 'user');
    $sessionUserId = $_SESSION[$userField] ?? null;

    $app->session()->setUserContext(
        is_int($sessionUserId) || (is_string($sessionUserId) && ctype_digit($sessionUserId))
            ? (int) $sessionUserId
            : null
    );
}

/*
|--------------------------------------------------------------------------
| User Activity
|--------------------------------------------------------------------------
| Shield stamps users.last_active only from its own auth middlewares, and
| this app guards routes with PermissionMiddleware instead, so nothing
| wrote the column and the admin user list showed "Never" for everyone.
| Loading the user first matters: the session authenticator's
| recordActiveDate() reads its user property directly and returns early
| when the user has not been resolved.
*/
if (php_sapi_name() !== 'cli' && $app->session()->isActive()) {
    $activeUser = $app->auth()->user();

    if ($activeUser !== null) {
        $app->auth()->recordActiveDate();
    }
}

// Core admin: registers users/groups/permissions via adext (menu items + routes)
require(__DIR__ . $ds . 'core-admin.php');

// Timezone: after core-admin so declaredFields() cache isn't poisoned early.
// Tolerant of fresh installs: an unreadable settings table falls back to the
// declaration default, and the catch below pins UTC if even that is unusable.
$tz = $app->settings()->get('CMS.defaultTimezone');
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

/*
|--------------------------------------------------------------------------
| Page Cache Write Path
|--------------------------------------------------------------------------
| A cacheable request that missed registered the service in services.php.
| Flight calls the response callback before it sends headers, so the callback
| can set the cache headers and write the finished body to disk. page-cache.php
| sets the flag on a miss only, so the callback stores nothing when the
| request cannot use the cache.
*/
$pageCache = $app->get('pageCache.instance');
if (
    $pageCache instanceof \Pubvana\Services\PageCacheService
    && $app->get('pageCache.store') === true
) {
    $app->response()->addResponseBodyCallback(
        static fn (string $body): string => $pageCache->captureResponse($app->response(), $body)
    );
}

// Fire it up. Flight processes the matched route and sends the response
$app->start();

// Second expiry trigger. The content services call invalidate() directly.
// This catches a successful write nobody has connected yet, so a publish
// does not wait out a full TTL behind an expired page. Cache hits exit
// earlier and never reach here.
if (
    $pageCache instanceof \Pubvana\Services\PageCacheService
    && $pageCache->shouldInvalidateAfterRequest((int) http_response_code())
) {
    $pageCache->invalidate();
}
