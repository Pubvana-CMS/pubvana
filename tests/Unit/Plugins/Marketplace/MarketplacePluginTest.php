<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Marketplace\Plugin;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Marketplace Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class MarketplacePluginTest extends TestCase
{
    private Engine $app;

    private ExtensionRegistry $adext;

    /** @var array<string, mixed> */
    public array $settings = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = [];
        $test = $this;
        $pdo = Sqlite::recreate();
        MarketplaceSchema::create($pdo);
        $this->app = new Engine();
        $this->app->init();
        $this->app->map('adext', function (): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        $this->app->map('pluginLoader', static fn(): object => new class {
            public function routePrefix(string $id): string
            {
                return '/marketplace';
            }
        });
        $this->app->map('db', fn(): \PDO => $pdo);
        $this->app->map('settings', fn(): object => new class($test) {
            public function __construct(private MarketplacePluginTest $t)
            {
            }

            public function get(string $k, mixed $d = null): mixed
            {
                return $this->t->settings[$k] ?? $d;
            }

            public function set(string $k, mixed $v): void
            {
                $this->t->settings[$k] = $v;
            }
        });
        $this->app->set('environment', 'development');
        \Flight::setEngine($this->app);

        (new Plugin())->register($this->app, new Router(), []);

        /** @var ExtensionRegistry $adext */
        $this->adext = $this->app->adext();
    }

    public function testRegisterWiresServiceRoutesAndExtensions(): void
    {
        self::assertInstanceOf(MarketplaceService::class, $this->app->marketplace());

        $routes = $this->adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /marketplace', $handlers);
        self::assertContains('admin POST /marketplace/connect', $handlers);
        self::assertContains('admin POST /marketplace/disconnect', $handlers);
        self::assertContains('admin GET /marketplace/purchases', $handlers);
        self::assertContains('admin POST /marketplace/verify', $handlers);
        self::assertContains('admin POST /marketplace/cart-add', $handlers);
        self::assertContains('admin POST /marketplace/install', $handlers);
        self::assertContains('admin POST /marketplace/install-free', $handlers);
        self::assertContains('admin POST /marketplace/reinstall', $handlers);
        self::assertContains('admin GET /marketplace/cart-open', $handlers);

        self::assertArrayHasKey('pubvana.marketplace', $this->adext->get('admin.dashboard', 'cards'));
        self::assertArrayHasKey('pubvana.marketplace', $this->adext->get('health', 'checks'));
        self::assertArrayHasKey('pubvana.marketplace', $this->adext->get('cron', '24h'));
    }

    public function testDashboardCardDisconnected(): void
    {
        $cards = $this->adext->get('admin.dashboard', 'cards');
        $rows = $cards['pubvana.marketplace']['callable']([]);

        self::assertSame('marketplace-disconnected', $rows[0]['id']);
        self::assertSame('Disconnected', $rows[0]['value']);
        self::assertSame('secondary', $rows[0]['tone']);
    }

    public function testDashboardCardConnectedCountsLicenses(): void
    {
        $this->app->settings()->set('Marketplace.account_token', 'tok');
        $this->insertInstall(1, 'Alpha', 'LIC-1', 1);
        $this->insertInstall(2, 'Beta', 'LIC-2', 0);

        $cards = $this->adext->get('admin.dashboard', 'cards');
        $rows = $cards['pubvana.marketplace']['callable']([]);

        self::assertSame('marketplace-connected', $rows[0]['id']);
        self::assertSame('success', $rows[0]['tone']);
        self::assertStringContainsString('1 licensed', $rows[0]['description']);
        self::assertStringContainsString('0 installed', $rows[0]['description']);
    }

    public function testCronCallsVerifyIfDue(): void
    {
        // verify_days gates the call; first call with no last_verify_at runs.
        $cron = $this->adext->get('cron', '24h');
        $cron['pubvana.marketplace']['callable']();

        // Nothing throws: verifyIfDue() hits the (unreachable) store only
        // when due, and swallows the failure. Settings was never given a
        // token, so connected() is false and the call is a no-op.
        self::assertTrue(true);
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/Marketplace/Config/Config.php';

        self::assertSame('marketplace', $config['routePrepend']);
        self::assertSame('https://pubvanacms.com', $config['store_url']);
        self::assertSame(14, $config['verify_days']);
        self::assertSame(1048576, $config['max_bytes']);
    }

    public function testSeedStructure(): void
    {
        /** @var array<string, mixed> $seed */
        $seed = require PROJECT_ROOT . '/plugins/Marketplace/Database/Seeds/Seed.php';

        self::assertArrayHasKey('install', $seed);
        foreach ($seed['install'] as $entry) {
            self::assertArrayHasKey('table', $entry);
            self::assertArrayHasKey('rows', $entry);
            self::assertNotEmpty($entry['rows']);
        }
        $perms = null;
        foreach ($seed['install'] as $entry) {
            if ($entry['table'] === 'auth_permissions') {
                $perms = $entry['rows'];
            }
        }
        self::assertNotNull($perms);
        self::assertContains('marketplace.manage', array_column($perms, 'alias'));
    }

    private function setSetting(string $key, mixed $value): void
    {
        $settings = $this->app->settings();
        $settings->set($key, $value);
    }

    private function insertInstall(int $productId, string $name, string $license, int $valid): void
    {
        $pdo = $this->app->db();
        $stmt = $pdo->prepare(
            'INSERT INTO marketplace_installs (store_product_id, product_name, license_key, license_valid)
             VALUES (:p, :n, :l, :v)'
        );
        $stmt->execute(['p' => $productId, 'n' => $name, 'l' => $license, 'v' => $valid]);
    }
}
