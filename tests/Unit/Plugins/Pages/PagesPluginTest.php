<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use flight\Engine;
use flight\net\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Pages\Plugin;
use Pubvana\Plugins\Pages\Services\PagesService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Pages Plugin registration coverage.
 */
#[CoversClass(Plugin::class)]
final class PagesPluginTest extends TestCase
{
    public function testRegisterWiresServiceRoutesAndExtensions(): void
    {
        $pdo = Sqlite::recreate();
        PagesSchema::create($pdo);
        $app = $this->pluginEngine($pdo);

        (new Plugin())->register($app, new Router(), ['route_prefix' => '/page']);

        self::assertInstanceOf(PagesService::class, $app->pages());

        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $routes = $adext->getRoutes();
        $handlers = array_map(static fn($r): string => $r['scope'] . ' ' . $r['method'] . ' ' . $r['path'], $routes);
        self::assertContains('admin GET /page', $handlers);
        self::assertContains('admin GET /page/create', $handlers);
        self::assertContains('admin POST /page/store', $handlers);
        self::assertContains('admin GET /page/@id/edit', $handlers);
        self::assertContains('admin POST /page/@id/update', $handlers);
        self::assertContains('admin POST /page/@id/delete', $handlers);
        self::assertContains('admin GET /page/@id/revisions', $handlers);
        self::assertContains('admin POST /page/@id/restore/@revisionId', $handlers);
        self::assertContains('public GET /page', $handlers);
        self::assertContains('public GET /page/@slug', $handlers);

        self::assertArrayHasKey('pubvana.pages', $adext->get('admin.dashboard', 'cards'));
        self::assertArrayHasKey('pubvana.pages', $adext->get('admin.dashboard', 'sections'));
        self::assertArrayHasKey('pubvana.pages', $adext->get('nav.linkable', 'default'));
        self::assertArrayHasKey('pubvana.pages', $adext->get('brokenlinks', 'source'));
        self::assertArrayHasKey('pubvana.pages', $adext->get('search', 'provider'));
        self::assertArrayHasKey('pubvana.pages', $adext->get('comments.host', 'content'));

        // Dashboard callables resolve through the service.
        $cards = $adext->get('admin.dashboard', 'cards');
        self::assertSame('Pages', $cards['pubvana.pages']['label']);
        $sections = $adext->get('admin.dashboard', 'sections');
        self::assertSame('Pages', $sections['pubvana.pages']['label']);

        // Search provider + comments host + nav linkable resolve.
        $search = $adext->get('search', 'provider');
        self::assertSame([], $search['pubvana.pages']['callable']('nothing-here'));
        $hosts = $adext->get('comments.host', 'content');
        self::assertSame([], $hosts['pubvana.pages']['callable']());
        $nav = $adext->get('nav.linkable', 'default');
        self::assertSame([], $nav['pubvana.pages']['callable']());

        // Broken-links source resolves (empty table -> empty list).
        $sources = $adext->get('brokenlinks', 'source');
        self::assertSame([], $sources['pubvana.pages']['callable']());

        // Homepage provider: the token follows routePrepend, so Pages answers
        // to 'page' in CMS.homepageType.
        $providers = $adext->get('homepage', 'provider');
        self::assertArrayHasKey('pubvana.pages', $providers);
        self::assertSame('page', $providers['pubvana.pages']['token']);
        self::assertSame('Static Page', $providers['pubvana.pages']['label']);
        self::assertSame(30, $providers['pubvana.pages']['priority']);

        // The page picker is a field the provider owns, so core carries no
        // Pages setting. Its options load on demand from the pages service.
        $page = $app->pages()->createPage(
            ['title' => 'Home Only', 'content' => '', 'status' => 'published'],
            1
        );
        $fields = $providers['pubvana.pages']['fields'];
        self::assertCount(1, $fields);
        self::assertSame('CMS.homepagePageId', $fields[0]['key']);
        self::assertSame('select', $fields[0]['type']);
        self::assertSame([(int) $page->id => 'Home Only'], $fields[0]['options_callable']());
    }

    public function testHomepageProviderDeclinesWithoutAChosenPage(): void
    {
        $pdo = Sqlite::recreate();
        PagesSchema::create($pdo);
        // The settings double returns the default, so no page is chosen.
        $app = $this->pluginEngine($pdo);

        (new Plugin())->register($app, new Router(), ['route_prefix' => '/page']);

        $provider = $app->adext()->get('homepage', 'provider')['pubvana.pages'];

        // Declining hands "/" to the next provider instead of erroring.
        self::assertFalse($provider['callable']());
    }

    public function testHomepageProviderDeclinesWhenTheChosenPageIsUnpublished(): void
    {
        $pdo = Sqlite::recreate();
        PagesSchema::create($pdo);
        $app = $this->pluginEngine($pdo, ['CMS.homepagePageId' => '999']);

        (new Plugin())->register($app, new Router(), ['route_prefix' => '/page']);

        $provider = $app->adext()->get('homepage', 'provider')['pubvana.pages'];

        self::assertFalse($provider['callable']());
    }

    /**
     * Engine with the services Plugin::register() reads, over a test DB.
     *
     * @param array<string, mixed> $settings Values the settings double serves
     *
     * @return Engine<object>
     */
    private function pluginEngine(\PDO $pdo, array $settings = []): Engine
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
        $app->map('pluginLoader', static fn(): object => new class {
            public function routePrefix(string $id): string
            {
                return '/page';
            }
        });
        $app->map('db', static fn(): \PDO => $pdo);
        $app->map('settings', static fn(): object => new class($settings) {
            /** @param array<string, mixed> $values */
            public function __construct(private array $values)
            {
            }

            public function get(string $k, mixed $d = null): mixed
            {
                return $this->values[$k] ?? $d;
            }
        });
        \Flight::setEngine($app);

        return $app;
    }

    public function testConfigShape(): void
    {
        /** @var array<string, mixed> $config */
        $config = require PROJECT_ROOT . '/plugins/Pages/Config/Config.php';

        self::assertSame('page', $config['routePrepend']);
        self::assertSame(15, $config['max_revisions']);
    }
}
