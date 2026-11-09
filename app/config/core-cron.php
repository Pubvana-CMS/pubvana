<?php

/**
 * Core cron tasks.
 *
 * Loaded by services.php and by core-admin.php. The cron script loads
 * services.php and never core-admin.php, and the web request loads both, so
 * this lives in one file to keep the two boot paths in step. The guard stops
 * a second registration in the same process.
 *
 * Plugins register their own tasks from Plugin.php, which both paths load.
 *
 * @package Pubvana\Config
 */

/** @var \flight\Engine $app */

// Flies one flag on the app, not a process constant, so each app instance
// registers once even when both boot paths load this file.
if ($app->get('coreCron.registered') === true) {
    return;
}

$app->set('coreCron.registered', true);

$adext = $app->adext();

// Trust check (4h cron). The service self-throttles to one full batch per
// 24h and honors home-site malicious findings on every tick, so the cron
// cadence bounds discovery time without flooding pubvanacms.com.
$adext->register('cron', '4h', 'pubvana.core', [
    'label'    => 'Trust check against pubvanacms.com',
    'priority' => 60,
    'callable' => function () use ($app): void {
        $app->trustClient()->checkIfDue();
    },
]);

// Page cache cleanup (24h cron). Deletes page files older than twice the
// TTL. A page with an old version number already counts as a miss, so this
// only frees disk space.
$adext->register('cron', '24h', 'pubvana.core.page-cache', [
    'label'    => 'Prune cached pages',
    'priority' => 70,
    'callable' => function () use ($app): int {
        $pageCache = $app->get('pageCache.instance');

        return $pageCache instanceof \Pubvana\Services\PageCacheService
            ? $pageCache->prune()
            : 0;
    },
]);
