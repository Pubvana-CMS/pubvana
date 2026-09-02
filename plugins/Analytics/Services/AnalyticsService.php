<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Services;

use Pubvana\Plugins\Analytics\Models\PageView;
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
    private \PDO $pdo;

    /** @var Engine<object> */
    private Engine $app;

    /** @var array<string, mixed> */
    private array $config;

    private ?bool $trackingEnabled = null;

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
    public function __construct(\PDO $pdo, Engine $app, array $config = [])
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Build the full report dataset for a period.
     *
     * @param string $range One of '7', '30', '90', '180', '365', 'all'
     * @return array<string, mixed>
     */
    public function dashboard(string $range): array
    {
        return [
            'range'      => $this->normalizeRange($range),
            'totalViews' => $this->totalViews($range),
            'trends'     => $this->trend($range),
            'topContent' => $this->topContent($range, 10),
            'referrers'  => $this->referrers($range, 10),
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
            return $this->rawCount($this->cutoff($days ?? $this->hotDays()));
        }

        return $this->rawCount($this->cutoff($this->hotDays()))
            + $this->dailyTotal($this->dailyBoundary($range));
    }

    /**
     * Per-group trend series for the chart, zero-filled across the period.
     *
     * @return array{granularity:string, labels:array<int,string>, series:array<int,array{label:string, values:array<int,int>}>}
     */
    public function trend(string $range): array
    {
        $monthly = $this->rangeDays($range) === null;
        $map = [];

        $this->accumulateTrend($map, $this->rawTrendRows($range, $monthly));
        if ($this->usesDaily($range)) {
            $this->accumulateTrend($map, $this->dailyTrendRows($range, $monthly));
        }

        return $this->assembleTrend($map, $range, $monthly);
    }

    /**
     * Top content by hit count, ranked #1 by views down.
     *
     * @return array<int, array{page_path:string, page_group:string, view_count:int}>
     */
    public function topContent(string $range, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $days = $this->rangeDays($range);

        if (!$this->usesDaily($range)) {
            $stmt = $this->pdo->prepare(
                'SELECT page_path, page_group, COUNT(*) AS view_count
                 FROM analytics_page_views
                 WHERE viewed_at >= :cutoff
                 GROUP BY page_path, page_group
                 ORDER BY view_count DESC, page_path ASC
                 LIMIT ' . $limit
            );
            $stmt->bindValue(':cutoff', $this->cutoff($days ?? $this->hotDays()));
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT page_path, page_group, SUM(view_count) AS view_count
                 FROM (
                     SELECT page_path, page_group, COUNT(*) AS view_count
                     FROM analytics_page_views
                     WHERE viewed_at >= :cutoff
                     GROUP BY page_path, page_group
                     UNION ALL
                     SELECT page_path, page_group, view_count
                     FROM analytics_views_daily' . $this->dailyWhere($range) . '
                 ) t
                 GROUP BY page_path, page_group
                 ORDER BY view_count DESC, page_path ASC
                 LIMIT ' . $limit
            );
            $stmt->bindValue(':cutoff', $this->cutoff($this->hotDays()));
            $this->bindDailyBoundary($stmt, $range);
        }

        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'page_path'  => (string) $row['page_path'],
                'page_group' => (string) $row['page_group'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $rows;
    }

    /**
     * Top referrer domains in the given period.
     *
     * @return array<int, array{referrer_domain:string, view_count:int}>
     */
    public function referrers(string $range, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $days = $this->rangeDays($range);

        if (!$this->usesDaily($range)) {
            $stmt = $this->pdo->prepare(
                'SELECT referrer_domain, COUNT(*) AS view_count
                 FROM analytics_page_views
                 WHERE viewed_at >= :cutoff
                   AND referrer_domain IS NOT NULL
                   AND referrer_domain <> \'\'
                 GROUP BY referrer_domain
                 ORDER BY view_count DESC, referrer_domain ASC
                 LIMIT ' . $limit
            );
            $stmt->bindValue(':cutoff', $this->cutoff($days ?? $this->hotDays()));
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT referrer_domain, SUM(view_count) AS view_count
                 FROM (
                     SELECT referrer_domain, COUNT(*) AS view_count
                     FROM analytics_page_views
                     WHERE viewed_at >= :cutoff
                       AND referrer_domain IS NOT NULL
                       AND referrer_domain <> \'\'
                     GROUP BY referrer_domain
                     UNION ALL
                     SELECT referrer_domain, view_count
                     FROM analytics_referrers_daily' . $this->dailyWhere($range) . '
                 ) t
                 GROUP BY referrer_domain
                 ORDER BY view_count DESC, referrer_domain ASC
                 LIMIT ' . $limit
            );
            $stmt->bindValue(':cutoff', $this->cutoff($this->hotDays()));
            $this->bindDailyBoundary($stmt, $range);
        }

        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'referrer_domain' => (string) $row['referrer_domain'],
                'view_count'      => (int) $row['view_count'],
            ];
        }

        return $rows;
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
        $cutoff = $this->cutoff($this->hotDays());

        $views = $this->pdo->prepare(
            'INSERT INTO analytics_views_daily (day, page_group, page_path, view_count)
             SELECT DATE(viewed_at), page_group, page_path, COUNT(*)
             FROM analytics_page_views
             WHERE viewed_at < :cutoff
             GROUP BY DATE(viewed_at), page_group, page_path
             ON DUPLICATE KEY UPDATE view_count = analytics_views_daily.view_count + VALUES(view_count)'
        );
        $views->bindValue(':cutoff', $cutoff);
        $views->execute();

        $referrers = $this->pdo->prepare(
            'INSERT INTO analytics_referrers_daily (day, referrer_domain, view_count)
             SELECT DATE(viewed_at), referrer_domain, COUNT(*)
             FROM analytics_page_views
             WHERE viewed_at < :cutoff
               AND referrer_domain IS NOT NULL
               AND referrer_domain <> \'\'
             GROUP BY DATE(viewed_at), referrer_domain
             ON DUPLICATE KEY UPDATE view_count = analytics_referrers_daily.view_count + VALUES(view_count)'
        );
        $referrers->bindValue(':cutoff', $cutoff);
        $referrers->execute();

        $removed = 0;
        $delete = $this->pdo->prepare(
            'DELETE FROM analytics_page_views WHERE viewed_at < :cutoff LIMIT 5000'
        );
        $delete->bindValue(':cutoff', $cutoff);

        do {
            $delete->execute();
            $count = $delete->rowCount();
            $removed += $count;
        } while ($count > 0);

        return $removed;
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
            // Never break page delivery on a rollup failure.
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Initial server-side tracking toggle resolved once per request cycle.
     */
    public function isTrackingEnabled(): bool
    {
        if ($this->trackingEnabled === null) {
            $this->trackingEnabled = (bool) $this->app->settings()->get('Analytics.tracking_enabled', true);
        }

        return $this->trackingEnabled;
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
        if (php_sapi_name() === 'cli') {
            return;
        }

        if (!$this->isTrackingEnabled()) {
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

        try {
            $model = new PageView($this->pdo);
            $model->page_path = $this->clip($path, 255) ?? '';
            $model->page_group = $this->clip($this->groupForPath($path), 50) ?? '';
            $model->referrer_domain = $this->clip($this->referrerDomain((string) ($_SERVER['HTTP_REFERER'] ?? '')), 255);
            $model->viewed_at = $this->now();
            $model->insert();
        } catch (\Throwable $e) {
            // Never break page delivery due to analytics failure.
        }
    }

    // -------------------------------------------------------------------------
    // Trend assembly
    // -------------------------------------------------------------------------

    /**
     * Raw group/bucket rows for the recent (hot) window.
     *
     * @return array<int, array{page_group:string, bucket:string, view_count:int}>
     */
    private function rawTrendRows(string $range, bool $monthly): array
    {
        $bucketExpr = $monthly ? "DATE_FORMAT(viewed_at, '%Y-%m')" : 'DATE(viewed_at)';
        $stmt = $this->pdo->prepare(
            "SELECT page_group, {$bucketExpr} AS bucket, COUNT(*) AS view_count
             FROM analytics_page_views
             WHERE viewed_at >= :cutoff
             GROUP BY page_group, bucket
             ORDER BY bucket ASC"
        );
        $stmt->bindValue(':cutoff', $this->cutoff($this->rangeDays($range) ?? $this->hotDays()));
        $stmt->execute();

        return $this->trendRows($stmt);
    }

    /**
     * Rolled group/bucket rows for the historical window.
     *
     * @return array<int, array{page_group:string, bucket:string, view_count:int}>
     */
    private function dailyTrendRows(string $range, bool $monthly): array
    {
        $bucketExpr = $monthly ? 'SUBSTRING(day, 1, 7)' : 'day';
        $sql = "SELECT page_group, {$bucketExpr} AS bucket, SUM(view_count) AS view_count
                FROM analytics_views_daily" . $this->dailyWhere($range) . '
                GROUP BY page_group, bucket
                ORDER BY bucket ASC';

        $stmt = $this->pdo->prepare($sql);
        $this->bindDailyBoundary($stmt, $range);
        $stmt->execute();

        return $this->trendRows($stmt);
    }

    /**
     * @return array<int, array{page_group:string, bucket:string, view_count:int}>
     */
    private function trendRows(\PDOStatement $stmt): array
    {
        $rows = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'page_group' => (string) $row['page_group'],
                'bucket'     => (string) $row['bucket'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $rows;
    }

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
     * @return array{granularity:string, labels:array<int,string>, series:array<int,array{label:string, values:array<int,int>}>}
     */
    private function assembleTrend(array $map, string $range, bool $monthly): array
    {
        $buckets = $this->buildAxis($map, $range, $monthly);

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
    // SQL helpers
    // -------------------------------------------------------------------------

    /**
     * Count raw rows at or after a datetime cutoff.
     */
    private function rawCount(string $cutoff): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM analytics_page_views WHERE viewed_at >= :cutoff'
        );
        $stmt->bindValue(':cutoff', $cutoff);
        $stmt->execute();

        return (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    /**
     * Sum daily view counts, optionally bounded below.
     */
    private function dailyTotal(?string $boundary): int
    {
        $sql = 'SELECT SUM(view_count) AS total FROM analytics_views_daily';
        if ($boundary !== null) {
            $sql .= ' WHERE day >= :day';
        }

        $stmt = $this->pdo->prepare($sql);
        if ($boundary !== null) {
            $stmt->bindValue(':day', $boundary);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);
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

    /**
     * WHERE clause for the daily tables (leading space), or empty for all-time.
     */
    private function dailyWhere(string $range): string
    {
        $boundary = $this->dailyBoundary($range);
        return $boundary === null ? '' : ' WHERE day >= :day';
    }

    /**
     * Bind :day when a daily boundary applies.
     */
    private function bindDailyBoundary(\PDOStatement $stmt, string $range): void
    {
        $boundary = $this->dailyBoundary($range);
        if ($boundary !== null) {
            $stmt->bindValue(':day', $boundary);
        }
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

        return strtolower($host);
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
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3);
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