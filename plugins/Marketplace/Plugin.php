<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace;

use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use Pubvana\Plugins\Marketplace\Controllers\MarketplaceAdminController;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Plugins\SiteHealth\Services\CheckResult;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Marketplace Plugin - the companion app for the Pubvana Digital Store.
 *
 * Registers the `marketplace` singleton, the Tools > Marketplace admin, and
 * the 24h cron task that performs the ~2 week purchase/license verification
 * (phone-home) at pubvanacms.com.
 *
 * @package Pubvana\Plugins\Marketplace
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/marketplace');
        $config['route_prefix'] = $prefix;

        $app->map('marketplace', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new MarketplaceService($app->db(), $app, $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $prefix = $app->pluginLoader()->routePrefix('pubvana/marketplace');
        $manage = new PermissionMiddleware($app, 'marketplace.manage');

        // Admin routes (adext prepends /admin)
        $adext->addRoutes('admin', [
            ['GET',  $prefix,                              [MarketplaceAdminController::class, 'index'],        [$manage]],
            ['POST', $prefix . '/connect',                 [MarketplaceAdminController::class, 'connect'],     [$manage]],
            ['POST', $prefix . '/disconnect',              [MarketplaceAdminController::class, 'disconnect'],  [$manage]],
            ['GET',  $prefix . '/purchases',               [MarketplaceAdminController::class, 'purchases'],   [$manage]],
            ['POST', $prefix . '/verify',                  [MarketplaceAdminController::class, 'verify'],      [$manage]],
            ['POST', $prefix . '/cart-add',                [MarketplaceAdminController::class, 'addToCart'],   [$manage]],
            ['POST', $prefix . '/install',                 [MarketplaceAdminController::class, 'install'],     [$manage]],
            ['POST', $prefix . '/install-free',            [MarketplaceAdminController::class, 'installFree'], [$manage]],
            ['POST', $prefix . '/reinstall',               [MarketplaceAdminController::class, 'reinstallAll'], [$manage]],
            ['GET',  $prefix . '/cart-open',               [MarketplaceAdminController::class, 'cartOpen'],    [$manage]],
        ], 'pubvana.marketplace');

        // Dashboard card: shows connection status and purchase count
        $adext->register('admin.dashboard', 'cards', 'pubvana.marketplace', [
            'label'    => 'Marketplace',
            'priority' => 15,
            'callable' => fn(array $context): array => $this->dashboardCards($app, $prefix),
        ]);

        // Site Health check: reports Marketplace connection status
        if (class_exists(CheckResult::class)) {
            $adext->register('health', 'checks', 'pubvana.marketplace', [
                'label'    => 'Marketplace',
                'priority' => 50,
                'callable' => fn(): CheckResult => $this->healthCheck($app),
            ]);
        }

        // Core cron system (docs/Cron.md): the 24h task enforces the
        // verify_days cadence internally, so the store is actually phoned
        // home about every two weeks. Real failures throw (CronService logs
        // FAILED and exits 2); graceful "not due / nothing to do" stays quiet.
        $adext->register('cron', '24h', 'pubvana.marketplace', [
            'label'    => 'Marketplace purchase verification (phone-home)',
            'priority' => 50,
            'callable' => function () use ($app): void {
                $app->marketplace()->verifyIfDue();
            },
        ]);
    }

    /**
     * Dashboard card: connection status and purchase summary.
     *
     * @param Engine<object> $app
     * @param string         $prefix Route prefix for this plugin
     * @return list<array<string, mixed>>
     */
    private function dashboardCards(Engine $app, string $prefix): array
    {
        $service = $app->marketplace();
        $connected = $service->connected();
        $email = $service->accountEmail();

        if (!$connected) {
            return [
                [
                    'id'          => 'marketplace-disconnected',
                    'label'       => 'Marketplace',
                    'value'       => 'Disconnected',
                    'icon'        => 'ti-store',
                    'tone'        => 'secondary',
                    'group'       => 'tools',
                    'href'        => $prefix,
                    'description' => 'Connect a Pubvana account to browse and install purchases.',
                ],
            ];
        }

        $records = $service->localInstallRecords();
        $installedCount = 0;
        $licensedCount = 0;
        foreach ($records as $r) {
            if (!empty($r['installed'])) {
                $installedCount++;
            }
            if (!empty($r['license_valid'])) {
                $licensedCount++;
            }
        }

        $tone = $licensedCount > 0 ? 'success' : 'warning';
        $description = "{$licensedCount} licensed, {$installedCount} installed. Connected as {$email}.";

        return [
            [
                'id'          => 'marketplace-connected',
                'label'       => 'Marketplace',
                'value'       => 'Connected',
                'icon'        => 'ti-store',
                'tone'        => $tone,
                'group'       => 'tools',
                'href'        => $prefix,
                'description' => $description,
            ],
        ];
    }

    /**
     * Site Health check for Marketplace connection and license status.
     *
     * @param Engine<object> $app
     */
    private function healthCheck(Engine $app): CheckResult
    {
        $service = $app->marketplace();

        if (!$service->connected()) {
            return new CheckResult(
                id: 'marketplace-connection',
                name: 'Marketplace Connection',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::WARNING,
                message: 'Marketplace is not connected to a Pubvana account.',
                remediation: 'Visit Tools > Marketplace and connect your account to browse and install purchases.'
            );
        }

        $records = $service->localInstallRecords();
        $licensedCount = 0;
        $expiredCount = 0;
        foreach ($records as $r) {
            if (!empty($r['license_valid'])) {
                $licensedCount++;
            }
            $expires = (string) ($r['expires'] ?? '');
            if ($expires !== '' && strtotime($expires) !== false && strtotime($expires) < time()) {
                $expiredCount++;
            }
        }

        if ($expiredCount > 0) {
            return new CheckResult(
                id: 'marketplace-licenses',
                name: 'Marketplace Licenses',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::WARNING,
                message: "{$expiredCount} of {$licensedCount} licensed items have expired licenses.",
                remediation: 'Visit Tools > Marketplace > Purchases and verify against pubvanacms.com.'
            );
        }

        return new CheckResult(
            id: 'marketplace-connection',
            name: 'Marketplace Connection',
            category: CheckResult::CAT_PLUGINS,
            status: CheckResult::PASS,
            message: "Connected as {$service->accountEmail()}. {$licensedCount} licensed items tracked."
        );
    }
}