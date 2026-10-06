<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Plugin;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Plugin wiring for the AI Assistant.
 *
 * The help catalog and the fact-check error messages are built from the
 * configured API base, so Plugin::register() must hand the services the
 * /api prefix. Passing the public route prefix (/ai) produced catalog
 * paths and error text that pointed at routes that do not exist.
 */
#[CoversClass(Plugin::class)]
final class AiPluginTest extends TestCase
{
    public function testRegisterPassesTheApiBaseToTheService(): void
    {
        $pdo = Sqlite::recreate();

        $app = new Engine();
        $app->init();
        $app->map('adext', static function (): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        $app->map('pluginLoader', static fn(): object => new class {
            public function routePrefix(string $id): string
            {
                return '/ai';
            }

            public function apiPrefix(string $id): string
            {
                return '/api/ai';
            }
        });
        $app->map('db', static fn(): \PDO => $pdo);
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        $catalog = $app->ai()->helpCatalog();
        self::assertSame('/api/ai/posts/{slug}', $catalog['posts.read']['path']);
        self::assertSame(['/api/ai/posts', '/api/ai/posts/{slug}'], $catalog['posts.read']['endpoints']);
    }
}
