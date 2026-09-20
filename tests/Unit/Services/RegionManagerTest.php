<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\BlockPlacement;
use Pubvana\Models\Theme;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\PluginView;
use Pubvana\Services\RegionManager;
use Pubvana\Services\ThemeService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;

/**
 * RegionManager over the in-memory block_placements table plus temp
 * fixture directories for template/manifest reads.
 *
 * Covers region discovery (platform + theme manifest, collisions,
 * malformed entries), block discovery with priority sort, the
 * placement CRUD surface (place, remove, reorder, move, orphans),
 * the per-request placement cache, JSON option values, and the
 * Vision render path including the three-tier template resolution.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(RegionManager::class)]
final class RegionManagerTest extends TestCase
{
    private const FIXTURE_THEME = '_regionmanager_test_theme';

    private PDO $pdo;

    private Engine $app;

    private RegionManager $service;

    /** @var string Temp root for template fixtures */
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->tmpRoot = sys_get_temp_dir() . '/pubvana-region-' . uniqid();
        mkdir($this->tmpRoot . '/app-views', 0777, true);
        mkdir($this->tmpRoot . '/theme-views', 0777, true);
        mkdir($this->tmpRoot . '/plugin-views', 0777, true);
        mkdir($this->tmpRoot . '/empty-app-views', 0777, true);
        $this->cleanupThemeFixture();
        $this->app = $this->buildApp();
        $this->service = new RegionManager($this->app);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        $this->cleanupThemeFixture();
        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Region discovery
    // -----------------------------------------------------------------

    public function testGetRegionsReturnsPlatformRegionsWithoutActiveTheme(): void
    {
        $regions = $this->service->getRegions();

        self::assertSame(['footer', 'before-content', 'after-content'], array_keys($regions));
        foreach ($regions as $region) {
            self::assertSame('platform', $region['source']);
        }
        self::assertSame('Footer', $regions['footer']['label']);
    }

    public function testGetRegionsMergesThemeManifestRegions(): void
    {
        $this->seedActiveTheme(self::FIXTURE_THEME);
        $themeDir = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME;
        mkdir($themeDir, 0777, true);
        file_put_contents($themeDir . '/pubvana.json', (string) json_encode([
            'provides' => [
                'regions' => [
                    ['id' => 'hero', 'label' => 'Hero', 'description' => 'Top of page'],
                    ['id' => 'footer', 'label' => 'Theme footer', 'description' => 'Collides with platform'],
                    ['id' => 'no-label'],                          // label falls back to id
                    ['id' => 'no-desc', 'label' => 'L'],           // description falls back to ''
                    ['id' => '', 'label' => 'Empty id'],           // skipped: empty id
                    ['id' => 42, 'label' => 'Non-string id'],      // skipped: non-string id
                    'not-an-array',                                // skipped: not an array
                ],
            ],
        ]));

        $service = new RegionManager($this->app);
        $regions = $service->getRegions();

        self::assertSame('hero', $regions['hero']['id']);
        self::assertSame('Hero', $regions['hero']['label']);
        self::assertSame('Top of page', $regions['hero']['description']);
        self::assertSame('theme', $regions['hero']['source']);

        self::assertSame('platform', $regions['footer']['source'], 'platform wins the id collision');
        self::assertSame('Footer', $regions['footer']['label']);

        self::assertSame('no-label', $regions['no-label']['label']);
        self::assertSame('', $regions['no-desc']['description']);

        self::assertArrayNotHasKey('42', $regions);
        self::assertCount(6, $regions, '3 platform + hero + no-label + no-desc');
    }

    public function testGetRegionsSkipsInvalidManifestShapes(): void
    {
        $this->seedActiveTheme(self::FIXTURE_THEME);
        $themeDir = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME;
        mkdir($themeDir, 0777, true);
        // regions not an array, then no manifest at all on a re-read
        file_put_contents($themeDir . '/pubvana.json', '{"provides":{"regions":"nope"}}');

        $service = new RegionManager($this->app);
        self::assertCount(3, $service->getRegions());

        unlink($themeDir . '/pubvana.json');
        self::assertCount(3, $service->getRegions(), 'a theme without a manifest contributes nothing');

        // Malformed JSON body
        file_put_contents($themeDir . '/pubvana.json', '{not json');
        self::assertCount(3, $service->getRegions());
    }

    // -----------------------------------------------------------------
    // Block discovery
    // -----------------------------------------------------------------

    public function testGetAvailableBlocksSortsByDeclaredPriority(): void
    {
        $registry = $this->app->adext();
        $registry->register('block', 'available', 'late.plugin', [
            'label'    => 'Late',
            'template' => 'card.tpl',
            'priority' => 90,
        ]);
        $registry->register('block', 'available', 'early.plugin', [
            'label'    => 'Early',
            'template' => 'card.tpl',
            'priority' => 10,
        ]);

        $blocks = $this->service->getAvailableBlocks();

        self::assertSame(['early.plugin', 'late.plugin'], array_keys($blocks));
    }

    // -----------------------------------------------------------------
    // Placement reads and the per-request cache
    // -----------------------------------------------------------------

    public function testGetPlacementsReturnsEmptyForUnknownRegion(): void
    {
        self::assertSame([], $this->service->getPlacements('nope'));
    }

    public function testGetPlacementsIsCachedPerRequest(): void
    {
        $this->insertPlacement('footer', 'pubvana.one', 0);

        self::assertCount(1, $this->service->getPlacements('footer'));

        // A row added directly after the cache filled is not seen until
        // the next request (fresh service instance).
        $this->insertPlacement('footer', 'pubvana.two', 1);
        self::assertCount(1, $this->service->getPlacements('footer'), 'served from the cache');

        self::assertCount(2, (new RegionManager($this->app))->getPlacements('footer'));
    }

    public function testGetAllPlacementsGroupsByRegion(): void
    {
        $this->insertPlacement('footer', 'pubvana.one', 0);
        $this->insertPlacement('sidebar', 'pubvana.two', 0);
        $this->insertPlacement('footer', 'pubvana.three', 1);

        $grouped = $this->service->getAllPlacements();

        self::assertCount(2, $grouped['footer']);
        self::assertCount(1, $grouped['sidebar']);
        self::assertSame('pubvana.one', $grouped['footer'][0]->block_key);
        self::assertSame('pubvana.three', $grouped['footer'][1]->block_key);
    }

    // -----------------------------------------------------------------
    // Placement CRUD
    // -----------------------------------------------------------------

    public function testSavePlacementAppendsAtNextSortOrder(): void
    {
        $this->insertPlacement('footer', 'pubvana.one', 4);

        $placement = $this->service->savePlacement('footer', 'pubvana.two');

        self::assertNotNull($placement);
        self::assertSame('pubvana.two', $placement->block_key);
        self::assertSame(5, (int) $placement->sort_order);
        self::assertNotEmpty($placement->created_at);
    }

    public function testSavePlacementRejectsADuplicateBlockInRegion(): void
    {
        $this->insertPlacement('footer', 'pubvana.one', 0);

        self::assertNull($this->service->savePlacement('footer', 'pubvana.one'));
        self::assertCount(1, $this->service->getAllPlacements()['footer']);
    }

    public function testSavePlacementSameBlockDifferentRegionIsAllowed(): void
    {
        $this->insertPlacement('footer', 'pubvana.one', 0);

        $placement = $this->service->savePlacement('before-content', 'pubvana.one');

        self::assertNotNull($placement);
    }

    public function testRemovePlacementDeletesOnlyExistingRows(): void
    {
        $placement = $this->insertPlacement('footer', 'pubvana.one', 0);

        $this->service->removePlacement(9999);
        self::assertCount(1, $this->service->getAllPlacements()['footer']);

        $this->service->removePlacement((int) $placement->id);
        self::assertSame([], $this->service->getAllPlacements());
    }

    public function testReorderPlacementsIsRegionScoped(): void
    {
        $first = $this->insertPlacement('footer', 'pubvana.one', 0);
        $second = $this->insertPlacement('footer', 'pubvana.two', 1);
        $other = $this->insertPlacement('sidebar', 'pubvana.three', 0);

        $this->service->reorderPlacements('footer', [(int) $second->id, (int) $first->id, (int) $other->id]);

        self::assertSame(0, (int) $this->fetchPlacement((int) $second->id)['sort_order']);
        self::assertSame(1, (int) $this->fetchPlacement((int) $first->id)['sort_order']);
        self::assertSame(0, (int) $this->fetchPlacement((int) $other->id)['sort_order'], 'other-region rows untouched');
    }

    public function testMovePlacementReRegionsAndAppends(): void
    {
        $this->insertPlacement('sidebar', 'pubvana.existing', 0);
        $moving = $this->insertPlacement('footer', 'pubvana.moving', 0);

        $this->service->movePlacement((int) $moving->id, 'sidebar');

        $moved = $this->fetchPlacement((int) $moving->id);
        self::assertNotNull($moved);
        self::assertSame('sidebar', $moved['region_id']);
        self::assertSame(1, (int) $moved['sort_order'], 'appends after the existing placement');
    }

    public function testMovePlacementDeletesSourceWhenTargetAlreadyHasBlock(): void
    {
        $existing = $this->insertPlacement('sidebar', 'pubvana.block', 0);
        $moving = $this->insertPlacement('footer', 'pubvana.block', 0);

        $this->service->movePlacement((int) $moving->id, 'sidebar');

        self::assertNull($this->fetchPlacement((int) $moving->id));
        self::assertNotNull($this->fetchPlacement((int) $existing->id));
    }

    public function testMovePlacementIgnoresMissingRows(): void
    {
        $this->service->movePlacement(9999, 'sidebar');

        self::assertSame([], $this->service->getAllPlacements());
    }

    public function testGetOrphanedPlacementsListsUnknownRegions(): void
    {
        $this->insertPlacement('footer', 'pubvana.ok', 0);
        $orphan = $this->insertPlacement('theme-sidebar', 'pubvana.orphan', 0);

        $orphans = $this->service->getOrphanedPlacements();

        self::assertCount(1, $orphans);
        self::assertSame((int) $orphan->id, (int) $orphans[0]->id);
    }

    public function testOrphansReEvaluateAfterThemeRegionsRegister(): void
    {
        $this->insertPlacement('theme-sidebar', 'pubvana.orphan', 0);
        self::assertCount(1, $this->service->getOrphanedPlacements());

        // A theme declares the region: no longer an orphan.
        $this->seedActiveTheme(self::FIXTURE_THEME);
        $themeDir = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME;
        mkdir($themeDir, 0777, true);
        file_put_contents($themeDir . '/pubvana.json', '{"provides":{"regions":[{"id":"theme-sidebar"}]}}');

        $service = new RegionManager($this->app);
        self::assertSame([], $service->getOrphanedPlacements());
    }

    // -----------------------------------------------------------------
    // Block option values (JSON column)
    // -----------------------------------------------------------------

    public function testPlacementValuesRoundTripThroughJsonColumn(): void
    {
        $placement = $this->insertPlacement('footer', 'pubvana.one', 0);

        $this->service->savePlacementValues((int) $placement->id, ['title' => 'Hi', 'count' => 2]);

        self::assertSame(
            ['title' => 'Hi', 'count' => 2],
            $this->service->getPlacementValues((int) $placement->id)
        );
    }

    public function testPlacementValuesOnMissingRowAreEmpty(): void
    {
        self::assertSame([], $this->service->getPlacementValues(9999));

        $this->service->savePlacementValues(9999, ['x' => 1]);
        self::assertSame([], $this->service->getAllPlacements(), 'no phantom row written');
    }

    // -----------------------------------------------------------------
    // Rendering
    // -----------------------------------------------------------------

    public function testBuildRegionWithoutAPluginViewReturnsEmpty(): void
    {
        $this->insertPlacement('footer', 'pubvana.card', 0);
        // The mapped view is not a PluginView, so there is no Vision.
        $app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            'adext' => $this->adextProvider(),
            'view' => fn(): object => new stdClass(),
        ]);
        $this->registerCardTemplate();
        $this->registerCardBlock($app);

        self::assertSame('', (new RegionManager($app))->buildRegion('footer'));
    }

    public function testBuildRegionReturnsEmptyWithNoPlacements(): void
    {
        self::assertSame('', $this->service->buildRegion('footer'));
    }

    public function testBuildRegionRendersPlacedBlocksInOrder(): void
    {
        $app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            'adext' => $this->adextProvider(),
            'view' => $this->pluginViewProvider(),
        ]);
        $service = new RegionManager($app);

        $this->registerCardTemplate();
        $this->registerCardBlock($app);

        $calls = [];
        $this->insertPlacement('footer', 'pubvana.card', 0, ['title' => 'First']);
        $this->insertPlacement('footer', 'pubvana.card', 1, ['title' => 'Second']);

        // Record provider invocations: the saved values flow through.
        $registry = $app->adext();
        $registry->register('block', 'available', 'pubvana.probe', [
            'label'    => 'Probe',
            'template' => 'card.tpl',
            'provider' => function (array $options) use (&$calls): array {
                $calls[] = $options;
                return $options;
            },
        ]);
        $this->insertPlacement('footer', 'pubvana.probe', 2, ['title' => 'Probe']);

        $html = $service->buildRegion('footer');

        self::assertSame('First|Second|Probe|', $html);
        // Only the probe provider records invocations; the card
        // provider's saved values flow through as rendered titles.
        self::assertSame(
            [['title' => 'Probe']],
            $calls
        );
    }

    public function testBuildRegionSkipsUnknownBlockKeys(): void
    {
        $app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            'adext' => $this->adextProvider(),
            'view' => $this->pluginViewProvider(),
        ]);
        $this->insertPlacement('footer', 'pubvana.gone', 0);

        self::assertSame('', (new RegionManager($app))->buildRegion('footer'));
    }

    // -----------------------------------------------------------------
    // renderBlock() error paths (invoked directly)
    // -----------------------------------------------------------------

    public function testRenderBlockCatchesProviderThrowables(): void
    {
        $this->registerCardTemplate();

        $block = [
            'template' => 'card.tpl',
            'provider' => function (): array {
                throw new \RuntimeException('boom');
            },
        ];

        self::assertSame('', $this->invoke($this->service, 'renderBlock', [$block, [], $this->vision(), $this->pluginView(), 'pubvana.card']));
    }

    public function testRenderBlockForcesProviderDataToAnArray(): void
    {
        $this->registerCardTemplate('{{ payload }}');

        $block = [
            'template' => 'card.tpl',
            'provider' => fn(): string => 'not-an-array',
        ];

        self::assertSame('', $this->invoke($this->service, 'renderBlock', [$block, [], $this->vision(), $this->pluginView(), 'pubvana.card']));
    }

    public function testRenderBlockWithoutProviderPassesOptionsAsData(): void
    {
        $this->registerCardTemplate('{{ title }}');

        $block = ['template' => 'card.tpl'];

        self::assertSame(
            'Options',
            $this->invoke($this->service, 'renderBlock', [$block, ['title' => 'Options'], $this->vision(), $this->pluginView(), 'pubvana.card'])
        );
    }

    public function testRenderBlockWithoutProviderMergesOptionDefaults(): void
    {
        $this->registerCardTemplate('{{ title }}|{{ placeholder }}|{{ button_text }}');

        $block = [
            'template' => 'card.tpl',
            'options'  => [
                'title'       => ['type' => 'input', 'label' => 'Title', 'default' => 'Search'],
                'placeholder' => ['type' => 'input', 'label' => 'Placeholder', 'default' => 'Search...'],
                'button_text' => ['type' => 'input', 'label' => 'Button', 'default' => 'Go'],
            ],
        ];

        // A placement saved with empty options still carries the schema
        // defaults into the template.
        self::assertSame(
            'Search|Search...|Go',
            $this->invoke($this->service, 'renderBlock', [$block, [], $this->vision(), $this->pluginView(), 'pubvana.card'])
        );

        // Saved values win over defaults.
        self::assertSame(
            'Find|Type here|Submit',
            $this->invoke($this->service, 'renderBlock', [
                $block,
                ['title' => 'Find', 'placeholder' => 'Type here', 'button_text' => 'Submit'],
                $this->vision(),
                $this->pluginView(),
                'pubvana.card',
            ])
        );
    }

    public function testRenderBlockPassesPageContextToProviders(): void
    {
        $this->registerCardTemplate('{{ posts }}');

        $received = null;
        $block = [
            'template' => 'card.tpl',
            'provider' => function (array $options, array $context) use (&$received): array {
                $received = $context;
                return ['posts' => (string) ($context['post_id'] ?? 0)];
            },
        ];

        $this->service->setContext(['post_id' => 42, 'slug' => 'hello-world']);

        self::assertSame(
            '42',
            $this->invoke($this->service, 'renderBlock', [$block, [], $this->vision(), $this->pluginView(), 'pubvana.card'])
        );
        self::assertSame(['post_id' => 42, 'slug' => 'hello-world'], $received);
    }

    public function testRenderBlockFailsQuietlyWhenTemplateIsMissing(): void
    {
        $block = ['template' => 'missing.tpl'];

        self::assertSame(
            '',
            $this->invoke($this->service, 'renderBlock', [$block, [], $this->vision(), $this->pluginView(), 'pubvana.card'])
        );
    }

    // -----------------------------------------------------------------
    // resolveBlockTemplate() three-tier chain (invoked directly)
    // -----------------------------------------------------------------

    public function testResolveBlockTemplateRejectsInvalidValues(): void
    {
        $view = $this->pluginView();

        // Bad template values: empty or a directory-ish path.
        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['', 'pubvana.card', $view]));
        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['pubvana/card/blocks/card.tpl', 'pubvana.card', $view]));

        // Bad block keys: fewer than two segments, or an empty author/package.
        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'card', $view]));
        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', '.card', $view]));
        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.', $view]));
    }

    public function testResolveBlockTemplatePrefersTheAppOverride(): void
    {
        $this->registerCardTemplate();
        $this->app->set('flight.views.path', $this->tmpRoot . '/app-views');

        $resolved = $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.card', $this->pluginView()]);

        self::assertSame($this->tmpRoot . '/app-views/pubvana/card/public/blocks/card.tpl', $resolved);
    }

    public function testResolveBlockTemplateFallsBackToThemeThenPlugin(): void
    {
        $this->registerCardTemplate();
        $view = $this->pluginView();
        $view->setThemePath($this->tmpRoot . '/theme-views');

        // The app override tier is skipped: flight.views.path points at a
        // dir without the template.
        $this->app->set('flight.views.path', $this->tmpRoot . '/empty-app-views');

        $themeResolved = $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.card', $view]);
        self::assertSame($this->tmpRoot . '/theme-views/pubvana/card/public/blocks/card.tpl', $themeResolved);

        // Remove the theme override: the plugin tier answers, with the
        // package-prefixed path stripped to Views/public/blocks/.
        unlink($this->tmpRoot . '/theme-views/pubvana/card/public/blocks/card.tpl');
        $pluginResolved = $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.card', $view]);
        self::assertSame($this->tmpRoot . '/plugin-views/public/blocks/card.tpl', $pluginResolved);

        // Remove the plugin file too: nothing resolves.
        unlink($this->tmpRoot . '/plugin-views/public/blocks/card.tpl');
        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.card', $view]));
    }

    public function testResolveBlockTemplateAcceptsTemplateWithOrWithoutExtension(): void
    {
        $this->registerCardTemplate();
        $view = $this->pluginView();
        $this->app->set('flight.views.path', $this->tmpRoot . '/empty-app-views');

        $withExtension = $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.card', $view]);
        $withoutExtension = $this->invoke($this->service, 'resolveBlockTemplate', ['card', 'pubvana.card', $view]);

        self::assertSame($this->tmpRoot . '/plugin-views/public/blocks/card.tpl', $withExtension);
        self::assertSame($this->tmpRoot . '/plugin-views/public/blocks/card.tpl', $withoutExtension);
    }

    public function testResolveBlockTemplateIgnoresFullPrefixPluginLayout(): void
    {
        // The plugin tier accepts exactly one layout: Views/public/blocks/.
        // A template stored under the package-prefixed path
        // (Views/pubvana/card/...) must not resolve.
        $view = $this->pluginView();
        $this->app->set('flight.views.path', $this->tmpRoot . '/empty-app-views');

        $dir = $this->tmpRoot . '/plugin-views/pubvana/card/public/blocks';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/card.tpl', '{{ title }}|');

        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.card', $view]));
    }

    public function testResolveBlockTemplateSkipsUnregisteredPlugins(): void
    {
        $this->registerCardTemplate();
        $view = new PluginView(); // no theme path, no plugin path registered

        self::assertSame('', $this->invoke($this->service, 'resolveBlockTemplate', ['card.tpl', 'pubvana.card', $view]));
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * Fresh Engine with db, adext, the real ThemeService and a real
     * PluginView mapped, the way services.php wires RegionManager.
     *
     * @return Engine<object>
     */
    private function buildApp(): Engine
    {
        $test = $this;

        return $this->app([
            'db' => fn(): PDO => $this->pdo,
            'adext' => $this->adextProvider(),
            'themes' => function () use ($test) {
                static $service = null;
                if ($service === null) {
                    $service = new ThemeService($test->app);
                }
                return $service;
            },
            'view' => $this->pluginViewProvider(),
        ]);
    }

    /**
     * @return callable(): ExtensionRegistry
     */
    private function adextProvider(): callable
    {
        return $this->singleton(fn(): ExtensionRegistry => new ExtensionRegistry());
    }

    /**
     * @return callable(): PluginView
     */
    private function pluginViewProvider(): callable
    {
        return $this->singleton(fn(): PluginView => $this->pluginView());
    }

    private function pluginView(): PluginView
    {
        $view = new PluginView();
        $view->setThemePath(null);
        $view->addPluginPath('pubvana/card', $this->tmpRoot . '/plugin-views');
        $view->addPluginPath('pubvana/probe', $this->tmpRoot . '/plugin-views');

        return $view;
    }

    /**
     * Wrap a lazy provider in a per-engine singleton guard.
     *
     * @param callable $provider Zero-argument factory
     * @return callable Singleton-guarded factory
     */
    private function singleton(callable $provider): callable
    {
        return function () use ($provider) {
            static $instance = null;
            if ($instance === null) {
                $instance = $provider();
            }
            return $instance;
        };
    }

    /**
     * A real Vision engine, the way PluginView::vision() builds one.
     */
    private function vision(): \Enlivenapp\Vision\Engine
    {
        return new \Enlivenapp\Vision\Engine();
    }

    /**
     * Read a placement row straight from the table.
     *
     * @return array{id: int, region_id: string, block_key: string, sort_order: int}|null
     */
    private function fetchPlacement(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, region_id, block_key, sort_order FROM block_placements WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Write the card template fixture into every tier dir.
     *
     * App and theme tiers use the '{author}/{package}/public/blocks/' path
     * derived from the 'pubvana.card' block key; the plugin tier stores the
     * file under Views/public/blocks/, matching RegionManager's key-derived
     * resolution. Tests delete the tiers they want skipped.
     *
     * @param string $body Template body (default renders {{ title }})
     */
    private function registerCardTemplate(string $body = '{{ title }}|'): void
    {
        foreach (['app-views', 'theme-views'] as $tier) {
            $dir = $this->tmpRoot . '/' . $tier . '/pubvana/card/public/blocks';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($dir . '/card.tpl', $body);
        }

        $pluginDir = $this->tmpRoot . '/plugin-views/public/blocks';
        if (!is_dir($pluginDir)) {
            mkdir($pluginDir, 0777, true);
        }
        file_put_contents($pluginDir . '/card.tpl', $body);
    }

    /**
     * Register the card block in adext with a provider echoing the title.
     *
     * The block key 'pubvana.card' derives the package 'pubvana/card',
     * matching the registered plugin view path.
     */
    private function registerCardBlock(?Engine $engine = null): void
    {
        ($engine ?? $this->app)->adext()->register('block', 'available', 'pubvana.card', [
            'label'    => 'Card',
            'template' => 'card.tpl',
            'provider' => fn(array $options): array => ['title' => (string) ($options['title'] ?? '')],
        ]);
    }

    /**
     * Seed an active theme row (disabled left NULL, as findActive expects).
     */
    private function seedActiveTheme(string $folder): void
    {
        $theme = new Theme($this->pdo);
        $theme->name = $folder;
        $theme->folder = $folder;
        $theme->is_active = 1;
        $theme->insert();
    }

    /**
     * Remove the theme fixture directory if a previous run left it.
     */
    private function cleanupThemeFixture(): void
    {
        $dir = PROJECT_ROOT . '/themes/' . self::FIXTURE_THEME;
        if (is_dir($dir)) {
            $this->deleteDir($dir);
        }
    }

    /**
     * Delete a directory tree.
     */
    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $path = (string) $file->getPathname();
            is_dir($path) ? rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Insert a placement row directly so CRUD tests control exact data.
     *
     * @param array<string, mixed>|null $options Saved options (encoded when given)
     */
    private function insertPlacement(
        string $regionId,
        string $blockKey,
        int $sortOrder,
        ?array $options = null
    ): BlockPlacement {
        $placement = new BlockPlacement($this->pdo);
        $placement->region_id = $regionId;
        $placement->block_key = $blockKey;
        $placement->sort_order = $sortOrder;
        if ($options !== null) {
            $placement->setOptions($options);
        }
        $placement->created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $placement->insert();

        return $placement;
    }
}
