<?php

/**
 * Routes - URL patterns mapped to handlers.
 *
 * This file defines the core routes that aren't handled by plugins.
 * Plugin routes are loaded automatically by the PluginLoader from
 * each plugin's Routes.php and AdminRoutes.php files.
 *
 * FlightPHP routing:
 *   $app->route('GET /path', function () { ... });
 *   $app->route('POST /path', function () { ... });
 *   $app->route('GET /path/@param', function ($param) { ... });
 *
 * @package Pubvana\Config
 */

use Pubvana\Middleware\SecurityHeadersMiddleware;

$app = $app ?? Flight::app();

/*
|--------------------------------------------------------------------------
| Security Headers
|--------------------------------------------------------------------------
| Applies CSP, X-Frame-Options, etc. to every response.
| HSTS is only sent when the HTTPS policy is active (flight.force_https) -
| advertising HSTS over plain HTTP is meaningless at best.
| Must run before any output is sent.
*/
$securityHeaders = new SecurityHeadersMiddleware(
    $app->get('flight.force_https') === true
        ? ['Strict-Transport-Security' => 'max-age=31536000; includeSubDomains']
        : []
);
$securityHeaders->before();

/*
|--------------------------------------------------------------------------
| Asset Serving
|--------------------------------------------------------------------------
| Serves assets from themes, plugins, and vendor packages via AssetController.
| Route: /assets/{type}/{name}/{path}
|   - type: plugin, theme, or vendor
|   - name: plugin/theme name or vendor/package
|   - path: relative path within assets directory
|
| Examples:
|   /assets/plugin/Profiles/css/profiles.css
|   /assets/theme/default/css/style.css
|   /assets/vendor/pubvana/blog/js/blog.js
*/
$app->route('GET /assets/@type/@name/@path:.+', function (string $type, string $name, string $path) use ($app) {
    (new \Pubvana\Controllers\Public\AssetController($app))->serve($type, $name, $path);
});

/*
|--------------------------------------------------------------------------
| Admin Dashboard
|--------------------------------------------------------------------------
| The /admin route renders the dashboard. Plugin routes live under
| /admin/* and are loaded by the PluginLoader (e.g. /admin/blog,
| /admin/pages, /admin/media).
*/
$app->route('GET /admin', function () use ($app) {
    // Auth check temporarily disabled for development — see the
    // Auth Middleware note in core-admin.php for reinstatement.
    // Shield option once reinstated:
    //   ->addMiddleware(new PermissionMiddleware($app, 'admin.access'))
    // $user = $app->auth()->user();
    // if ($user === null) {
    //     $app->redirect('/auth/login');
    //     return;
    // }

    (new \Pubvana\Controllers\Admin\AdminController($app))->index();
});

/*
|--------------------------------------------------------------------------
| Homepage
|--------------------------------------------------------------------------
| The homepage route dispatches to whatever plugin owns the front page.
| By default this is the blog index, but it can be changed to a static
| page or any other route via the FrontPage.route setting.
*/
$app->route('GET /', function () use ($app) {
    $app->pluginLoader()->dispatchHomepage();
});
