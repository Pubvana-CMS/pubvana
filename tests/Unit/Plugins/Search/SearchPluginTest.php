<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Search;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Search\Plugin;
use Pubvana\Plugins\Search\Services\SearchService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * Search Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class SearchPluginTest extends TestCase
{
    public function testRegisterWiresServiceRoutesAndBlock(): void
    {
        $app = new Engine();
        $app->init();
        $app->map('adext', static function () use ($app): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), []);

        self::assertInstanceOf(SearchService::class, $app->search());

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /search', $handlers);
        self::assertContains('admin POST /search', $handlers);
        self::assertContains('public GET /search', $handlers);

        $blocks = $adext->get('block', 'available');
        self::assertArrayHasKey('pubvana.search.form', $blocks);
        self::assertSame('Search Form', $blocks['pubvana.search.form']['label']);
        self::assertSame('pubvana/search/public/blocks/search', $blocks['pubvana.search.form']['template']);
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/Search/Config/Config.php';

        self::assertSame('', $config['routePrepend']);
    }
}
