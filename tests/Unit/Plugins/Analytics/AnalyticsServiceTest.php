<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Analytics;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Analytics\Services\AnalyticsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * AnalyticsService reporting and pure helpers against in-memory SQLite.
 *
 * logView/maybeRollup/rollup are CLI-guarded or MySQL-specific and stay
 * out; the rollup upsert SQL already has RollupUpsertSqlTest. Reporting
 * tests use hot-window ranges so only the raw table is consulted.
 */
#[CoversClass(AnalyticsService::class)]
final class AnalyticsServiceTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    private array $settingsData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
        $this->settingsData = [];
    }

    private function createTables(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE analytics_page_views (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                page_path       TEXT NOT NULL,
                page_group      TEXT NOT NULL,
                referrer_domain TEXT,
                viewed_at       TEXT NOT NULL
            )'
        );
        $pdo->exec(
            'CREATE TABLE analytics_views_daily (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                day        TEXT NOT NULL,
                page_group TEXT NOT NULL,
                page_path  TEXT NOT NULL,
                view_count INTEGER NOT NULL DEFAULT 0
            )'
        );
        $pdo->exec(
            'CREATE TABLE analytics_referrers_daily (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                day             TEXT NOT NULL,
                referrer_domain TEXT NOT NULL,
                view_count      INTEGER NOT NULL DEFAULT 0
            )'
        );
    }

    /**
     * @param array<string, mixed> $config
     * @return AnalyticsService
     */
    private function service(array $config = []): AnalyticsService
    {
        $test = $this;
        $app = $this->app([
            'settings' => static fn (): object => new class ($test) {
                public function __construct(private AnalyticsServiceTest $test)
                {
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->test->settingsGet($key, $default);
                }

                public function set(string $key, mixed $value): void
                {
                    $this->test->settingsSet($key, $value);
                }
            },
        ]);

        return new AnalyticsService($this->pdo, $app, array_merge([
            'tracking' => [
                'skip_prefixes' => ['/admin', '/api', '/assets'],
                'skip_paths' => ['/feed', '/rss', '/sitemap.xml', '/robots.txt'],
            ],
            'rollup' => ['hot_days' => 30],
        ], $config));
    }

    public function settingsGet(string $key, mixed $default = null): mixed
    {
        return $this->settingsData[$key] ?? $default;
    }

    public function settingsSet(string $key, mixed $value): void
    {
        $this->settingsData[$key] = $value;
    }

    private function insertView(string $path, string $group, string $viewedAt, ?string $referrer = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO analytics_page_views (page_path, page_group, referrer_domain, viewed_at)
             VALUES (:path, :group, :referrer, :viewed)'
        );
        self::assertNotFalse($stmt);
        $stmt->execute([
            ':path' => $path,
            ':group' => $group,
            ':referrer' => $referrer,
            ':viewed' => $viewedAt,
        ]);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public function testNormalizeRange(): void
    {
        $service = $this->service();

        self::assertSame('7', $service->normalizeRange('7'));
        self::assertSame('30', $service->normalizeRange('30'));
        self::assertSame('all', $service->normalizeRange('all'));
        self::assertSame('all', $service->normalizeRange(' ALL '));
        self::assertSame('30', $service->normalizeRange('bogus'));
        self::assertSame('30', $service->normalizeRange(''));
        self::assertSame('30', $service->normalizeRange('14'));
    }

    public function testGroupForPath(): void
    {
        $service = $this->service();

        self::assertSame('home', $service->groupForPath('/'));
        self::assertSame('home', $service->groupForPath(''));
        self::assertSame('blog', $service->groupForPath('/blog/hello'));
        self::assertSame('blog', $service->groupForPath('/Blog/hello'));
        self::assertSame('page', $service->groupForPath('/page/about'));
    }

    public function testIsTrackingEnabledCaches(): void
    {
        $service = $this->service();

        self::assertTrue($service->isTrackingEnabled());

        $this->settingsData['Analytics.tracking_enabled'] = false;
        // Cached true from the first read.
        self::assertTrue($service->isTrackingEnabled());

        self::assertFalse($this->service()->isTrackingEnabled());
    }

    public function testNormalizePath(): void
    {
        $service = $this->service();

        self::assertSame('/', $this->invoke($service, 'normalizePath', ['/', '/']));
        self::assertSame('/blog/hi', $this->invoke($service, 'normalizePath', ['/blog/hi/', '/']));
        self::assertSame('/blog/hi', $this->invoke($service, 'normalizePath', ['/blog//hi', '/']));
        self::assertSame('/hi', $this->invoke($service, 'normalizePath', ['/sub/hi', '/sub']));
        self::assertSame('/hi', $this->invoke($service, 'normalizePath', ['http://x.test/hi?a=1', '/']));
    }

    public function testShouldSkip(): void
    {
        $service = $this->service();

        self::assertTrue($this->invoke($service, 'shouldSkip', ['/admin/pages']));
        self::assertTrue($this->invoke($service, 'shouldSkip', ['/admin']));
        self::assertTrue($this->invoke($service, 'shouldSkip', ['/api/ai/posts']));
        self::assertTrue($this->invoke($service, 'shouldSkip', ['/feed']));
        self::assertTrue($this->invoke($service, 'shouldSkip', ['/style.css']));
        self::assertTrue($this->invoke($service, 'shouldSkip', ['/app.js']));
        self::assertTrue($this->invoke($service, 'shouldSkip', ['/photo.JPG']));
        self::assertFalse($this->invoke($service, 'shouldSkip', ['/blog/hello']));
        self::assertFalse($this->invoke($service, 'shouldSkip', ['/']));
    }

    public function testIsBot(): void
    {
        $service = $this->service();

        self::assertFalse($this->invoke($service, 'isBot', ['']));
        self::assertFalse($this->invoke($service, 'isBot', ['Mozilla/5.0 (Windows NT 10.0) Chrome/120']));
        self::assertTrue($this->invoke($service, 'isBot', ['Googlebot/2.1']));
        self::assertTrue($this->invoke($service, 'isBot', ['curl/8.0']));
        self::assertTrue($this->invoke($service, 'isBot', ['python-requests/2.0']));
    }

    public function testReferrerDomain(): void
    {
        $service = $this->service();

        self::assertNull($this->invoke($service, 'referrerDomain', ['']));
        self::assertNull($this->invoke($service, 'referrerDomain', ['not-a-url']));
        self::assertSame('example.com', $this->invoke($service, 'referrerDomain', ['https://Example.COM/page']));
        self::assertSame('example.com', $this->invoke($service, 'referrerDomain', ['http://example.com:8080/x']));
    }

    public function testClipAndLabels(): void
    {
        $service = $this->service();

        self::assertNull($this->invoke($service, 'clip', [null, 5]));
        self::assertNull($this->invoke($service, 'clip', ['', 5]));
        self::assertSame('abc', $this->invoke($service, 'clip', ['abc', 5]));
        self::assertSame('ab', $this->invoke($service, 'clip', ['abc', 2]));

        self::assertSame('Jan 2026', $this->invoke($service, 'monthLabel', ['2026-01']));
        self::assertSame('2026-02', $this->invoke($service, 'nextMonth', ['2026-01']));
        self::assertSame('2027-01', $this->invoke($service, 'nextMonth', ['2026-12']));
        self::assertSame(date('M j'), (string) $this->invoke($service, 'dayLabel', [date('Y-m-d')]));
    }

    public function testTotalViewsCountsHotWindow(): void
    {
        $service = $this->service();

        $this->insertView('/blog/a', 'blog', $this->now());
        $this->insertView('/blog/b', 'blog', $this->now());
        $this->insertView('/old', 'old', '2000-01-01 00:00:00');

        self::assertSame(2, $service->totalViews('7'));
        self::assertSame(2, $service->totalViews('30'));
    }

    public function testTopContentOrdersByViews(): void
    {
        $service = $this->service();

        $this->insertView('/blog/a', 'blog', $this->now());
        $this->insertView('/blog/a', 'blog', $this->now());
        $this->insertView('/blog/a', 'blog', $this->now());
        $this->insertView('/page/b', 'page', $this->now());
        $this->insertView('/old', 'old', '2000-01-01 00:00:00');

        $top = $service->topContent('7', 10);
        self::assertCount(2, $top);
        self::assertSame('/blog/a', $top[0]['page_path']);
        self::assertSame(3, $top[0]['view_count']);
        self::assertSame('blog', $top[0]['page_group']);
        self::assertSame(1, $top[1]['view_count']);

        $limited = $service->topContent('7', 1);
        self::assertCount(1, $limited);
    }

    public function testReferrersExcludesBlanks(): void
    {
        $service = $this->service();

        $this->insertView('/a', 'blog', $this->now(), 'example.com');
        $this->insertView('/b', 'blog', $this->now(), 'example.com');
        $this->insertView('/c', 'blog', $this->now(), 'other.test');
        $this->insertView('/d', 'blog', $this->now(), null);
        $this->insertView('/e', 'blog', $this->now(), '');

        $refs = $service->referrers('7', 10);
        self::assertCount(2, $refs);
        self::assertSame('example.com', $refs[0]['referrer_domain']);
        self::assertSame(2, $refs[0]['view_count']);
        self::assertSame('other.test', $refs[1]['referrer_domain']);
    }

    public function testTrendZeroFillsAndOrdersGroups(): void
    {
        $service = $this->service();

        $today = date('Y-m-d H:i:s');
        $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
        $this->insertView('/blog/a', 'blog', $today);
        $this->insertView('/blog/b', 'blog', $today);
        $this->insertView('/blog/c', 'blog', $today);
        $this->insertView('/page/x', 'page', $yesterday);

        $trend = $service->trend('7');
        self::assertSame('day', $trend['granularity']);
        self::assertCount(7, $trend['labels']);
        self::assertCount(2, $trend['series']);
        // Busiest group first.
        self::assertSame('blog', $trend['series'][0]['label']);
        self::assertCount(7, $trend['series'][0]['values']);
        self::assertSame(4, array_sum($trend['series'][0]['values']) + array_sum($trend['series'][1]['values']));
        // Today is the last bucket.
        self::assertSame(3, $trend['series'][0]['values'][6]);
    }

    public function testTrendEmpty(): void
    {
        $service = $this->service();

        $trend = $service->trend('7');
        self::assertSame('day', $trend['granularity']);
        self::assertSame([], $trend['series']);
        self::assertCount(7, $trend['labels']);
    }

    public function testDashboardShape(): void
    {
        $service = $this->service();

        $this->insertView('/blog/a', 'blog', $this->now(), 'example.com');

        $report = $service->dashboard('bogus');
        self::assertSame('30', $report['range']);
        self::assertSame(1, $report['totalViews']);
        self::assertArrayHasKey('granularity', $report['trends']);
        self::assertCount(1, $report['topContent']);
        self::assertCount(1, $report['referrers']);
    }

    public function testLogViewAndMaybeRollupNoopOnCli(): void
    {
        $service = $this->service();

        // PHPUnit runs on CLI: both must return without touching the DB.
        $service->logView();
        $service->maybeRollup();

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM analytics_page_views');
        self::assertNotFalse($stmt);
        self::assertSame(0, (int) $stmt->fetchColumn());
    }
}
