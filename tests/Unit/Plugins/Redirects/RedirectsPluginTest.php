<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Redirects;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Redirects\Plugin;
use Pubvana\Plugins\Redirects\Services\RedirectLinksService;
use Pubvana\Plugins\Redirects\Services\RedirectsService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Redirects Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class RedirectsPluginTest extends TestCase
{
    public function testRegisterWiresServicesRoutesAndExtensions(): void
    {
        $pdo = Sqlite::recreate();
        RedirectsSchema::create($pdo);
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
                return '/redirects';
            }
        });
        $app->map('db', fn(): \PDO => $pdo);
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        self::assertInstanceOf(RedirectsService::class, $app->redirects());
        self::assertInstanceOf(RedirectLinksService::class, $app->redirectLinks());

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /redirects', $handlers);
        self::assertContains('admin GET /redirects/create', $handlers);
        self::assertContains('admin POST /redirects/store', $handlers);
        self::assertContains('admin GET /redirects/@id/edit', $handlers);
        self::assertContains('admin POST /redirects/@id/update', $handlers);
        self::assertContains('admin POST /redirects/@id/delete', $handlers);
        self::assertContains('admin GET /redirects/404-manager', $handlers);
        self::assertContains('admin POST /redirects/404-manager/@id/ignore', $handlers);
        self::assertContains('admin POST /redirects/404-manager/@id/unignore', $handlers);
        self::assertContains('admin POST /redirects/404-manager/@id/delete', $handlers);

        self::assertArrayHasKey('pubvana.redirects', $adext->get('admin.dashboard', 'cards'));
        self::assertArrayHasKey('pubvana.redirects', $adext->get('admin.dashboard', 'sections'));

        // Dashboard callables resolve through the services.
        $cards = $adext->get('admin.dashboard', 'cards');
        $cardRows = $cards['pubvana.redirects']['callable']([]);
        self::assertSame('active-404s', $cardRows[0]['id']);
        self::assertSame(0, $cardRows[0]['value']);
        self::assertSame('success', $cardRows[0]['tone']);
        self::assertSame('enabled-redirects', $cardRows[1]['id']);

        $sections = $adext->get('admin.dashboard', 'sections');
        $sectionRows = $sections['pubvana.redirects']['callable']([]);
        self::assertSame('recent-redirect-links', $sectionRows[0]['id']);
        self::assertSame([], $sectionRows[0]['items']);
        self::assertSame('No redirect links have been recorded.', $sectionRows[0]['empty_state']);
    }

    public function testDashboardReflectsCounts(): void
    {
        $pdo = Sqlite::recreate();
        RedirectsSchema::create($pdo);
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
                return '/redirects';
            }
        });
        $app->map('db', fn(): \PDO => $pdo);
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        $app->redirects()->create(['source_path' => '/a', 'target_url' => '/b', 'enabled' => '1']);
        $pdo->exec("INSERT INTO redirects_links (source_path, hit_count, last_seen_at) VALUES ('/old', 3, '2026-01-05 10:00:00')");

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $cards = $adext->get('admin.dashboard', 'cards');
        $cardRows = $cards['pubvana.redirects']['callable']([]);
        self::assertSame(1, $cardRows[0]['value']);
        self::assertSame('danger', $cardRows[0]['tone']);
        self::assertSame(1, $cardRows[1]['value']);

        $sections = $adext->get('admin.dashboard', 'sections');
        $sectionRows = $sections['pubvana.redirects']['callable']([]);
        self::assertCount(1, $sectionRows[0]['items']);
        self::assertSame('/old', $sectionRows[0]['items'][0]['label']);
        self::assertStringContainsString('3 hits', $sectionRows[0]['items'][0]['meta']);
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/Redirects/Config/Config.php';

        self::assertSame('redirects', $config['routePrepend']);
        self::assertSame(['/admin', '/api'], $config['skip_prefixes']);
        self::assertSame(['/admin', '/api'], $config['incoming_404s']['skip_prefixes']);
    }
}
