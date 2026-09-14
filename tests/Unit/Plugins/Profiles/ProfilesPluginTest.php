<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Plugins\Profiles\Plugin;
use Pubvana\Plugins\Profiles\Services\ProfileBlockService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Profiles Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class ProfilesPluginTest extends TestCase
{
    public function testRegisterWiresServicesRoutesBlockAndCss(): void
    {
        $pdo = Sqlite::recreate();
        ProfilesSchema::create($pdo);
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
                return '/profile';
            }
        });
        $app->map('db', fn(): \PDO => $pdo);
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        self::assertInstanceOf(Profile::class, $app->profiles());
        self::assertInstanceOf(ProfileBlockService::class, $app->profileBlock());

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /profile', $handlers);
        self::assertContains('admin GET /profile/@userId', $handlers);
        self::assertContains('admin POST /profile/@userId/update', $handlers);
        self::assertContains('public GET /profile/@username', $handlers);
        self::assertContains('public GET /profile/@username/edit', $handlers);
        self::assertContains('public POST /profile/@username/update', $handlers);

        $blocks = $adext->get('block', 'available');
        self::assertArrayHasKey('pubvana.profiles.author-card', $blocks);
        self::assertSame('Author Card', $blocks['pubvana.profiles.author-card']['label']);

        $css = $adext->get('public.css', 'default');
        self::assertArrayHasKey('pubvana.profiles', $css);
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/Profiles/Config/Config.php';

        self::assertSame('profile', $config['routePrepend']);
    }
}
