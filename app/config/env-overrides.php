<?php

/**
 * Environment overrides, shared by both boot paths.
 *
 * Included by:
 *   - app/config/bootstrap.php (web: public/index.php)
 *   - app/config/services.php  (CLI: the app's ./runway script requires it)
 *
 * Responsibilities, in order:
 *   1. Load .env once per process
 *   2. Apply those values over the app's existing config
 *   3. Decide 'flight.force_https':
 *        FORCE_HTTPS only when explicitly set; otherwise off, so an install
 *        can run over http or https regardless of APP_ENV.
 *
 * Idempotent: safe to include from both boot paths in one process.
 * Every operation is either guarded or a pure re-assignment.
 *
 * @package Pubvana\Config
 */

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

// 1. .env loads once per process, into PHP's own variable tables.
// Precedence ("real environment variables beat .env") is enforced by
// $resolveEnv below, not here: phpdotenv can't see real process variables
// when php.ini's variables_order leaves out "E", so loading alone would
// let .env win.
if (!defined('PUBVANA_ENV_LOADED')) {
    define('PUBVANA_ENV_LOADED', true);
    $ds = DIRECTORY_SEPARATOR;
    $envFile = PROJECT_ROOT . $ds . '.env';
    if (file_exists($envFile) && class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable(PROJECT_ROOT)->load();
    }
}

/** @var \flight\Engine $app */
$app = $app ?? \Flight::app();

// Strict bool parsing: junk like "banana" parses to null, never to a
// silent security-relevant default. Callers decide how null is handled.
$toBool = static fn ($value): ?bool => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

// No config.php anymore: DB credentials come from .env / process env.
// Seed a base array so the DB_* folds below can land on a valid shape
// (services.php builds its DSN from every key here).
if (!is_array($app->get('database'))) {
    $app->set('database', [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => '',
        'user'     => '',
        'password' => '',
        'charset'  => 'utf8mb4',
    ]);
}

// No config.php: the plugin array (formerly from config.php's 'plugins' block)
// is not set. Seed it so the nested fold for SESSION_ENCRYPTION_KEY can land
// on plugins.enlivenapp/flight-sessions.encryption_key — without the base
// array that fold silently skips and web requests die on missing session keys.
if (!is_array($app->get('plugins'))) {
    $app->set('plugins', []);
}

// Defaults formerly supplied by config.php, now inherited from environment fallbacks.
if ($app->get('environment') === null) {
    $app->set('environment', 'production');
}

// The site is served at the web root. Flight derives Request::base from the
// front-controller's SCRIPT_NAME (e.g. /public/index.php -> base "/public"),
// and redirect() prepends that base. Pin flight.base_url to "/" so redirects
// and generated URLs stay root-relative (e.g. /blog, not /public/blog).
if ($app->get('flight.base_url') === null) {
    $app->set('flight.base_url', '/');
}

// 2. Apply values onto Flight's key/value store. Checked per key in this
//    order: real process env var (getenv) first, then what .env provided.
//    getenv() is the only reliable way to see real deployment variables:
//    php.ini's variables_order may exclude "E", leaving $_ENV without them.
$resolveEnv = static function (string $key): ?string {
    $real = getenv($key);
    if ($real !== false && $real !== '') {
        return $real;
    }
    return $_ENV[$key] ?? $_SERVER[$key] ?? null;
};

// Scalar keys map straight onto Flight's KV store,
// null means "fold into the database array", arrays are nested paths.
$envMap = [
    'APP_ENV'     => 'environment',
    // FORCE_HTTPS is parsed strictly below rather than in this map, so raw
    // strings never end up as app config values.
    'SITE_NAME'   => 'CMS.siteName',
    'ADMIN_EMAIL' => 'CMS.adminEmail',
    'SITE_URL'    => 'CMS.siteUrl',
    // FORCE_HTTPS is parsed strictly below rather than in this map, so raw
    // strings never end up as app config values.
    'DB_HOST'     => null,
    'DB_PORT'     => null,
    'DB_NAME'     => null,
    'DB_USER'     => null,
    'DB_PASS'     => null,
    // Array value = path segments into nested config arrays
    'SESSION_ENCRYPTION_KEY' => ['plugins', 'enlivenapp/flight-sessions', 'encryption_key'],
];

foreach ($envMap as $envKey => $appKey) {
    $value = $resolveEnv($envKey);
    if ($value === null) {
        continue;
    }

    // Nested array path (e.g. secrets inside the plugins array)
    if (is_array($appKey)) {
        $arr = $app->get($appKey[0]);
        if (!is_array($arr)) {
            continue;
        }
        $ref  = &$arr;
        $last = count($appKey) - 1;
        for ($i = 1; $i < $last; $i++) {
            if (!isset($ref[$appKey[$i]]) || !is_array($ref[$appKey[$i]])) {
                $ref[$appKey[$i]] = [];
            }
            $ref = &$ref[$appKey[$i]];
        }
        $ref[$appKey[$last]] = $value;
        unset($ref);
        $app->set($appKey[0], $arr);
        continue;
    }

    if ($appKey !== null) {
        $app->set($appKey, $value);
        continue;
    }

    // DB vars fold into the database array
    $dbKey = match ($envKey) {
        'DB_HOST' => 'host',
        'DB_PORT' => 'port',
        'DB_NAME' => 'dbname',
        'DB_USER' => 'user',
        'DB_PASS' => 'password',
    };
    $db = $app->get('database');
    if (is_array($db)) {
        $db[$dbKey] = $value;
        $app->set('database', $db);
    }
}

// Normalize the final bool so consumers never see the string "false".
// A typoed FORCE_HTTPS must NOT silently enable forcing.
// Debug follows the environment: off in production, on in development.
// APP_DEBUG is deliberately not read; APP_ENV alone drives behavior.
$app->set('flight.debug', ($app->get('environment') ?? 'production') === 'development');

// HTTPS policy: only an explicit FORCE_HTTPS enables forcing. The default is
// off so the site runs over http or https regardless of APP_ENV. Shield
// requires this key to exist (throws on null), so it is ALWAYS set.
$forceHttpsRaw = $resolveEnv('FORCE_HTTPS');
$forceHttps    = $forceHttpsRaw === null ? null : $toBool($forceHttpsRaw);
if ($forceHttpsRaw !== null && $forceHttps === null) {
    error_log('env-overrides: ignoring invalid FORCE_HTTPS value "' . $forceHttpsRaw . '"');
}
$app->set(
    'flight.force_https',
    $forceHttps ?? false
);
