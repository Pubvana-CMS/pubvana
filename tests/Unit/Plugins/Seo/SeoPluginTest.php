<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Plugin;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * Plugin wiring for the SEO plugin.
 *
 * The grant paths and the routes are both built from Config/Config.php, so
 * this catches a change to one without the other.
 */
#[CoversClass(Plugin::class)]
final class SeoPluginTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     * @return array{0: Engine<object>, 1: ExtensionRegistry}
     */
    private function register(array $config = []): array
    {
        $app = new Engine();
        $app->init();
        $app->map('adext', static function (): ExtensionRegistry {
            static $registry = null;
            if ($registry === null) {
                $registry = new ExtensionRegistry();
            }

            return $registry;
        });
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), $config);

        return [$app, $app->adext()];
    }

    public function testRegistersSeoGrants(): void
    {
        [, $registry] = $this->register();
        $grants = $registry->get('ai.grant', 'default');

        self::assertArrayHasKey('seo.read', $grants);
        self::assertArrayHasKey('seo.manage', $grants);
        self::assertSame('GET', $grants['seo.read']['method']);
        self::assertSame('/api/ai/seo/settings', $grants['seo.read']['path']);
        self::assertContains('/api/ai/seo/analyze', $grants['seo.read']['endpoints']);
        self::assertSame('POST', $grants['seo.manage']['method']);
        self::assertSame('/api/ai/seo/settings', $grants['seo.manage']['path']);
    }

    public function testRegistersAiRoutes(): void
    {
        [, $registry] = $this->register();

        $routes = [];
        foreach ($registry->getRoutes() as $route) {
            $routes[] = $route['method'] . ' ' . $route['path'];
        }

        self::assertContains('GET /api/ai/seo/help', $routes);
        self::assertContains('GET /api/ai/seo/settings', $routes);
        self::assertContains('POST /api/ai/seo/settings', $routes);
        self::assertContains('POST /api/ai/seo/analyze', $routes);
        self::assertContains('GET /sitemap.xml', $routes);
        self::assertContains('GET /robots.txt', $routes);
        self::assertContains('GET /llms.txt', $routes);
    }

    public function testAiBaseComesFromTheConfig(): void
    {
        [, $registry] = $this->register(['aiApiPrefix' => '/api/ai/seo-v2']);

        $grants = $registry->get('ai.grant', 'default');
        self::assertSame('/api/ai/seo-v2/settings', $grants['seo.read']['path']);
        self::assertSame('/api/ai/seo-v2/settings', $grants['seo.manage']['path']);

        $paths = array_column($registry->getRoutes(), 'path');
        self::assertContains('/api/ai/seo-v2/help', $paths);
        self::assertContains('/api/ai/seo-v2/settings', $paths);
        self::assertContains('/api/ai/seo-v2/analyze', $paths);
        self::assertContains('/sitemap.xml', $paths);
    }
}
