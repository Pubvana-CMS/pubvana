<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Plugin;
use Pubvana\Plugins\Blog\Services\BlogService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Blog Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class BlogPluginTest extends TestCase
{
    public function testRegisterWiresServiceRoutesAndExtensions(): void
    {
        $pdo = Sqlite::recreate();
        BlogSchema::create($pdo);
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
                return '/blog';
            }
        });
        $app->map('db', fn(): \PDO => $pdo);
        $app->map('settings', static fn(): object => new class {
            public function get(string $k, mixed $d = null): mixed
            {
                return $d;
            }
        });
        \Flight::setEngine($app);

        (new Plugin())->register($app, new Router(), ['route_prefix' => '/blog']);

        self::assertInstanceOf(BlogService::class, $app->blog());

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /blog', $handlers);
        self::assertContains('admin POST /blog/store', $handlers);
        self::assertContains('admin GET /blog/categories', $handlers);
        self::assertContains('admin GET /blog/tags', $handlers);
        self::assertContains('public GET /blog', $handlers);
        self::assertContains('public GET /blog/@slug', $handlers);
        self::assertContains('public GET /feed', $handlers);
        self::assertContains('public GET /atom.xml', $handlers);

        self::assertArrayHasKey('pubvana.blog', $adext->get('admin.dashboard', 'cards'));
        self::assertArrayHasKey('pubvana.blog', $adext->get('admin.dashboard', 'sections'));
        self::assertArrayHasKey('pubvana.blog.recent-posts', $adext->get('block', 'available'));
        self::assertArrayHasKey('pubvana.blog.categories', $adext->get('block', 'available'));
        self::assertArrayHasKey('pubvana.blog.tags', $adext->get('block', 'available'));
        self::assertArrayHasKey('pubvana.blog.archive', $adext->get('block', 'available'));
        self::assertArrayHasKey('pubvana.blog.related-posts', $adext->get('block', 'available'));
        self::assertArrayHasKey('pubvana.blog', $adext->get('search', 'provider'));
        self::assertArrayHasKey('pubvana.blog', $adext->get('comments.host', 'content'));
        self::assertArrayHasKey('pubvana.blog', $adext->get('nav.linkable', 'default'));
        self::assertArrayHasKey('pubvana.blog', $adext->get('brokenlinks', 'source'));

        // Homepage provider: the token follows routePrepend, so Blog answers
        // to 'blog' in CMS.homepageType. Priority 20 keeps it first, which is
        // what serves "/" when the setting is unset or names a provider that
        // is no longer registered.
        $providers = $adext->get('homepage', 'provider');
        self::assertArrayHasKey('pubvana.blog', $providers);
        self::assertSame('blog', $providers['pubvana.blog']['token']);
        self::assertSame('Blog Feed', $providers['pubvana.blog']['label']);
        self::assertSame(20, $providers['pubvana.blog']['priority']);
        self::assertIsCallable($providers['pubvana.blog']['callable']);

        // Host callables resolve through the service, not inline SQL.
        $nav = $adext->get('nav.linkable', 'default');
        self::assertSame([], $nav['pubvana.blog']['callable']());
        $sources = $adext->get('brokenlinks', 'source');
        self::assertSame([], $sources['pubvana.blog']['callable']());

        // Block providers resolve through the service.
        $blocks = $adext->get('block', 'available');
        $recent = $blocks['pubvana.blog.recent-posts']['provider']([]);
        self::assertSame('Recent Posts', $recent['title']);
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/Blog/Config/Config.php';

        self::assertSame('blog', $config['routePrepend']);
        self::assertSame(15, $config['max_revisions']);
    }
}
