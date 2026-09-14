<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use flight\net\Router;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\PluginLoader;
use Pubvana\Services\PluginView;
use Pubvana\Services\PluginViewContextMiddleware;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;

/**
 * PluginLoader deep suite: discovery (local manifests + composer
 * installed.json), plugin_state sync and gating, the full load flow
 * for local plugins and vendor packages (config merge, route groups,
 * hook registration, view paths, Plugin.php loading), priority
 * ordering, and the migration-config builders.
 *
 * Fixtures are generated under a temp plugins/vendor root with unique
 * namespaces per test, so require_once'd Plugin classes never collide.
 * The PROJECT_ROOT-relative migration-pattern helpers only probe real
 * directories, so those fixtures create (and clean up) uniquely named
 * dirs under the real repo plugins/ and vendor/ trees.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PluginLoader::class)]
final class PluginLoaderTest extends TestCase
{
    private string $tmpRoot;

    /** @var string Unique fixture prefix for dirs/classes in this test */
    private string $fxId;

    /** @var array<int, string> Repo-side fixture paths to remove in tearDown */
    private array $repoPaths = [];

    /** @var array<int, array{id: string, app: object, config: array<string, mixed>}> */
    private array $registerLog = [];

    private PDO $pdo;

    /** @var Engine<object>|null Memoized per-test global engine */
    private ?Engine $testApp = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->fxId = 'fx' . uniqid();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-pluginloader-' . $this->fxId;
        mkdir($this->tmpRoot . '/plugins', 0777, true);
        mkdir($this->tmpRoot . '/vendor/composer', 0777, true);
        file_put_contents($this->tmpRoot . '/vendor/composer/installed.json', '{"packages":[]}');
        $GLOBALS['fixture_register_log'] = [];
        $this->registerLog = [];
    }

    protected function tearDown(): void
    {
        $this->deletePath($this->tmpRoot);
        foreach ($this->repoPaths as $path) {
            $this->deletePath(PROJECT_ROOT . '/' . $path);
        }
        unset($GLOBALS['fixture_register_log']);
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // discoverLocal()
    // -----------------------------------------------------------------

    public function testDiscoverLocalParsesManifestsAndAppliesDefaults(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
            'semver'    => '1.2.3',
        ]);

        $discovered = $this->loader()->discoverLocal();

        self::assertArrayHasKey('pubvana/alpha', $discovered);
        $info = $discovered['pubvana/alpha'];

        self::assertSame('local', $info['source']);
        self::assertSame('_fxalpha', $info['folder']);
        self::assertSame($this->fxNs('\Alpha'), $info['namespace']);
        self::assertSame('1.2.3', $info['version']);
        self::assertSame(['enabled' => true, 'priority' => 50], $info['config']);
        self::assertSame(50, $info['priority']);
        self::assertSame('pubvana/alpha', $info['manifest']['name']);
    }

    public function testDiscoverLocalSkipsFoldersWithoutValidManifests(): void
    {
        mkdir($this->tmpRoot . '/plugins/no-manifest', 0777, true);
        mkdir($this->tmpRoot . '/plugins/bad-json', 0777, true);
        file_put_contents($this->tmpRoot . '/plugins/bad-json/pubvana.json', '{nope');
        // A manifest with no name key: the folder becomes the plugin ID.
        $this->writeLocalPlugin('_fxnoname', null, ['namespace' => $this->fxNs('\NoName')]);

        $discovered = $this->loader()->discoverLocal();

        self::assertCount(1, $discovered);
        self::assertArrayHasKey('_fxnoname', $discovered);
        self::assertSame(50, $discovered['_fxnoname']['priority']);
    }

    public function testDiscoverLocalInheritsAppConfigAndPriority(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
        ]);

        $loader = new PluginLoader(
            $this->engine(),
            $this->engine()->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            ['pubvana/alpha' => ['enabled' => false, 'priority' => 9, 'custom' => 'kept']]
        );

        $info = $loader->discoverLocal()['pubvana/alpha'];

        self::assertSame(9, $info['priority']);
        self::assertSame('kept', $info['config']['custom']);
        self::assertFalse($info['config']['enabled']);
    }

    // -----------------------------------------------------------------
    // discoverVendor()
    // -----------------------------------------------------------------

    public function testDiscoverVendorFiltersPackagesByType(): void
    {
        // A library type package: never a plugin.
        $this->writeVendorPackage('acme/library', $this->fxNs('\Lib'), 'library');
        // A pubvana-plugin: kept.
        $this->writeVendorPackage('acme/tool', $this->fxNs('\Tool'), 'pubvana-plugin');
        // A foundation type under the trusted namespace: kept.
        $this->writeVendorPackage('enlivenapp/fake', $this->fxNs('\Fake'), 'flightphp-foundation');
        // A foundation type outside the trusted namespace: kept as a normal
        // plugin here (the foundation gate also requires the namespace).
        $this->writeVendorPackage('third-party/pretend', $this->fxNs('\Pretend'), 'flightphp-foundation');
        // A plugin type without a PSR-4 mapping: skipped.
        $this->writeVendorPackage('acme/nonsr4', $this->fxNs('\NoMap'), 'pubvana-plugin', skipPsr4: true);
        // Listed in installed.json but not on disk: skipped.
        $file = $this->tmpRoot . '/vendor/composer/installed.json';
        $installed = json_decode((string) file_get_contents($file), true);
        $installed['packages'] ??= [];
        $installed['packages'][] = [
            'name'     => 'ghost/pkg',
            'type'     => 'pubvana-plugin',
            'version'  => '1.0.0',
            'autoload' => ['psr-4' => ['Ghost\\Pkg\\' => 'src/']],
        ];
        file_put_contents($file, (string) json_encode($installed));

        $discovered = $this->loader()->discoverVendor();

        self::assertSame(
            ['acme/tool', 'enlivenapp/fake', 'third-party/pretend'],
            array_keys($discovered)
        );
    }

    public function testDiscoverVendorPrefersTheComposerJsonTypeOverTheSnapshot(): void
    {
        // installed.json snapshot says library; the package's own
        // composer.json says pubvana-plugin. composer.json is authoritative.
        $this->writeVendorPackage('acme/tool', $this->fxNs('\Tool'), 'pubvana-plugin', snapshotType: 'library');

        $discovered = $this->loader()->discoverVendor();

        self::assertArrayHasKey('acme/tool', $discovered);
        self::assertSame('pubvana-plugin', $discovered['acme/tool']['type']);
    }

    public function testDiscoverVendorRequiresAnInstalledJsonSnapshot(): void
    {
        $this->writeVendorPackage('acme/tool', $this->fxNs('\Tool'), 'pubvana-plugin');
        unlink($this->tmpRoot . '/vendor/composer/installed.json');

        self::assertSame([], $this->loader()->discoverVendor());
    }

    // -----------------------------------------------------------------
    // plugin_state sync, defaults, and gating
    // -----------------------------------------------------------------

    public function testLoadPluginsInsertsFirstDiscoveryDefaultsAndLocksFoundation(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
        ]);
        $this->writeVendorPackage('enlivenapp/flight-shield', $this->fxNs('\Fake'), 'flightphp-foundation');

        $loader = $this->loader();
        $loader->loadPlugins();

        // Untrusted plugin: disabled, default priority, not required.
        self::assertSame(
            ['enabled' => false, 'priority' => 50, 'required' => false],
            $loader->getPluginState('pubvana/alpha')
        );
        self::assertFalse($loader->isEnabled('pubvana/alpha'));

        // Trusted foundation: enabled, required, locked to its required-stack
        // priority (shield = 5).
        self::assertSame(
            ['enabled' => true, 'priority' => 5, 'required' => true],
            $loader->getPluginState('enlivenapp/flight-shield')
        );
        self::assertTrue($loader->isEnabled('enlivenapp/flight-shield'));
        self::assertTrue($loader->isRequired('enlivenapp/flight-shield'));
    }

    public function testExistingPluginStateRowsAreNeverModifiedBySync(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
        ]);

        // An admin-controlled row that contradicts the app config.
        $state = new \Pubvana\Models\PluginState($this->pdo);
        $state->plugin_id = 'pubvana/alpha';
        $state->enabled = true;
        $state->priority = 7;
        $state->required = false;
        $state->insert();

        $app = $this->engine();
        $loader = new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            // App config says disabled at priority 99: the DB row must win.
            ['pubvana/alpha' => ['enabled' => false, 'priority' => 99]]
        );
        $loader->loadPlugins();

        self::assertSame(
            ['enabled' => true, 'priority' => 7, 'required' => false],
            $loader->getPluginState('pubvana/alpha')
        );
        self::assertTrue($loader->isEnabled('pubvana/alpha'));
    }

    public function testIsEnabledFallsBackToTheAppConfigWithoutStates(): void
    {
        $loader = new PluginLoader(
            $this->engine(),
            $this->engine()->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            ['pubvana/alpha' => ['enabled' => true], 'pubvana/beta' => []]
        );

        self::assertTrue($loader->isEnabled('pubvana/alpha'));
        self::assertFalse($loader->isEnabled('pubvana/beta'));
        self::assertFalse($loader->isEnabled('pubvana/unknown'));
    }

    // -----------------------------------------------------------------
    // The load flow
    // -----------------------------------------------------------------

    public function testLoadPluginsLoadsAnEnabledLocalPluginEndToEnd(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
            'semver'    => '1.2.3',
            'provides'  => [
                'admin.menu' => [
                    'content' => [['label' => 'Alpha', 'url' => '/alpha']],
                ],
                'public.css' => [
                    'main' => ['url' => '/plugins/alpha/assets/x.css'],
                ],
                'block' => [
                    'available' => ['card' => ['label' => 'Card', 'template' => 'pubvana/alpha/blocks/card']],
                ],
            ],
        ], [
            'config'      => "return ['routePrepend' => 'alpha', 'in_config' => 'yes'];",
            'routes'      => "<?php\n\$router->get('/index', function () { return 'alpha'; });",
            'adminRoutes' => "<?php\n\$router->get('/overview', function () { return 'admin'; });",
        ]);

        $app = $this->engine();
        $loader = new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            ['pubvana/alpha' => ['enabled' => true, 'priority' => 7, 'extra' => 'override']]
        );
        $loaded = $loader->loadPlugins();

        // The plugin instance registered and was recorded as loaded.
        self::assertCount(1, $loaded);
        self::assertArrayHasKey('pubvana/alpha', $loader->getLoaded());

        // register() received the engine, router, and merged config.
        $this->assertRegistered('pubvana/alpha');
        $entry = $this->registerEntry('pubvana/alpha');
        self::assertSame($app, $entry['app']);
        self::assertSame('yes', $entry['config']['in_config'], 'Config/Config.php value');
        self::assertSame('override', $entry['config']['extra'], 'app-level override wins');
        self::assertSame('alpha', $entry['config']['routePrepend']);
        self::assertArrayNotHasKey('enabled', $entry['config'], 'gating keys never reach the plugin');
        self::assertArrayNotHasKey('priority', $entry['config']);

        // Views: the local Views/ dir is mapped on PluginView.
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);
        self::assertSame($this->tmpRoot . '/plugins/_fxalpha/Views', $view->getPluginPath('pubvana/alpha'));

        // Hooks from the manifest are in the registry. Keys are the plugin
        // ID (with its slash) + the manifest key.
        $menu = $app->adext()->get('admin.menu', 'content');
        self::assertSame('Alpha', $menu['pubvana/alpha.0']['label']);
        $css = $app->adext()->get('public.css', 'default');
        self::assertArrayHasKey('pubvana/alpha.main', $css);
        $blocks = $app->adext()->get('block', 'available');
        self::assertSame('Card', $blocks['pubvana/alpha.card']['label']);

        // Routes: public group uses the configured routePrepend, the admin
        // group is always /admin, and every route carries the view context
        // middleware; admin routes are additionally gated on admin.access.
        $routes = $app->router()->getRoutes();
        $patterns = array_map(static fn($r) => $r->pattern, $routes);
        self::assertContains('/alpha/index', $patterns);
        self::assertContains('/admin/overview', $patterns);

        $adminRoute = null;
        foreach ($routes as $route) {
            $middlewareClasses = array_map(static fn($m) => $m::class, $route->middleware);
            self::assertContains(PluginViewContextMiddleware::class, $middlewareClasses);
            if ($route->pattern === '/admin/overview') {
                $adminRoute = $route;
            }
        }
        self::assertNotNull($adminRoute);
        $adminMiddleware = array_map(static fn($m) => $m::class, $adminRoute->middleware);
        self::assertContains(
            \Enlivenapp\FlightShield\Middlewares\PermissionMiddleware::class,
            $adminMiddleware,
            'admin routes are gated on admin.access'
        );

        // Manifest passthrough for the admin UI.
        self::assertSame('1.2.3', $loader->getManifest('pubvana/alpha')['semver']);
    }

    public function testDisabledPluginIsNeverLoadedAndExcludedFromMigrationConfig(): void
    {
        // The Database dirs must exist under the real repo plugins/ tree
        // (the pattern probe is PROJECT_ROOT-relative), named after the
        // manifest folder, uniquely, and cleaned up in tearDown.
        $this->writeLocalPlugin('_fxmuted', 'pubvana/muted', [
            'name'      => 'pubvana/muted',
            'namespace' => $this->fxNs('\Muted'),
            'semver'    => '2.0.0',
        ], [
            'routes' => "<?php\n\$router->get('/index', function () { return 'muted'; });",
        ]);
        $this->makeRepoDir('plugins/_fxmuted/Database/Migrations');
        $this->makeRepoDir('plugins/_fxmuted/Database/Seeds');

        $app = $this->engine();
        // No app config: first-discovery default is DISABLED.
        $loader = new PluginLoader($app, $app->router(), $this->tmpRoot . '/plugins', $this->tmpRoot . '/vendor');
        $loader->loadPlugins();

        self::assertSame([], $loader->getLoaded());
        $this->assertNotRegistered('pubvana/muted');
        self::assertCount(0, $app->router()->getRoutes(), 'a disabled plugin registers no routes');

        $config = $loader->getMigrationConfig();
        self::assertNotContains('plugins/_fxmuted/Database/Migrations', $config['paths']);
        self::assertNotContains('plugins/_fxmuted/Database/Seeds', $config['seeds']['paths']);
        self::assertArrayNotHasKey('plugins/_fxmuted', $config['versions']);
    }

    public function testVendorPackageConfigIsStoredAndPrefixed(): void
    {
        $this->writeVendorPackage('acme/tool', $this->fxNs('\Tool'), 'pubvana-plugin');
        $toolRoot = $this->tmpRoot . '/vendor/acme/tool';
        mkdir($toolRoot . '/src/Config', 0777, true);
        file_put_contents(
            $toolRoot . '/src/Config/Config.php',
            "<?php\nreturn ['routePrepend' => 'tool', 'api_key' => 'from-package'];"
        );
        mkdir($toolRoot . '/src/Views', 0777, true);
        mkdir($toolRoot . '/src/Config/Routes.php.d', 0777, true); // placeholder, removed below
        rmdir($toolRoot . '/src/Config/Routes.php.d');
        file_put_contents($toolRoot . '/src/Config/Routes.php', "<?php\n\$router->get('/status', function () { return 'ok'; });");

        $app = $this->engine();
        $loader = new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            ['acme/tool' => ['enabled' => true, 'api_key' => 'from-app']]
        );
        $loader->loadPlugins();

        // Config is stored under the derived key with app overrides merged.
        self::assertSame(
            ['routePrepend' => 'tool', 'api_key' => 'from-app'],
            $app->get('acme.tool')
        );
        $this->assertRegistered('acme/tool');

        // Vendor views register from src/Views; routes wrap under the prefix.
        $view = $app->view();
        self::assertInstanceOf(PluginView::class, $view);
        self::assertSame($toolRoot . '/src/Views', $view->getPluginPath('acme/tool'));
        $patterns = array_map(static fn($r) => $r->pattern, $app->router()->getRoutes());
        self::assertContains('/tool/status', $patterns);
    }

    public function testRoutePrefixAndApiPrefixDeriveAndRespectConfig(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
        ]);

        $app = $this->engine();
        $loader = new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            ['pubvana/alpha' => ['enabled' => true, 'routePrepend' => 'custom']]
        );
        $loader->loadPlugins();

        self::assertSame('/custom', $loader->routePrefix('pubvana/alpha'));
        self::assertSame('/api/custom', $loader->apiPrefix('pubvana/alpha'));

        // No config at all: derived from the ID (slash and dashes become underscores).
        $plain = new PluginLoader($app, $app->router(), $this->tmpRoot . '/plugins', $this->tmpRoot . '/vendor');
        self::assertSame('/pubvana_never', $plain->routePrefix('pubvana/never'));
        self::assertSame('/api/pubvana_never', $plain->apiPrefix('pubvana/never'));
    }

    public function testPluginClassWithoutRegisterMethodIsSkipped(): void
    {
        $pluginDir = $this->tmpRoot . '/plugins/_fxnope';
        mkdir($pluginDir, 0777, true);
        file_put_contents($pluginDir . '/pubvana.json', (string) json_encode([
            'name'      => 'pubvana/nope',
            'namespace' => $this->fxNs('\NoRegister'),
        ]));
        // The class exists but has neither the interface nor a register().
        file_put_contents(
            $pluginDir . '/Plugin.php',
            "<?php\nnamespace {$this->fxNs('\\NoRegister')};\nclass Plugin {}\n"
        );

        $app = $this->engine();
        $loader = new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            ['pubvana/nope' => ['enabled' => true]]
        );
        $loaded = $loader->loadPlugins();

        self::assertSame([], $this->registerLog(), 'register() must never be called');
        self::assertSame([], $loaded);
    }

    public function testPrioritySortsTheLoadingOrder(): void
    {
        $this->writeLocalPlugin('_fxlate', 'pubvana/late', ['name' => 'pubvana/late', 'namespace' => $this->fxNs('\Late')]);
        $this->writeLocalPlugin('_fxearly', 'pubvana/early', ['name' => 'pubvana/early', 'namespace' => $this->fxNs('\Early')]);

        $app = $this->engine();
        $loader = new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            [
                'pubvana/late'  => ['enabled' => true, 'priority' => 20],
                'pubvana/early' => ['enabled' => true, 'priority' => 5],
            ]
        );
        $loader->loadPlugins();

        $this->assertRegistered('pubvana/early');
        $this->assertRegistered('pubvana/late');
        $ids = array_column($this->registerLog(), 'id');
        self::assertSame(['pubvana/early', 'pubvana/late'], array_slice($ids, 0, 2));
    }

    // -----------------------------------------------------------------
    // Migration config helpers
    // -----------------------------------------------------------------

    public function testPluginMigrationPatternsForBothSources(): void
    {
        // Local: plugins/{folder}/Database/... probed under the real repo.
        $this->makeRepoDir('plugins/_fxalpha/Database/Migrations');
        $this->makeRepoDir('plugins/_fxalpha/Database/Seeds');

        // Vendor: src/Database/... wins over Database/...
        $this->makeRepoDir('vendor/enlivenapp/_fxpacked/src/Database/Migrations');
        $this->makeRepoDir('vendor/enlivenapp/_fxpacked/src/Database/Seeds');
        $this->makeRepoDir('vendor/enlivenapp/_fxpacked/Database/Migrations');

        // Neither dir exists anywhere: empty lists.
        mkdir($this->tmpRoot . '/plugins/_fxbare', 0777, true);

        $loader = $this->loader();

        [$paths, $seeds] = $loader->pluginMigrationPatterns('pubvana/alpha', ['source' => 'local', 'folder' => '_fxalpha']);
        self::assertSame(['plugins/_fxalpha/Database/Migrations'], $paths);
        self::assertSame(['plugins/_fxalpha/Database/Seeds'], $seeds);

        [$paths, $seeds] = $loader->pluginMigrationPatterns('enlivenapp/_fxpacked', ['source' => 'vendor']);
        self::assertSame(['vendor/enlivenapp/_fxpacked/src/Database/Migrations'], $paths);
        self::assertSame(['vendor/enlivenapp/_fxpacked/src/Database/Seeds'], $seeds);

        [$paths, $seeds] = $loader->pluginMigrationPatterns('pubvana/bare', ['source' => 'local', 'folder' => '_fxbare']);
        self::assertSame([], $paths);
        self::assertSame([], $seeds);
    }

    public function testPluginSemverSources(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
            'semver'    => '2.0.0',
        ]);
        $this->writeLocalPlugin('_fxnosemver', 'pubvana/nosemver', [
            'name'      => 'pubvana/nosemver',
            'namespace' => $this->fxNs('\NoSemver'),
        ]);

        $loader = $this->loader();
        $local = $loader->discoverLocal();

        self::assertSame('2.0.0', $loader->pluginSemver('pubvana/alpha', $local['pubvana/alpha']));
        self::assertNull($loader->pluginSemver('pubvana/nosemver', $local['pubvana/nosemver']));

        // Vendor packages may carry a pubvana.json semver next to the package
        // (probed under the real repo vendor tree).
        $this->makeRepoDir('vendor/enlivenapp/_fxpacked');
        file_put_contents(
            PROJECT_ROOT . '/vendor/enlivenapp/_fxpacked/pubvana.json',
            (string) json_encode(['semver' => '9.9.9'])
        );
        self::assertSame('9.9.9', $loader->pluginSemver('enlivenapp/_fxpacked', ['source' => 'vendor']));

        // Vendor without a manifest: null (composer's version is the fallback).
        $this->writeVendorPackage('acme/tool', $this->fxNs('\Tool'), 'pubvana-plugin');
        self::assertNull($loader->pluginSemver('acme/tool', ['source' => 'vendor']));
    }

    public function testCoreNameAndSemverReadTheRootManifest(): void
    {
        $loader = $this->loader();

        self::assertSame('pubvana/pubvana', $this->invoke($loader, 'coreName'));
        self::assertSame('3.0.0', $loader->coreSemver());
    }

    public function testFoundationGateRequiresBothTrustedNamespaceAndType(): void
    {
        $loader = $this->loader();

        self::assertTrue($loader->isFoundationPackage('enlivenapp/flight-shield', ['type' => 'flightphp-foundation']));
        self::assertTrue($loader->isFoundationPackage('pubvana/core', ['type' => 'pubvana-foundation']));

        // Trusted namespace, wrong type.
        self::assertFalse($loader->isFoundationPackage('enlivenapp/flight-shield', ['type' => 'library']));
        // Foundation type, untrusted namespace.
        self::assertFalse($loader->isFoundationPackage('acme/flight-shield', ['type' => 'flightphp-foundation']));
        // A plugin ID that merely starts with the trusted prefix.
        self::assertFalse($loader->isFoundationPackage('enlivenappish/pkg', ['type' => 'flightphp-foundation']));
    }

    public function testGetMigrationConfigBuildsTiersAndGatesDisabledPlugins(): void
    {
        $this->writeLocalPlugin('_fxalpha', 'pubvana/alpha', [
            'name'      => 'pubvana/alpha',
            'namespace' => $this->fxNs('\Alpha'),
            'semver'    => '1.2.3',
        ]);
        $this->writeLocalPlugin('_fxmuted', 'pubvana/muted', [
            'name'      => 'pubvana/muted',
            'namespace' => $this->fxNs('\Muted'),
            'semver'    => '2.0.0',
        ]);
        $this->makeRepoDir('plugins/_fxalpha/Database/Migrations');
        $this->makeRepoDir('plugins/_fxmuted/Database/Migrations');

        $app = $this->engine();
        $loader = new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor',
            [
                'pubvana/alpha' => ['enabled' => true],
                'pubvana/muted' => ['enabled' => false],
            ]
        );

        $config = $loader->getMigrationConfig();

        self::assertContains('app/Database/Migrations', $config['paths']);
        self::assertContains('plugins/_fxalpha/Database/Migrations', $config['paths']);
        self::assertNotContains('plugins/_fxmuted/Database/Migrations', $config['paths']);

        // Versions: the core identity plus the enabled plugin's semver.
        self::assertSame('3.0.0', $config['versions']['pubvana/pubvana']);
        self::assertSame('1.2.3', $config['versions']['plugins/_fxalpha']);
        self::assertArrayNotHasKey('plugins/_fxmuted', $config['versions']);

        // Core module mapping keeps the migrations module names readable.
        self::assertSame(['app/Database/Migrations' => 'pubvana/pubvana'], $config['module_names']);
    }

    public function testMigrationChecksSkipCliRunsOutsideRunway(): void
    {
        // The repo marker is gitignored, so CI checkouts never have it.
        // Create it for the duration of this test instead of depending on
        // repo state; the CLI is not runway, so no migrations run.
        $marker = PROJECT_ROOT . '/.migrations_installed';
        $existed = is_file($marker);
        if (!$existed) {
            file_put_contents($marker, 'test');
        }
        try {
            self::assertFileExists($marker);
            self::assertFalse($this->invoke($this->loader(), 'shouldRunMigrationsOnThisRequest'));
        } finally {
            if (!$existed && is_file($marker)) {
                unlink($marker);
            }
        }
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * Unique PSR-4 namespace for a fixture class.
     */
    private function fxNs(string $suffix): string
    {
        return 'Fixture_' . $this->fxId . ltrim($suffix, '\\');
    }

    /**
     * Loader over the fixture roots.
     */
    private function loader(): PluginLoader
    {
        $app = $this->engine();

        return new PluginLoader(
            $app,
            $app->router(),
            $this->tmpRoot . '/plugins',
            $this->tmpRoot . '/vendor'
        );
    }

    /**
     * Fresh global Engine with a PluginView and ExtensionRegistry mapped.
     *
     * @return Engine<object>
     */
    private function engine(): Engine
    {
        if (!isset($this->testApp)) {
            $test = $this;
            $app = $this->app([
                // The loader reads plugin_state through db(); the registry
                // and view must be singletons or hook registrations vanish.
                'db' => fn(): PDO => $test->pdo,
                'adext' => function (): ExtensionRegistry {
                    static $registry = null;
                    if ($registry === null) {
                        $registry = new ExtensionRegistry();
                    }
                    return $registry;
                },
                'view' => function () {
                    static $view = null;
                    if ($view === null) {
                        $view = new PluginView('/tmp/pubvana-pluginloader-appviews');
                    }
                    return $view;
                },
            ]);
            \Flight::setEngine($app);
            $this->testApp = $app;
        }

        return $this->testApp;
    }

    /**
     * Write a local plugin fixture.
     *
     * @param string|null                $pluginId  null to omit the name key
     * @param array<string, mixed>       $manifest  Manifest body
     * @param array<string, string>      $files     Optional config/routes/adminRoutes bodies
     */
    private function writeLocalPlugin(
        string $folder,
        ?string $pluginId,
        array $manifest,
        array $files = []
    ): string {
        $pluginDir = $this->tmpRoot . '/plugins/' . $folder;
        mkdir($pluginDir . '/Views', 0777, true);
        if ($pluginId !== null) {
            $manifest['name'] = $pluginId;
        }
        file_put_contents($pluginDir . '/pubvana.json', (string) json_encode($manifest));

        $namespace = (string) ($manifest['namespace'] ?? '');
        $nsLit = str_replace('\\', '\\\\', $namespace);
        $idLit = str_replace('\\', '\\\\', (string) ($pluginId ?? $folder));
        file_put_contents(
            $pluginDir . '/Plugin.php',
            "<?php\n"
            . "namespace {$namespace};\n"
            . "use flight\\Engine;\n"
            . "use flight\\net\\Router;\n"
            . "use Pubvana\\Services\\PluginInterface;\n"
            . "class Plugin implements PluginInterface {\n"
            . "    public function register(Engine \$app, Router \$router, array \$config = []): void {\n"
            . "        \$GLOBALS['fixture_register_log'][] = ['id' => '{$idLit}', 'app' => \$app, 'config' => \$config];\n"
            . "    }\n"
            . "}\n"
        );

        if (isset($files['config'])) {
            mkdir($pluginDir . '/Config', 0777, true);
            file_put_contents($pluginDir . '/Config/Config.php', "<?php\n" . $files['config']);
        }
        if (isset($files['routes'])) {
            file_put_contents($pluginDir . '/Routes.php', $files['routes']);
        }
        if (isset($files['adminRoutes'])) {
            file_put_contents($pluginDir . '/AdminRoutes.php', $files['adminRoutes']);
        }

        return $pluginDir;
    }

    /**
     * Write a vendor package fixture: composer.json, optional src/Plugin.php
     * (vendor style: no interface, duck-typed register), and an
     * installed.json snapshot entry.
     */
    private function writeVendorPackage(
        string $name,
        string $namespace,
        string $type,
        string $snapshotType = null,
        bool $skipPsr4 = false
    ): void {
        $root = $this->tmpRoot . '/vendor/' . $name;
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/composer.json', (string) json_encode(['type' => $type]));

        file_put_contents(
            $root . '/src/Plugin.php',
            "<?php\n"
            . "namespace {$namespace};\n"
            . "use flight\\Engine;\n"
            . "use flight\\net\\Router;\n"
            . "class Plugin {\n"
            . "    public function register(Engine \$app, Router \$router, array \$config = []): void {\n"
            . "        \$GLOBALS['fixture_register_log'][] = ['id' => '{$name}', 'app' => \$app, 'config' => \$config];\n"
            . "    }\n"
            . "}\n"
        );

        $file = $this->tmpRoot . '/vendor/composer/installed.json';
        $installed = json_decode((string) file_get_contents($file), true);
        $installed['packages'] ??= [];
        $entry = [
            'name'     => $name,
            'type'     => $snapshotType ?? $type,
            'version'  => '1.0.0',
            'autoload' => $skipPsr4 ? [] : ['psr-4' => [$namespace . '\\' => 'src/']],
        ];
        $installed['packages'][] = $entry;
        file_put_contents($file, (string) json_encode($installed));
    }

    /**
     * The plugin registration log, straight from the fixtures' globals.
     *
     * @return array<int, array{id: string, app: object, config: array<string, mixed>}>
     */
    private function registerLog(): array
    {
        /** @var array<int, array<string, mixed>> $log */
        $log = $GLOBALS['fixture_register_log'] ?? [];

        return $log;
    }

    private function registerEntry(string $pluginId): array
    {
        foreach ($this->registerLog() as $entry) {
            if ($entry['id'] === $pluginId) {
                return $entry;
            }
        }
        self::fail("Plugin '{$pluginId}' never registered");
    }

    private function assertRegistered(string $pluginId): void
    {
        foreach ($this->registerLog() as $entry) {
            if ($entry['id'] === $pluginId) {
                self::assertTrue(true);
                return;
            }
        }
        self::fail("Plugin '{$pluginId}' never registered");
    }

    private function assertNotRegistered(string $pluginId): void
    {
        foreach ($this->registerLog() as $entry) {
            if ($entry['id'] === $pluginId) {
                self::fail("Plugin '{$pluginId}' registered but must not have run");
            }
        }
        self::assertTrue(true);
    }

    /**
     * Create a directory under the real repo root for the
     * PROJECT_ROOT-relative pattern probes; removed in tearDown.
     */
    private function makeRepoDir(string $relative): void
    {
        $path = PROJECT_ROOT . '/' . $relative;
        if (is_dir($path)) {
            return;
        }
        mkdir($path, 0777, true);
        $this->repoPaths[] = $relative;
    }

    /**
     * Delete a file or a directory tree.
     */
    private function deletePath(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $filePath = (string) $file->getPathname();
            is_dir($filePath) ? rmdir($filePath) : unlink($filePath);
        }
        rmdir($path);
    }
}
