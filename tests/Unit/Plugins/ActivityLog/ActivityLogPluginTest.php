<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\ActivityLog;

use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\ActivityLog\Plugin;
use Pubvana\Plugins\ActivityLog\Services\ActivityLogService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ActivityLog Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class ActivityLogPluginTest extends TestCase
{
    public function testRegisterWiresServiceRouteAndCard(): void
    {
        $pdo = Sqlite::recreate();
        ActivityLogSchema::create($pdo);
        $app = new Engine();
        $app->init();
        $app->map('adext', static function () use ($app): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        $app->map('pluginLoader', static fn(): object => new class {
            public function routePrefix(string $id): string
            {
                return '/activity-log';
            }
        });
        $app->map('db', fn(): \PDO => $pdo);
        $app->map('auth', static fn(): object => new class {
            public function user(): ?object
            {
                return null;
            }
        });
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        self::assertInstanceOf(ActivityLogService::class, $app->activityLog());

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /activity-log', $handlers);

        // Admin route carries the activity_log.view permission gate.
        foreach ($routes as $route) {
            if ($route['path'] === '/activity-log') {
                $middleware = $route['middleware'][0] ?? null;
                self::assertInstanceOf(PermissionMiddleware::class, $middleware);
            }
        }

        self::assertArrayHasKey('pubvana.activity-log', $adext->get('admin.dashboard', 'cards'));

        // Dashboard card resolves through the service (empty table).
        $cards = $adext->get('admin.dashboard', 'cards');
        $rows = $cards['pubvana.activity-log']['callable']([]);
        self::assertSame('recent-activity', $rows[0]['id']);
        self::assertSame(0, $rows[0]['value']);
        self::assertSame('secondary', $rows[0]['tone']);
        self::assertSame('No admin activity in the last 24 hours.', $rows[0]['description']);
    }

    public function testDashboardCardReflectsRecentActivity(): void
    {
        $pdo = Sqlite::recreate();
        ActivityLogSchema::create($pdo);
        $app = new Engine();
        $app->init();
        $app->map('adext', static function () use ($app): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        $app->map('pluginLoader', static fn(): object => new class {
            public function routePrefix(string $id): string
            {
                return '/activity-log';
            }
        });
        $app->map('db', fn(): \PDO => $pdo);
        $app->map('auth', static fn(): object => new class {
            public function user(): ?object
            {
                return null;
            }
        });
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        $service = $app->activityLog();
        $service->log(['action' => 'create', 'entity_type' => 'page', 'entity_name' => 'About']);
        unset($_SERVER['REMOTE_ADDR']);

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $cards = $adext->get('admin.dashboard', 'cards');
        $rows = $cards['pubvana.activity-log']['callable']([]);
        self::assertSame(1, $rows[0]['value']);
        self::assertSame('info', $rows[0]['tone']);
        self::assertStringContainsString('1 admin actions', $rows[0]['description']);
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/ActivityLog/Config/Config.php';

        self::assertSame('activity-log', $config['routePrepend']);
        self::assertTrue($config['track_admin_actions']);
        self::assertSame(365, $config['retention_days']);
    }
}
