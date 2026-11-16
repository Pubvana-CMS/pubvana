<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Services;

use Pubvana\Plugins\Analytics\Models\BotsDaily;
use Pubvana\Plugins\Analytics\Models\PageView;
use Pubvana\Plugins\Analytics\Models\ReferrersDaily;
use Pubvana\Plugins\Analytics\Models\ViewsDaily;
use flight\Engine;

/**
 * AnalyticsService - Page view tracking, rollup, and reporting.
 *
 * Tracking runs server-side after a public route dispatches. Every view
 * event lands as a row in analytics_page_views; a daily rollup compacts
 * rows older than the hot window into two per-day aggregate tables, so
 * retention is effectively unbounded without one row per hit.
 *
 * Content buckets (the 'group' column) are derived from the first URL
 * segment, so /blog/x becomes 'blog', /page/x becomes 'page', the
 * homepage becomes 'home', and an unknown plugin's prefix becomes its
 * own group automatically.
 *
 * @package Pubvana\Plugins\Analytics\Services
 */
class AnalyticsService
{
    private PageView $pageViews;

    private ViewsDaily $viewsDaily;

    private ReferrersDaily $referrersDaily;

    private BotsDaily $botsDaily;

    /** @var Engine<object> */
    private Engine $app;

    /** @var array<string, mixed> */
    private array $config;

    private const BOT_KEYWORDS = [
        'bot', 'crawler', 'spider', 'slurp', 'curl', 'wget', 'python',
        'libwww', 'httpclient', 'headless', 'phantomjs', 'puppeteer',
        'playwright', 'ahrefs', 'mj12', 'semrush', 'dotbot', 'yandex',
        'baiduspider', 'googlebot', 'bingbot', 'applebot',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'petalbot',
        'uptimerobot', 'pingdom', 'monitor', 'validator',
    ];

    private const STATIC_EXTENSIONS = [
        'css', 'js', 'map', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg',
        'webp', 'ico', 'bmp', 'avif', 'woff', 'woff2', 'ttf', 'eot', 'otf',
        'xml', 'txt', 'pdf', 'mp4', 'webm', 'mp3', 'zip', 'tar', 'gz',
    ];

    private const RANGES = [7, 30, 90, 180, 365];

    /**
     * @param Engine<object>       $app
     * @param array<string, mixed> $config
     */
    public function __construct(
        PageView $pageViews,
        ViewsDaily $viewsDaily,
        ReferrersDaily $referrersDaily,
        BotsDaily $botsDaily,
        Engine $app,
        array $config = []
    ) {
        $this->pageViews = $pageViews;
        $this->viewsDaily = $viewsDaily;
        $this->referrersDaily = $referrersDaily;
        $this->botsDaily = $botsDaily;
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Build the full report dataset for a period.
     *
     * @param string $range One of '7', '30', '90', '180', '365', 'all'
     * @param int    $topContentLimit Rows to return for top content
     * @param int    $referrerLimit   Rows to return for referrers
     * @return array<string, mixed>
     */
    public function dashboard(string $range, int $topContentLimit = 10, int $referrerLimit = 10): array
    {
        $range = $this->normalizeRange($range);

        return [
            'range'              => $range,
            'totalViews'         => $this->totalViews($range),
            'previousTotalViews' => $this->previousTotalViews($range),
            'trends'             => $this->trend($range),
            'topContent'         => $this->topContent($range, $topContentLimit),
            'referrers'          => $this->referrers($range, $referrerLimit),
        ];
    }

    /**
     * Normalize a range token to one of '7', '30', '90', '180', '365', 'all'.
     */
    public function normalizeRange(string $range): string
    {
        $range = strtolower(trim($range));

        if ($range === 'all') {
            return 'all';
        }

        $days = (int) $range;
        return in_array($days, self::RANGES, true) ? (string) $days : '30';
    }

    /**
     * Day count for a finite range, or null for all-time.
     */
    private function rangeDays(string $range): ?int
    {
        $range = $this->normalizeRange($range);
        return $range === 'all' ? null : (int) $range;
    }

    /**
     * Number of days the raw table retains before rollup.
     */
    private function hotDays(): int
    {
        return (int) ($this->config['rollup']['hot_days'] ?? 30);
    }

    /**
     * Whether a range must consult the daily rollup tables.
     */
    private function usesDaily(string $range): bool
    {
        $days = $this->rangeDays($range);
        return $days === null || $days > $this->hotDays();
    }

    /**
     * Total view events in the given period.
     */
    public function totalViews(string $range): int
    {
        $days = $this->rangeDays($range);

        if (!$this->usesDaily($range)) {
            return $this->pageViews->visitorCountSince($this->cutoff($days ?? $this->hotDays()));
        }

        return $this->pageViews->visitorCountSince($this->cutoff($this->hotDays()))
            + $this->viewsDaily->sumSince($this->dailyBoundary($range));
    }

    /**
     * Visitor views in the equal-length period before the selected range.
     *
     * Null for 'all', which has no earlier period to compare against.
     */
    public function previousTotalViews(string $range): ?int
    {
        $days = $this->rangeDays($range);
        if ($days === null) {
            return null;
        }

        $end = strtotime("-{$days} days");
        $start = strtotime('-' . ($days * 2) . ' days');
        if ($end === false || $start === false) {
            return null;
        }

        $raw = $this->pageViews->visitorCountBetween(
            date('Y-m-d H:i:s', $start),
            date('Y-m-d H:i:s', $end)
        );
        $daily = $this->viewsDaily->sumBetween(date('Y-m-d', $start), date('Y-m-d', $end));

        return $raw + $daily;
    }

    /**
     * Per-group trend series for the chart, zero-filled across the period.
     *
     * @return array{granularity:string, labels:array<int,string>, series:array<int,array{label:string, values:array<int,int>}>}
     */
    public function trend(string $range): array
    {
        $monthly = $this->rangeDays($range) === null;
        $cutoff = $this->cutoff($this->rawCutoffDays($range));
        $boundary = $this->usesDaily($range) ? $this->dailyBoundary($range) : null;
        $map = [];
        $bots = [];

        $this->accumulateTrend($map, $this->pageViews->groupTrendSince($cutoff, $monthly));

        foreach ($this->pageViews->botTrendSince($cutoff, $monthly) as $row) {
            $bots[$row['bucket']] = ($bots[$row['bucket']] ?? 0) + $row['view_count'];
        }

        if ($this->usesDaily($range)) {
            $this->accumulateTrend($map, $this->viewsDaily->groupTrend($boundary, $monthly));

            foreach ($this->botsDaily->trend($boundary, $monthly) as $row) {
                $bots[$row['bucket']] = ($bots[$row['bucket']] ?? 0) + $row['view_count'];
            }
        }

        return $this->assembleTrend($map, $bots, $range, $monthly);
    }

    /**
     * Top content by hit count, ranked #1 by views down.
     *
     * @return array<int, array{page_path:string, page_group:string, view_count:int}>
     */
    public function topContent(string $range, int $limit = 10): array
    {
        $limit = max(1, $limit);

        $rows = $this->pageViews->topContentSince($this->cutoff($this->rawCutoffDays($range)), $limit);

        if ($this->usesDaily($range)) {
            $rows = $this->mergeTopContent(
                $rows,
                $this->viewsDaily->topContent($this->dailyBoundary($range), $limit)
            );
        }

        usort($rows, static function (array $a, array $b): int {
            return ($b['view_count'] <=> $a['view_count'])
                ?: strcmp($a['page_path'], $b['page_path']);
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * Top referrer domains in the given period.
     *
     * @return array<int, array{referrer_domain:string, view_count:int}>
     */
    public function referrers(string $range, int $limit = 10): array
    {
        $limit = max(1, $limit);

        $rows = $this->pageViews->referrerRowsSince($this->cutoff($this->rawCutoffDays($range)), $limit);

        if ($this->usesDaily($range)) {
            $rows = $this->mergeReferrers(
                $rows,
                $this->referrersDaily->rows($this->dailyBoundary($range), $limit)
            );
        }

        usort($rows, static function (array $a, array $b): int {
            return ($b['view_count'] <=> $a['view_count'])
                ?: strcmp($a['referrer_domain'], $b['referrer_domain']);
        });

        $rows = array_slice($rows, 0, $limit);

        return array_map(static fn (array $row): array => [
            'referrer_domain' => $row['referrer_domain'] === '' ? 'Direct' : $row['referrer_domain'],
            'view_count'      => $row['view_count'],
        ], $rows);
    }

    /**
     * One page of the full top content list for a range.
     *
     * @return array{rows: array<int, array{page_path:string, page_group:string, view_count:int}>, total:int}
     */
    public function contentPage(string $range, int $page, int $perPage): array
    {
        $cutoff = $this->cutoff($this->rawCutoffDays($range));
        $boundary = $this->usesDaily($range) ? $this->dailyBoundary($range) : null;
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        return [
            'rows'  => $this->pageViews->topContentPage($cutoff, $boundary, $perPage, ($page - 1) * $perPage),
            'total' => $this->pageViews->topContentCount($cutoff, $boundary),
        ];
    }

    /**
     * One page of the full referrer list for a range, with Direct folded in.
     *
     * @return array{rows: array<int, array{referrer_domain:string, view_count:int}>, total:int}
     */
    public function referrersPage(string $range, int $page, int $perPage): array
    {
        $cutoff = $this->cutoff($this->rawCutoffDays($range));
        $boundary = $this->usesDaily($range) ? $this->dailyBoundary($range) : null;
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $rows = $this->pageViews->referrerPage($cutoff, $boundary, $perPage, ($page - 1) * $perPage);

        return [
            'rows'  => array_map(static fn (array $row): array => [
                'referrer_domain' => $row['referrer_domain'] === '' ? 'Direct' : $row['referrer_domain'],
                'view_count'      => $row['view_count'],
            ], $rows),
            'total' => $this->pageViews->referrerCount($cutoff, $boundary),
        ];
    }

    /**
     * Roll older raw rows into the daily aggregate tables and remove them.
     *
     * Idempotent: once rolled, raw rows are deleted, so a re-run finds none.
     *
     * @return int Number of raw rows removed
     */
    public function rollup(): int
    {
        return $this->pageViews->rollupOlderThan(
            $this->cutoff($this->hotDays()),
            $this->viewsDaily,
            $this->referrersDaily,
            $this->botsDaily
        );
    }
    /**
     * Run the rollup at most once per day, guarded by a cache flag and lock.
     *
     * @return void
     */
    public function maybeRollup(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        $file = $this->rollupFlagPath();
        $today = date('Y-m-d');

        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            rewind($handle);
            $lastRun = trim((string) stream_get_contents($handle));
            if ($lastRun === $today) {
                return;
            }

            $this->rollup();

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $today);
            fflush($handle);
        } catch (\Throwable $e) {
            // Never break page delivery on a rollup failure, but leave a trail:
            // a silent failure hides a dead rollup.
            error_log('Analytics rollup failed: ' . $e->getMessage());
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Record the current request as a page view.
     *
     * Designed to be safe everywhere: failures are swallowed so tracking
     * never breaks page delivery. Only successful route dispatches matter;
     * 404s, admin, API, assets, known feeds, static suffixes, bots and
     * non-GET/HEAD verbs are skipped.
     *
     * @return void
     */
    public function logView(): void
    {
        try {
            if (php_sapi_name() === 'cli') {
                return;
            }

            $request = $this->app->request();
            if (!in_array($request->method, ['GET', 'HEAD'], true)) {
                return;
            }

            $path = $this->normalizePath($request->url, $request->base);
            if ($this->shouldSkip($path)) {
                return;
            }

            $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
            if ($this->isBot($ua)) {
                return;
            }

            $model = $this->pageViews->newRow();
            $model->page_path = $this->clip($path, 255) ?? '';
            $model->page_group = $this->clip($this->groupForPath($path), 50) ?? '';
            $model->referrer_domain = $this->clip($this->referrerDomain((string) ($_SERVER['HTTP_REFERER'] ?? '')), 255);
            $model->viewed_at = $this->now();
            $model->insert();
        } catch (\Throwable $e) {
            // Never break page delivery due to analytics failure. The guards
            // run inside the try too, so a settings or request failure is a
            // no-op rather than an error page.
        }
    }

    /**
     * Record page views for cache hits, which never boot the app.
     *
     * A hit never runs flight.route.executed, so the Analytics listener above
     * never runs for one. The read path writes one line per hit to
     * writable/cache/, and this processes that file on the 1m cron tick with
     * the same checks logView() uses.
     *
     * The method renames the file before it reads it, so a hit that arrives
     * mid-process writes to a new file and the count stays accurate.
     *
     * @return int Rows recorded
     */
    public function processPageCacheHits(): int
    {
        $log = $this->pageHitsFile();
        if (!is_file($log)) {
            return 0;
        }

        $processing = $log . '.processing';
        if (!@rename($log, $processing)) {
            return 0;
        }

        $handle = @fopen($processing, 'rb');
        if ($handle === false) {
            @unlink($processing);
            return 0;
        }

        $recorded = 0;

        while (($line = fgets($handle)) !== false) {
            $hit = json_decode(trim($line), true);
            if (!is_array($hit)) {
                continue;
            }

            $path = (string) ($hit['path'] ?? '');
            if ($path === '' || $this->shouldSkip($path)) {
                continue;
            }

            if ($this->isBot((string) ($hit['agent'] ?? ''))) {
                continue;
            }

            try {
                $model = $this->pageViews->newRow();
                $model->page_path = $this->clip($path, 255) ?? '';
                $model->page_group = $this->clip($this->groupForPath($path), 50) ?? '';
                $model->referrer_domain = $this->clip(
                    $this->referrerDomain((string) ($hit['referrer'] ?? '')),
                    255
                );
                $model->viewed_at = date('Y-m-d H:i:s', (int) ($hit['time'] ?? time()));
                $model->insert();
                $recorded++;
            } catch (\Throwable) {
                // One bad line must not stop the processing.
            }
        }

        fclose($handle);
        @unlink($processing);

        return $recorded;
    }

    /**
     * Path of the cache hit log written by PageCacheService.
     *
     * The page cache decides this path, so ask the service for it. The
     * fallback keeps the cron task working if the cache is not mapped.
     */
    private function pageHitsFile(): string
    {
        $pageCache = $this->app->get('pageCache.instance');
        if ($pageCache instanceof \Pubvana\Services\PageCacheService) {
            return $pageCache->hitLogPath();
        }

        $root = defined('PROJECT_ROOT') ? (string) PROJECT_ROOT : dirname(__DIR__, 3);

        return $root . '/writable/cache/pages/' . \Pubvana\Services\PageCacheService::HIT_LOG;
    }

    // -------------------------------------------------------------------------
    // Trend assembly
    // -------------------------------------------------------------------------

    /**
     * @param array<string, array<string, int>> $map group => bucket => count
     * @param array<int, array{page_group:string, bucket:string, view_count:int}> $rows
     */
    private function accumulateTrend(array &$map, array $rows): void
    {
        foreach ($rows as $row) {
            $map[$row['page_group']][$row['bucket']] =
                ($map[$row['page_group']][$row['bucket']] ?? 0) + $row['view_count'];
        }
    }

    /**
     * @param array<string, array<string, int>> $map group => bucket => count
     * @param array<string, int> $bots bucket => count
     * @return array{granularity:string, labels:array<int,string>, series:array<int,array{label:string, values:array<int,int>}>}
     */
    private function assembleTrend(array $map, array $bots, string $range, bool $monthly): array
    {
        $axisSource = $map;
        if ($bots !== []) {
            $axisSource[''] = $bots;
        }

        $buckets = $this->buildAxis($axisSource, $range, $monthly);

        if (empty($buckets)) {
            return [
                'granularity' => $monthly ? 'month' : 'day',
                'labels'      => [],
                'series'      => [],
            ];
        }

        // Order groups by total views so the busiest line renders first.
        $groups = array_keys($map);
        usort($groups, function (string $a, string $b) use ($map): int {
            return array_sum($map[$b]) <=> array_sum($map[$a]);
        });

        $series = [];

        if ($map !== [] || $bots !== []) {
            $visitors = [];
            $robots = [];
            foreach ($buckets as $bucket) {
                $total = 0;
                foreach ($map as $groupBuckets) {
                    $total += $groupBuckets[$bucket] ?? 0;
                }
                $visitors[] = $total;
                $robots[] = $bots[$bucket] ?? 0;
            }

            $series[] = ['label' => 'Visitors', 'values' => $visitors];
            $series[] = ['label' => 'Robots', 'values' => $robots];
        }

        foreach ($groups as $group) {
            $values = [];
            foreach ($buckets as $bucket) {
                $values[] = $map[$group][$bucket] ?? 0;
            }
            $series[] = [
                'label'  => $group,
                'values' => $values,
            ];
        }

        return [
            'granularity' => $monthly ? 'month' : 'day',
            'labels'      => array_map(
                fn(string $bucket) => $monthly ? $this->monthLabel($bucket) : $this->dayLabel($bucket),
                $buckets
            ),
            'series'      => $series,
        ];
    }

    /**
     * @param array<string, array<string, int>> $map
     * @return array<int, string> Sorted bucket keys
     */
    private function buildAxis(array $map, string $range, bool $monthly): array
    {
        if ($monthly) {
            $all = [];
            foreach ($map as $groupBuckets) {
                $all = array_merge($all, array_keys($groupBuckets));
            }
            if (empty($all)) {
                return [];
            }

            $min = min($all);
            $max = max(date('Y-m'), max($all));

            $buckets = [];
            $cursor = $min;
            while ($cursor <= $max) {
                $buckets[] = $cursor;
                $cursor = $this->nextMonth($cursor);
            }

            return $buckets;
        }

        $days = $this->rangeDays($range) ?? $this->hotDays();
        $buckets = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts = strtotime("-{$i} days");
            $buckets[] = $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
        }

        return $buckets;
    }

    // -------------------------------------------------------------------------
    // Report helpers
    // -------------------------------------------------------------------------

    /**
     * Days of raw rows a range reads. Past the hot window the older days
     * come from the daily tables.
     */
    private function rawCutoffDays(string $range): int
    {
        if ($this->usesDaily($range)) {
            return $this->hotDays();
        }

        return $this->rangeDays($range) ?? $this->hotDays();
    }

    /**
     * Sum matching paths from the raw and rolled rows.
     *
     * @param array<int, array{page_path:string, page_group:string, view_count:int}> $raw
     * @param array<int, array{page_path:string, page_group:string, view_count:int}> $daily
     * @return array<int, array{page_path:string, page_group:string, view_count:int}>
     */
    private function mergeTopContent(array $raw, array $daily): array
    {
        $merged = [];

        foreach (array_merge($raw, $daily) as $row) {
            $key = $row['page_path'] . '|' . $row['page_group'];

            if (isset($merged[$key])) {
                $merged[$key]['view_count'] += $row['view_count'];
                continue;
            }

            $merged[$key] = $row;
        }

        return array_values($merged);
    }

    /**
     * Sum matching domains from the raw and rolled rows.
     *
     * @param array<int, array{referrer_domain:string, view_count:int}> $raw
     * @param array<int, array{referrer_domain:string, view_count:int}> $daily
     * @return array<int, array{referrer_domain:string, view_count:int}>
     */
    private function mergeReferrers(array $raw, array $daily): array
    {
        $merged = [];

        foreach (array_merge($raw, $daily) as $row) {
            $domain = $row['referrer_domain'];

            if (isset($merged[$domain])) {
                $merged[$domain]['view_count'] += $row['view_count'];
                continue;
            }

            $merged[$domain] = $row;
        }

        return array_values($merged);
    }

    /**
     * Lower bound for the daily tables in Y-m-d, or null for all-time.
     */
    private function dailyBoundary(string $range): ?string
    {
        $days = $this->rangeDays($range);
        if ($days === null) {
            return null;
        }
        $ts = strtotime("-{$days} days");
        return $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
    }

    private function cutoff(int $days): string
    {
        $ts = strtotime("-{$days} days");
        return $ts === false ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $ts);
    }

    // -------------------------------------------------------------------------
    // Tracking helpers
    // -------------------------------------------------------------------------

    /**
     * Content group derived from the first URL segment.
     */
    public function groupForPath(string $path): string
    {
        $trimmed = ltrim($path, '/');
        if ($trimmed === '') {
            return 'home';
        }

        $segment = (string) strtok($trimmed, '/');
        $segment = strtolower(trim($segment));

        return $segment === '' ? 'home' : $segment;
    }

    private function normalizePath(string $url, string $base): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $path = trim($path);

        if ($path === '') {
            $path = '/';
        }

        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function shouldSkip(string $path): bool
    {
        $trackingConfig = $this->config['tracking'] ?? [];

        $skipPrefixes = $trackingConfig['skip_prefixes'] ?? [];
        foreach ($skipPrefixes as $prefix) {
            $prefix = $this->normalizePath((string) $prefix, '/');
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        $skipPaths = $trackingConfig['skip_paths'] ?? [];
        foreach ($skipPaths as $skipPath) {
            if ($path === (string) $skipPath) {
                return true;
            }
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, self::STATIC_EXTENSIONS, true)) {
            return true;
        }

        return false;
    }

    private function isBot(string $ua): bool
    {
        if ($ua === '') {
            return false;
        }

        $keywords = $this->config['tracking']['bot_keywords'] ?? self::BOT_KEYWORDS;

        foreach ($keywords as $keyword) {
            if (stripos($ua, (string) $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function referrerDomain(string $referer): ?string
    {
        if ($referer === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);

        // An internal click sends this site as the referrer.
        $siteUrl = (string) ($this->app->get('siteUrl') ?? '');
        if ($siteUrl !== '') {
            $self = strtolower((string) parse_url($siteUrl, PHP_URL_HOST));
            if ($this->bareHost($host) === $this->bareHost($self)) {
                return null;
            }
        }

        return $host;
    }

    /**
     * Host without a leading www., so both forms compare equal.
     */
    private function bareHost(string $host): string
    {
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function monthLabel(string $bucket): string
    {
        $ts = strtotime($bucket . '-01');
        return $ts === false ? $bucket : date('M Y', $ts);
    }

    private function dayLabel(string $bucket): string
    {
        $ts = strtotime($bucket);
        return $ts === false ? $bucket : date('M j', $ts);
    }

    private function nextMonth(string $bucket): string
    {
        $ts = strtotime($bucket . '-01 +1 month');
        return $ts === false ? $bucket : date('Y-m', $ts);
    }

    private function rollupFlagPath(): string
    {
        $root = PROJECT_ROOT;
        return $root . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'analytics_rollup';
    }

    /**
     * Truncate a value to the column length, keeping nulls intact.
     */
    private function clip(?string $value, int $length): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return strlen($value) > $length ? substr($value, 0, $length) : $value;
    }
}