<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Analytics;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Analytics\Models\BotsDaily;
use Pubvana\Plugins\Analytics\Models\PageView;
use Pubvana\Plugins\Analytics\Models\ReferrersDaily;
use Pubvana\Plugins\Analytics\Models\ViewsDaily;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createTables($this->pdo);
    }

    private function createTables(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE analytics_page_views (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                page_path       TEXT NOT NULL,
                page_group      TEXT NOT NULL,
                referrer_domain TEXT,
                is_bot          INTEGER NOT NULL DEFAULT 0,
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
        $pdo->exec(
            'CREATE TABLE analytics_bots_daily (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                day        TEXT NOT NULL,
                view_count INTEGER NOT NULL DEFAULT 0
            )'
        );
    }

    /**
     * @param array<string, mixed> $config
     * @return AnalyticsService
     */
    private function service(array $config = [], string $siteUrl = ''): AnalyticsService
    {
        $app = $this->app();
        $app->set('siteUrl', $siteUrl);

        return new AnalyticsService(
            new PageView($this->pdo),
            new ViewsDaily($this->pdo),
            new ReferrersDaily($this->pdo),
            new BotsDaily($this->pdo),
            $app,
            array_merge([
                'tracking' => [
                    'skip_prefixes' => ['/admin', '/api', '/assets'],
                    'skip_paths' => ['/feed', '/rss', '/sitemap.xml', '/robots.txt'],
                ],
                'rollup' => ['hot_days' => 30],
            ], $config)
        );
    }

    private function insertView(string $path, string $group, string $viewedAt, ?string $referrer = null, int $isBot = 0): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO analytics_page_views (page_path, page_group, referrer_domain, is_bot, viewed_at)
             VALUES (:path, :group, :referrer, :is_bot, :viewed)'
        );
        self::assertNotFalse($stmt);
        $stmt->execute([
            ':path' => $path,
            ':group' => $group,
            ':referrer' => $referrer,
            ':is_bot' => $isBot,
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

    public function testReferrerDomainDropsOwnHost(): void
    {
        $service = $this->service([], 'https://mysite.test');

        self::assertNull($this->invoke($service, 'referrerDomain', ['https://mysite.test/blog']));
        self::assertNull($this->invoke($service, 'referrerDomain', ['https://www.mysite.test/blog']));
        self::assertSame('other.test', $this->invoke($service, 'referrerDomain', ['https://other.test/x']));
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

    public function testReferrersGroupsBlanksAsDirect(): void
    {
        $service = $this->service();

        $this->insertView('/a', 'blog', $this->now(), 'example.com');
        $this->insertView('/b', 'blog', $this->now(), 'example.com');
        $this->insertView('/c', 'blog', $this->now(), 'other.test');
        $this->insertView('/d', 'blog', $this->now(), null);
        $this->insertView('/e', 'blog', $this->now(), '');

        $refs = $service->referrers('7', 10);
        self::assertCount(3, $refs);

        $byDomain = [];
        foreach ($refs as $row) {
            $byDomain[$row['referrer_domain']] = $row['view_count'];
        }

        self::assertSame(2, $byDomain['example.com'] ?? null);
        self::assertSame(2, $byDomain['Direct'] ?? null);
        self::assertSame(1, $byDomain['other.test'] ?? null);
    }

    public function testBotsStayOutOfVisitorTotals(): void
    {
        $service = $this->service();

        $this->insertView('/a', 'blog', $this->now());
        $this->insertView('/a', 'blog', $this->now(), null, 1);

        self::assertSame(1, $service->totalViews('7'));
        self::assertCount(1, $service->topContent('7'));
    }

    public function testTrendAddsVisitorsAndRobotsSeries(): void
    {
        $service = $this->service();

        $this->insertView('/blog/a', 'blog', $this->now());
        $this->insertView('/blog/a', 'blog', $this->now());
        $this->insertView('/blog/a', 'blog', $this->now(), null, 1);

        $trend = $service->trend('7');
        $labels = array_column($trend['series'], 'label');

        self::assertSame('Visitors', $labels[0]);
        self::assertSame('Robots', $labels[1]);
        self::assertSame('blog', $labels[2]);
        self::assertSame(2, $trend['series'][0]['values'][6]);
        self::assertSame(1, $trend['series'][1]['values'][6]);
    }

    public function testPreviousTotalViewsComparesEqualPeriod(): void
    {
        $service = $this->service();

        $this->insertView('/a', 'blog', $this->now());
        $this->insertView('/a', 'blog', date('Y-m-d H:i:s', strtotime('-10 days')));

        self::assertSame(1, $service->previousTotalViews('7'));
        self::assertNull($service->previousTotalViews('all'));
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
        self::assertCount(4, $trend['series']);
        // Visitors and Robots come first, then the busiest group.
        self::assertSame('Visitors', $trend['series'][0]['label']);
        self::assertSame('Robots', $trend['series'][1]['label']);
        self::assertSame('blog', $trend['series'][2]['label']);
        self::assertCount(7, $trend['series'][2]['values']);
        self::assertSame(4, array_sum($trend['series'][2]['values']) + array_sum($trend['series'][3]['values']));
        // Today is the last bucket.
        self::assertSame(3, $trend['series'][2]['values'][6]);
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

    public function testContentPagePaginates(): void
    {
        $service = $this->service();

        $this->insertView('/a', 'blog', $this->now());
        $this->insertView('/b', 'blog', $this->now());
        $this->insertView('/b', 'blog', $this->now());

        $first = $service->contentPage('7', 1, 1);
        self::assertSame(2, $first['total']);
        self::assertCount(1, $first['rows']);
        self::assertSame('/b', $first['rows'][0]['page_path']);

        $second = $service->contentPage('7', 2, 1);
        self::assertSame('/a', $second['rows'][0]['page_path']);
    }

    public function testReferrersPageFoldsDirect(): void
    {
        $service = $this->service();

        $this->insertView('/a', 'blog', $this->now());
        $this->insertView('/b', 'blog', $this->now(), 'example.com');
        $this->insertView('/c', 'blog', $this->now(), 'example.com');

        $page = $service->referrersPage('7', 1, 10);
        self::assertSame(2, $page['total']);
        self::assertSame('example.com', $page['rows'][0]['referrer_domain']);
        self::assertSame(2, $page['rows'][0]['view_count']);
        self::assertSame('Direct', $page['rows'][1]['referrer_domain']);
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
