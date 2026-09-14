<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\AdminController;
use Pubvana\Models\TrustCache;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * AdminController dashboard base coverage.
 *
 * Exercises grouping helpers, URL normalization, config access,
 * the shared trust gate, index assembly and layout rendering
 * through lightweight engine doubles.
 */
#[CoversClass(AdminController::class)]
final class AdminControllerTest extends TestCase
{
    /** @var array<string, mixed> Captured render calls */
    private array $renders = [];

    /** @var array<string, mixed> Captured view fetch calls */
    private array $fetches = [];

    /** @var array<string, mixed> Captured jsonHalt calls */
    private array $halts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->renders = [];
        $this->fetches = [];
        $this->halts = [];
    }

    public function testDashboardGroupsTokens(): void
    {
        $controller = new AdminController($this->engine());
        $groups = $this->invoke($controller, 'dashboardGroups');

        self::assertSame(
            ['system', 'people', 'content', 'media', 'commerce', 'community', 'analytics', 'tools'],
            array_keys($groups)
        );
        self::assertSame('System', $groups['system']['label']);
        self::assertSame(10, $groups['system']['priority']);
        self::assertSame(80, $groups['tools']['priority']);
    }

    public function testGroupForSourceMapping(): void
    {
        $controller = new AdminController($this->engine());

        self::assertSame('people', $this->invoke($controller, 'groupForSource', ['pubvana.users']));
        self::assertSame('people', $this->invoke($controller, 'groupForSource', ['pubvana.admin']));
        self::assertSame('content', $this->invoke($controller, 'groupForSource', ['pubvana.blog']));
        self::assertSame('content', $this->invoke($controller, 'groupForSource', ['pubvana.navigation']));
        self::assertSame('media', $this->invoke($controller, 'groupForSource', ['pubvana.media']));
        self::assertSame('commerce', $this->invoke($controller, 'groupForSource', ['pubvana.shop']));
        self::assertSame('community', $this->invoke($controller, 'groupForSource', ['pubvana.members']));
        self::assertSame('analytics', $this->invoke($controller, 'groupForSource', ['pubvana.stats']));
        self::assertSame('tools', $this->invoke($controller, 'groupForSource', ['pubvana.seo']));
        self::assertSame('customns', $this->invoke($controller, 'groupForSource', ['pubvana.customns']));
        self::assertSame('other', $this->invoke($controller, 'groupForSource', ['']));
    }

    public function testFlattenCandidatesShapes(): void
    {
        $controller = new AdminController($this->engine());

        $flat = [['label' => 'A'], ['label' => 'B']];
        self::assertCount(2, $this->invoke($controller, 'flattenCandidates', [$flat]));

        $double = [[['label' => 'Deep']]];
        $found = $this->invoke($controller, 'flattenCandidates', [$double]);
        self::assertCount(1, $found);
        self::assertSame('Deep', $found[0]['label']);

        $mixed = ['named' => ['label' => 'Skip'], 0 => ['title' => 'Keep'], 1 => 'nope'];
        $found = $this->invoke($controller, 'flattenCandidates', [$mixed]);
        self::assertCount(1, $found);
        self::assertSame('Keep', $found[0]['title']);
    }

    public function testNormalizeEntriesFiltersAndTags(): void
    {
        $controller = new AdminController($this->engine());
        $contributors = [
            'pubvana.blog' => [
                ['label' => 'Posts', 'href' => '/posts'],
                ['nope' => true],
            ],
        ];

        $entries = $this->invoke($controller, 'normalizeDashboardEntries', [$contributors, 'label']);

        self::assertCount(1, $entries);
        self::assertSame('pubvana.blog', $entries[0]['source']);
        self::assertSame('content', $entries[0]['group']);
        self::assertSame('/admin/posts', $entries[0]['href']);
    }

    public function testNormalizeEntriesRespectsExplicitGroup(): void
    {
        $controller = new AdminController($this->engine());
        $contributors = [
            'pubvana.blog' => [
                ['title' => 'Recent', 'group' => 'tools'],
            ],
        ];

        $entries = $this->invoke($controller, 'normalizeDashboardEntries', [$contributors, 'title']);

        self::assertSame('tools', $entries[0]['group']);
    }

    public function testNormalizeDashboardUrls(): void
    {
        $controller = new AdminController($this->engine());

        self::assertSame(
            '/admin/foo',
            $this->invoke($controller, 'normalizeDashboardUrls', [['href' => '/foo']])['href']
        );
        self::assertSame(
            '/admin/foo',
            $this->invoke($controller, 'normalizeDashboardUrls', [['href' => '/admin/foo']])['href']
        );
        self::assertArrayNotHasKey(
            'href',
            $this->invoke($controller, 'normalizeDashboardUrls', [[]])
        );
        self::assertSame(
            'https://x.test/y',
            $this->invoke($controller, 'normalizeDashboardUrls', [['href' => 'https://x.test/y']])['href']
        );

        $withItems = [
            'href' => '/top',
            'items' => [
                ['href' => '/sub'],
                ['href' => '/admin/keep'],
                ['href' => 'relative'],
            ],
        ];
        $out = $this->invoke($controller, 'normalizeDashboardUrls', [$withItems]);
        self::assertSame('/admin/top', $out['href']);
        self::assertSame('/admin/sub', $out['items'][0]['href']);
        self::assertSame('/admin/keep', $out['items'][1]['href']);
        self::assertSame('relative', $out['items'][2]['href']);
    }

    public function testGroupEntriesOrderingAndUnknown(): void
    {
        $controller = new AdminController($this->engine());
        $groups = $this->invoke($controller, 'dashboardGroups');

        $entries = [
            ['label' => 'T', 'group' => 'tools'],
            ['label' => 'S', 'group' => 'system'],
            ['label' => 'Z', 'group' => 'zzzcustom'],
            ['label' => 'P', 'group' => 'people'],
        ];

        $grouped = $this->invoke($controller, 'groupDashboardEntries', [$entries, $groups]);

        self::assertSame(['system', 'people', 'tools', 'zzzcustom'], array_column($grouped, 'id'));
        self::assertSame('System', $grouped[0]['label']);
        self::assertSame('Zzzcustom', $grouped[3]['label']);
        self::assertCount(1, $grouped[0]['items']);
    }

    public function testGroupEntriesEmptyOrderFallback(): void
    {
        $controller = new AdminController($this->engine());

        $grouped = $this->invoke($controller, 'groupDashboardEntries', [
            [['label' => 'A', 'group' => 'alpha'], ['label' => 'B', 'group' => 'beta']],
            [],
        ]);

        self::assertSame(['alpha', 'beta'], array_column($grouped, 'id'));
    }

    public function testGetConfigFallback(): void
    {
        $app = $this->engine();
        $app->set('pubvana.siteName', 'Hi');
        $controller = new AdminController($app);

        self::assertSame('Hi', $this->invoke($controller, 'getConfig', ['siteName']));
        self::assertSame('dflt', $this->invoke($controller, 'getConfig', ['missing', 'dflt']));
    }

    public function testTrustCachedStatus(): void
    {
        $item = $this->trustItem();
        $row = ['status' => 'trusted', 'warning' => null, 'checked_at' => '2026-01-01'];

        $controller = new AdminController($this->engine(trustCached: $row));
        self::assertSame($row, $this->invoke($controller, 'trustCachedStatus', [$item]));

        $throwing = new AdminController($this->engine(trustThrow: true));
        self::assertNull($this->invoke($throwing, 'trustCachedStatus', [$item]));
    }

    public function testTrustLiveStatus(): void
    {
        $item = $this->trustItem();
        $live = ['status' => 'trusted', 'warning' => null, 'answered' => true];

        $controller = new AdminController($this->engine(trustLive: $live));
        self::assertSame($live, $this->invoke($controller, 'trustLiveStatus', [$item]));

        $throwing = new AdminController($this->engine(trustThrow: true));
        self::assertSame(
            ['status' => 'unknown', 'warning' => null, 'answered' => false],
            $this->invoke($throwing, 'trustLiveStatus', [$item])
        );
    }

    public function testTrustGateNullItemProceeds(): void
    {
        $controller = new AdminController($this->engine());

        self::assertNull($this->invoke($controller, 'trustGate', [null, 'plugin', [], false, false]));
    }

    public function testTrustGateForceProceedsWhenClean(): void
    {
        $controller = new AdminController($this->engine(
            trustCached: ['status' => 'trusted', 'warning' => null, 'checked_at' => 'x']
        ));

        self::assertNull($this->invoke($controller, 'trustGate', [$this->trustItem(), 'plugin', ['id' => 1], true, false]));
        self::assertNull($this->invoke($controller, 'trustGate', [$this->trustItem(), 'plugin', ['id' => 1], true, true]));
    }

    public function testTrustGateForceRefusesCachedMalicious(): void
    {
        $app = $this->engine(
            trustCached: ['status' => TrustCache::STATUS_MALICIOUS, 'warning' => 'bad', 'checked_at' => 'x']
        );
        $controller = new AdminController($app);

        self::assertSame(
            'has been found malicious by the Pubvana trust service',
            $this->invoke($controller, 'trustGate', [$this->trustItem(), 'plugin', ['id' => 1], true, false])
        );
        self::assertCount(0, $this->halts);

        try {
            $this->invoke($controller, 'trustGate', [$this->trustItem(), 'plugin', ['id' => 1], true, true]);
            self::fail('ajax force malicious must halt');
        } catch (JsonHaltProbe $e) {
            self::assertSame(['blocked' => true, 'warning' => 'bad'], $this->halts[0]['data']);
        }
    }

    public function testTrustGateAjaxPaths(): void
    {
        $payload = ['id' => 7];

        $trusted = new AdminController($this->engine(trustLive: ['status' => 'trusted', 'warning' => null, 'answered' => true]));
        self::assertNull($this->invoke($trusted, 'trustGate', [$this->trustItem(), 'plugin', $payload, false, true]));

        $maliciousApp = $this->engine(trustLive: ['status' => TrustCache::STATUS_MALICIOUS, 'warning' => 'w', 'answered' => true]);
        $malicious = new AdminController($maliciousApp);
        try {
            $this->invoke($malicious, 'trustGate', [$this->trustItem(), 'plugin', $payload, false, true]);
            self::fail('ajax malicious must halt');
        } catch (JsonHaltProbe $e) {
            self::assertSame(['blocked' => true, 'warning' => 'w'], $this->halts[0]['data']);
        }

        $this->halts = [];
        $unknownApp = $this->engine(trustLive: ['status' => TrustCache::STATUS_UNKNOWN, 'warning' => null, 'answered' => true]);
        $unknown = new AdminController($unknownApp);
        try {
            $this->invoke($unknown, 'trustGate', [$this->trustItem(), 'plugin', $payload, false, true]);
            self::fail('ajax unknown must halt');
        } catch (JsonHaltProbe $e) {
            self::assertSame(['needsConfirm' => true, 'plugin' => $payload], $this->halts[0]['data']);
        }
    }

    public function testTrustGateNonAjaxOnlyStopsCachedMalicious(): void
    {
        $clean = new AdminController($this->engine(
            trustCached: ['status' => 'trusted', 'warning' => null, 'checked_at' => 'x']
        ));
        self::assertNull($this->invoke($clean, 'trustGate', [$this->trustItem(), 'plugin', [], false, false]));

        $miss = new AdminController($this->engine(trustCached: null));
        self::assertNull($this->invoke($miss, 'trustGate', [$this->trustItem(), 'plugin', [], false, false]));

        $bad = new AdminController($this->engine(
            trustCached: ['status' => TrustCache::STATUS_MALICIOUS, 'warning' => 'w', 'checked_at' => 'x']
        ));
        self::assertSame(
            'has been found malicious by the Pubvana trust service',
            $this->invoke($bad, 'trustGate', [$this->trustItem(), 'plugin', [], false, false])
        );
    }

    public function testIndexRendersGroupedDashboard(): void
    {
        $app = $this->engine();
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $adext->register('admin.dashboard', 'cards', 'pubvana.blog', [
            'label' => 'Blog',
            'callable' => static fn(array $context): array => [[
                'label' => 'Posts',
                'href' => '/posts',
                'group' => 'content',
            ]],
        ]);
        $adext->register('admin.dashboard', 'sections', 'pubvana.media', [
            'label' => 'Media',
            'callable' => static fn(array $context): array => [[
                'title' => 'Library',
                'group' => 'media',
            ]],
        ]);
        $controller = new AdminController($app);
        $controller->index();

        self::assertCount(1, $this->fetches);
        self::assertSame('admin/dashboard', $this->fetches[0]['view']);
        self::assertSame('Dashboard', $this->fetches[0]['data']['pageTitle']);
        $ids = array_column($this->fetches[0]['data']['groups'], 'id');
        self::assertSame(['content', 'media'], $ids);
        self::assertCount(1, $this->renders);
        self::assertSame('admin/layouts/admin', $this->renders[0]['template']);
    }

    public function testRenderLayoutFalsePassesThrough(): void
    {
        $app = $this->engine();
        $controller = new AdminController($app);
        $this->invoke($controller, 'render', ['admin/x', ['pageTitle' => 'X'], false]);

        self::assertSame('admin/x', $this->renders[0]['template']);
        self::assertSame('X', $this->renders[0]['data']['pageTitle']);
    }

    public function testRenderLayoutBuildsNav(): void
    {
        $app = $this->engine();
        $app->set('admin.topNav', [
            'content' => ['label' => 'Content', 'icon' => 'ti-a', 'subLabels' => ['links' => ['label' => 'Links']]],
            'broken' => 'nope',
        ]);
        /** @var ExtensionRegistry $adext */
        $adext = $app->adext();
        $adext->register('admin.menu', 'content.links', 'pubvana.blog', [
            'label' => 'Blog',
            'url' => '/blog',
        ]);
        $adext->register('admin.menu', 'content', 'pubvana.pages', [
            'label' => 'Pages',
            'url' => '/pages',
        ]);

        $controller = new AdminController($app);
        $this->invoke($controller, 'render', ['admin/x', ['pageTitle' => 'X']]);

        $layout = $this->renders[0];
        self::assertSame('admin/layouts/admin', $layout['template']);
        self::assertSame('CONTENT:admin/x', $layout['data']['content']);
        self::assertSame('Test User', $layout['data']['userGroups']);
        self::assertCount(1, $layout['data']['nav']);
        self::assertSame('content', $layout['data']['nav'][0]['key']);
        self::assertSame('label', $layout['data']['nav'][0]['entries'][0]['type']);
        self::assertSame('item', $layout['data']['nav'][0]['entries'][1]['type']);
    }

    public function testRenderSurvivesAuthFailure(): void
    {
        $app = $this->engine(authThrow: true);
        $app->set('admin.topNav', []);
        $controller = new AdminController($app);
        $this->invoke($controller, 'render', ['admin/x', []]);

        self::assertNull($this->renders[0]['data']['user']);
        self::assertSame('', $this->renders[0]['data']['userGroups']);
        self::assertSame('Dashboard', $this->renders[0]['data']['pageTitle']);
    }

    /**
     * @return array{type: string, slug: string, version: string, author: string, origin: string}
     */
    private function trustItem(): array
    {
        return ['type' => 'plugin', 'slug' => 'x', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'];
    }

    /** @param array<string, mixed> $row */
    public function recordFetch(string $view, array $row): void
    {
        $this->fetches[] = $row + ['view' => $view];
    }

    /** @param array<string, mixed> $row */
    public function recordRender(array $row): void
    {
        $this->renders[] = $row;
    }

    /** @param array<string, mixed> $row */
    public function recordHalt(array $row): void
    {
        $this->halts[] = $row;
    }

    /**
     * @param array{status: string, warning: ?string, checked_at: string}|null $trustCached
     * @param array{status: string, warning: ?string, answered: bool}|null $trustLive
     */
    private function engine(
        ?array $trustCached = null,
        ?array $trustLive = null,
        bool $trustThrow = false,
        bool $authThrow = false
    ): Engine {
        $test = $this;
        $app = $this->app([
            'adext' => static function (): ExtensionRegistry {
                static $registry = null;
                if ($registry === null) {
                    $registry = new ExtensionRegistry();
                }

                return $registry;
            },
            'auth' => static fn(): object => $authThrow
                ? new class {
                    /** @return never */
                    public function user(): never
                    {
                        throw new \RuntimeException('no auth');
                    }
                }
                : new class {
                    public function user(): object
                    {
                        return new class {
                            /** @return list<string> */
                            public function getGroups(): array
                            {
                                return ['Test User'];
                            }
                        };
                    }
                },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private AdminControllerTest $test)
                    {
                    }

                    /** @param array<string, mixed>|null $data */
                    public function fetch(string $view, ?array $data = null): string
                    {
                        $this->test->recordFetch($view, ['data' => $data ?? []]);

                        return 'CONTENT:' . $view;
                    }
                };
            },
            'trustClient' => static fn(): object => new class($trustCached, $trustLive, $trustThrow) {
                /** @param array<string, mixed>|null $cached @param array<string, mixed>|null $live */
                public function __construct(private ?array $cached, private ?array $live, private bool $throw)
                {
                }

                /** @return array<string, mixed>|null */
                public function getCachedStatus(string $t, string $s, string $v, string $a): ?array
                {
                    if ($this->throw) {
                        throw new \RuntimeException('down');
                    }

                    return $this->cached;
                }

                /** @return array<string, mixed> */
                public function checkAddon(string $t, string $s, string $v, string $a, string $o): array
                {
                    if ($this->throw) {
                        throw new \RuntimeException('down');
                    }

                    return $this->live ?? ['status' => 'trusted', 'warning' => null, 'answered' => true];
                }
            },
        ]);

        $app->set('CMS.siteName', 'Test Site');
        $app->map('render', function (string $template, array $data) use ($test): void {
            $test->recordRender(['template' => $template, 'data' => $data]);
        });
        $app->map('jsonHalt', function (mixed $data, int $code = 200) use ($test): never {
            $test->recordHalt(['data' => $data, 'code' => $code]);
            throw new JsonHaltProbe();
        });

        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * Probe exception standing in for Flight halt during trust-gate tests.
 */
final class JsonHaltProbe extends \RuntimeException
{
}
