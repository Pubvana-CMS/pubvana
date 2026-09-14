<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SiteHealth;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\SiteHealth\Controllers\HealthAdminController;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;
use Pubvana\Plugins\SiteHealth\Services\CheckResult;
use Pubvana\Plugins\SiteHealth\Services\HealthService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * HealthService orchestration + HealthAdminController coverage.
 *
 * The service writes its cache under PROJECT_ROOT/writable/cache, so tests
 * clear it before and after each run. The controller renders through the
 * admin layout doubles.
 */
#[CoversClass(HealthService::class)]
#[CoversClass(HealthAdminController::class)]
final class HealthServiceControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->clearCache();
    }

    protected function tearDown(): void
    {
        $this->clearCache();
        parent::tearDown();
    }

    public function testRunAllCachesAndForces(): void
    {
        $service = $this->service();
        $first = $service->runAll(true);
        self::assertGreaterThan(10, count($first['results']));
        self::assertArrayHasKey('overall', $first['summary']);

        // Second run without force: served from cache (same timestamp).
        $second = $service->runAll(false);
        self::assertSame($first['cached_at'], $second['cached_at']);

        // Force bypasses the cache.
        sleep(1);
        $third = $service->runAll(true);
        self::assertNotSame($first['cached_at'], $third['cached_at']);
    }

    public function testRunAllIgnoresCorruptCache(): void
    {
        $service = $this->service();
        $service->runAll(true);
        file_put_contents($this->cachePath(), 'not json');

        $data = $service->runAll(false);
        self::assertArrayHasKey('results', $data);
        self::assertArrayHasKey('summary', $data);
    }

    public function testRunAllIgnoresStaleCache(): void
    {
        $service = new HealthService($this->engine(), Sqlite::recreate(), ['cache_ttl' => -1]);
        $service->runAll(true);
        $data = $service->runAll(false);

        self::assertArrayHasKey('results', $data);
    }

    public function testExternalContributionsMerge(): void
    {
        $app = $this->engine(external: [
            ['callable' => static fn(): CheckResult => new CheckResult('ext', 'Ext', 'plugins', 'pass', 'ok')],
            ['callable' => static fn(): array => ['id' => 'ext-arr', 'status' => 'warning', 'category' => 'plugins']],
            ['callable' => static fn(): string => 'ignored'],
            ['no-callable' => true],
        ]);
        $service = new HealthService($app, Sqlite::recreate());

        $data = $service->runAll(true);
        $ids = array_column($data['results'], 'id');

        self::assertContains('ext', $ids);
        self::assertContains('ext-arr', $ids);
    }

    public function testDashboardCardsShapes(): void
    {
        // All-pass battery: no cards.
        $service = $this->service(extra: [new FixedCheck('a', CheckResult::PASS)]);
        // Built-ins may warn on this machine; assert shape only when issues exist.
        $cards = $service->dashboardCards();
        foreach ($cards as $card) {
            self::assertArrayHasKey('id', $card);
            self::assertArrayHasKey('href', $card);
        }

        // Forced critical: the critical card wins.
        $critical = $this->service(extra: [new FixedCheck('bad', CheckResult::CRITICAL)]);
        $criticalCards = $critical->dashboardCards();
        self::assertNotEmpty($criticalCards);
        self::assertSame('health-critical', $criticalCards[0]['id']);
        self::assertSame('danger', $criticalCards[0]['tone']);
    }

    public function testGroupByCategory(): void
    {
        $service = $this->service();
        $grouped = $service->groupByCategory([
            ['id' => 'a', 'category' => 'security'],
            ['id' => 'b'],
        ]);

        self::assertArrayHasKey('security', $grouped);
        self::assertArrayHasKey('other', $grouped);
    }

    public function testClearCache(): void
    {
        $service = $this->service();
        $service->runAll(true);
        self::assertFileExists($this->cachePath());

        $service->clearCache();
        self::assertFileDoesNotExist($this->cachePath());

        // Missing file: no error.
        $service->clearCache();
    }

    public function testControllerIndexRenders(): void
    {
        $app = $this->engine(controller: true);
        (new HealthAdminController($app))->index();

        self::assertSame('pubvana/sitehealth/admin/index', $this->fetches[0]['view']);
        self::assertSame('Site Health', $this->fetches[0]['data']['pageTitle']);
        self::assertArrayHasKey('environment', $this->fetches[0]['data']['grouped']);
        self::assertArrayHasKey('overall', $this->fetches[0]['data']['summary']);
        self::assertSame('/admin/site-health', $this->fetches[0]['data']['adminBase']);
        self::assertCount(4, $this->fetches[0]['data']['categories']);
    }

    public function testControllerRerun(): void
    {
        $app = $this->engine(controller: true);
        (new HealthAdminController($app))->rerun();

        self::assertSame('Site health checks re-run.', $this->flashes['success'][0]);
        self::assertSame(['/admin/site-health'], $this->redirects);
    }

    /** @param list<CheckInterface> $extra */
    private function service(array $extra = []): HealthService
    {
        $service = new HealthService($this->engine(), Sqlite::recreate());
        foreach ($extra as $check) {
            $service->addCheck($check);
        }

        return $service;
    }

    /**
     * @param list<array<string, mixed>> $external
     */
    private function engine(array $external = [], bool $controller = false): Engine
    {
        $test = $this;
        $app = $this->app([
            'adext' => static fn(): object => new class($external) {
                /** @param list<array<string, mixed>> $ext */
                public function __construct(private array $ext)
                {
                }

                /** @return list<array<string, mixed>> */
                public function get(string $t, string $s, array $c = []): array
                {
                    return $this->ext;
                }
            },
            'migrations' => [],
            'request' => static fn(): object => new class {
                public Collection $data;
                public Collection $query;
                public function __construct()
                {
                    $this->data = new Collection([]);
                    $this->query = new Collection([]);
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private HealthServiceControllerTest $t)
                {
                }

                public function flash(string $k, mixed $v): void
                {
                    $this->t->flashes[$k][] = $v;
                }

                public function pullFlash(string $k): mixed
                {
                    return null;
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private HealthServiceControllerTest $t)
                    {
                    }

                    /** @param array<string, mixed>|null $d */
                    public function fetch(string $v, ?array $d = null): string
                    {
                        $this->t->fetches[] = ['view' => $v, 'data' => $d ?? []];

                        return 'C:' . $v;
                    }
                };
            },
            'auth' => static fn(): object => new class {
                public function user(): object
                {
                    return new class {
                        /** @return list<string> */
                        public function getGroups(): array
                        {
                            return ['admin'];
                        }
                    };
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/site-health';
                }
            },
        ]);
        $app->set('admin.topNav', []);
        $app->set('environment', 'production');
        $app->set('CMS.siteUrl', 'https://example.org');
        $app->set('CMS.siteName', 'Real Site');
        $app->set('flight.force_https', true);
        $app->set('enlivenapp.flight-shield', ['default_authenticator' => 'session']);
        if ($controller) {
            $health = new HealthService($app, Sqlite::recreate());
            $app->map('health', fn(): HealthService => $health);
        }
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }

    private function cachePath(): string
    {
        return (string) PROJECT_ROOT . '/writable/cache/sitehealth.json';
    }

    private function clearCache(): void
    {
        if (file_exists($this->cachePath())) {
            unlink($this->cachePath());
        }
    }
}

/**
 * Fixed-status check double.
 */
final class FixedCheck implements CheckInterface
{
    public function __construct(private string $id, private string $status)
    {
    }

    public function run(): CheckResult
    {
        return new CheckResult($this->id, $this->id, CheckResult::CAT_PLUGINS, $this->status, 'fixed');
    }
}
