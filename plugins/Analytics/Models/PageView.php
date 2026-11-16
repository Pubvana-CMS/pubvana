<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Models;

/**
 * PageView - A single recorded page view event.
 *
 * @property int         $id
 * @property string      $page_path
 * @property string      $page_group
 * @property string|null $referrer_domain
 * @property int         $is_bot
 * @property string      $viewed_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 */
class PageView extends AnalyticsModel
{
    /** Cached result of the server-version probe for the row-alias upsert. */
    private ?bool $rowAliasSupport = null;

    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'analytics_page_views', $config);
    }

    /**
     * Find a page view event by ID.
     *
     * @param int $id
     * @return self|null
     */
    public function findById(int $id): ?self
    {
        // Fresh instance: a reused $this would alias every find to the same object.
        $query = new self($this->getDatabaseConnection());
        $query->eq('id', $id)->find();
        return $query->isHydrated() ? $query : null;
    }

    /**
     * A fresh unsaved row on the same connection.
     */
    public function newRow(): self
    {
        return new self($this->getDatabaseConnection());
    }

    /**
     * Visitor views at or after the cutoff.
     */
    public function visitorCountSince(string $cutoff): int
    {
        return $this->total(
            'SELECT COUNT(*) FROM analytics_page_views WHERE viewed_at >= :cutoff AND is_bot = 0',
            [':cutoff' => $cutoff]
        );
    }

    /**
     * Visitor views in [start, end).
     */
    public function visitorCountBetween(string $start, string $end): int
    {
        return $this->total(
            'SELECT COUNT(*) FROM analytics_page_views'
            . ' WHERE viewed_at >= :start AND viewed_at < :end AND is_bot = 0',
            [':start' => $start, ':end' => $end]
        );
    }

    /**
     * Top paths at or after the cutoff.
     *
     * @return array<int, array{page_path:string, page_group:string, view_count:int}>
     */
    public function topContentSince(string $cutoff, int $limit): array
    {
        $rows = $this->fetchRows(
            'SELECT page_path, page_group, COUNT(*) AS view_count'
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at >= :cutoff AND is_bot = 0'
            . ' GROUP BY page_path, page_group'
            . ' ORDER BY view_count DESC, page_path ASC'
            . ' LIMIT ' . max(1, $limit),
            [':cutoff' => $cutoff]
        );

        $content = [];
        foreach ($rows as $row) {
            $content[] = [
                'page_path'  => (string) $row['page_path'],
                'page_group' => (string) $row['page_group'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $content;
    }

    /**
     * Referrer rows at or after the cutoff. A view with no referrer keeps an
     * empty domain so the report can label it Direct.
     *
     * @return array<int, array{referrer_domain:string, view_count:int}>
     */
    public function referrerRowsSince(string $cutoff, int $limit): array
    {
        $rows = $this->fetchRows(
            "SELECT COALESCE(referrer_domain, '') AS referrer_domain, COUNT(*) AS view_count"
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at >= :cutoff AND is_bot = 0'
            . " GROUP BY COALESCE(referrer_domain, '')"
            . ' ORDER BY view_count DESC, referrer_domain ASC'
            . ' LIMIT ' . max(1, $limit),
            [':cutoff' => $cutoff]
        );

        $referrers = [];
        foreach ($rows as $row) {
            $referrers[] = [
                'referrer_domain' => (string) $row['referrer_domain'],
                'view_count'      => (int) $row['view_count'],
            ];
        }

        return $referrers;
    }

    /**
     * Visitor group rows per time bucket at or after the cutoff.
     *
     * @return array<int, array{page_group:string, bucket:string, view_count:int}>
     */
    public function groupTrendSince(string $cutoff, bool $monthly): array
    {
        $bucket = $monthly ? "DATE_FORMAT(viewed_at, '%Y-%m')" : 'DATE(viewed_at)';
        $rows = $this->fetchRows(
            "SELECT page_group, {$bucket} AS bucket, COUNT(*) AS view_count"
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at >= :cutoff AND is_bot = 0'
            . ' GROUP BY page_group, bucket'
            . ' ORDER BY bucket ASC',
            [':cutoff' => $cutoff]
        );

        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                'page_group' => (string) $row['page_group'],
                'bucket'     => (string) $row['bucket'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $groups;
    }

    /**
     * Bot rows per time bucket at or after the cutoff.
     *
     * @return array<int, array{bucket:string, view_count:int}>
     */
    public function botTrendSince(string $cutoff, bool $monthly): array
    {
        $bucket = $monthly ? "DATE_FORMAT(viewed_at, '%Y-%m')" : 'DATE(viewed_at)';
        $rows = $this->fetchRows(
            "SELECT {$bucket} AS bucket, COUNT(*) AS view_count"
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at >= :cutoff AND is_bot = 1'
            . ' GROUP BY bucket'
            . ' ORDER BY bucket ASC',
            [':cutoff' => $cutoff]
        );

        $bots = [];
        foreach ($rows as $row) {
            $bots[] = [
                'bucket'     => (string) $row['bucket'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $bots;
    }

    /**
     * One page of the full top content list, raw and rolled rows merged.
     *
     * @return array<int, array{page_path:string, page_group:string, view_count:int}>
     */
    public function topContentPage(string $rawCutoff, ?string $dailyFrom, int $limit, int $offset): array
    {
        $sql = 'SELECT page_path, page_group, SUM(view_count) AS view_count FROM ('
            . ' SELECT page_path, page_group, COUNT(*) AS view_count'
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at >= :cutoff AND is_bot = 0'
            . ' GROUP BY page_path, page_group';

        $params = [':cutoff' => $rawCutoff];

        if ($dailyFrom !== null) {
            $sql .= ' UNION ALL'
                . ' SELECT page_path, page_group, SUM(view_count) AS view_count'
                . ' FROM analytics_views_daily'
                . ' WHERE day >= :day'
                . ' GROUP BY page_path, page_group';
            $params[':day'] = $dailyFrom;
        }

        $sql .= ') t GROUP BY page_path, page_group'
            . ' ORDER BY view_count DESC, page_path ASC'
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        $content = [];
        foreach ($this->fetchRows($sql, $params) as $row) {
            $content[] = [
                'page_path'  => (string) $row['page_path'],
                'page_group' => (string) $row['page_group'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $content;
    }

    /**
     * Number of distinct paths in the full top content list.
     */
    public function topContentCount(string $rawCutoff, ?string $dailyFrom): int
    {
        $sql = 'SELECT COUNT(*) FROM ('
            . ' SELECT page_path, page_group FROM analytics_page_views'
            . ' WHERE viewed_at >= :cutoff AND is_bot = 0'
            . ' GROUP BY page_path, page_group';

        $params = [':cutoff' => $rawCutoff];

        if ($dailyFrom !== null) {
            $sql .= ' UNION ALL'
                . ' SELECT page_path, page_group FROM analytics_views_daily'
                . ' WHERE day >= :day'
                . ' GROUP BY page_path, page_group';
            $params[':day'] = $dailyFrom;
        }

        return $this->total($sql . ') t', $params);
    }

    /**
     * One page of the full referrer list, raw and rolled rows merged.
     *
     * @return array<int, array{referrer_domain:string, view_count:int}>
     */
    public function referrerPage(string $rawCutoff, ?string $dailyFrom, int $limit, int $offset): array
    {
        $sql = "SELECT referrer_domain, SUM(view_count) AS view_count FROM ("
            . " SELECT COALESCE(referrer_domain, '') AS referrer_domain, COUNT(*) AS view_count"
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at >= :cutoff AND is_bot = 0'
            . " GROUP BY COALESCE(referrer_domain, '')";

        $params = [':cutoff' => $rawCutoff];

        if ($dailyFrom !== null) {
            $sql .= ' UNION ALL'
                . ' SELECT referrer_domain, SUM(view_count) AS view_count'
                . ' FROM analytics_referrers_daily'
                . ' WHERE day >= :day'
                . ' GROUP BY referrer_domain';
            $params[':day'] = $dailyFrom;
        }

        $sql .= ') t GROUP BY referrer_domain'
            . ' ORDER BY view_count DESC, referrer_domain ASC'
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        $referrers = [];
        foreach ($this->fetchRows($sql, $params) as $row) {
            $referrers[] = [
                'referrer_domain' => (string) $row['referrer_domain'],
                'view_count'      => (int) $row['view_count'],
            ];
        }

        return $referrers;
    }

    /**
     * Number of distinct referrer domains in the full list.
     */
    public function referrerCount(string $rawCutoff, ?string $dailyFrom): int
    {
        $sql = "SELECT COUNT(*) FROM ("
            . " SELECT COALESCE(referrer_domain, '') AS referrer_domain FROM analytics_page_views"
            . ' WHERE viewed_at >= :cutoff AND is_bot = 0'
            . " GROUP BY COALESCE(referrer_domain, '')";

        $params = [':cutoff' => $rawCutoff];

        if ($dailyFrom !== null) {
            $sql .= ' UNION ALL'
                . ' SELECT referrer_domain FROM analytics_referrers_daily'
                . ' WHERE day >= :day'
                . ' GROUP BY referrer_domain';
            $params[':day'] = $dailyFrom;
        }

        return $this->total($sql . ') t', $params);
    }

    /**
     * Delete raw rows older than the cutoff, in batches.
     */
    public function deleteOlderThan(string $cutoff, int $limit = 5000): int
    {
        return $this->run(
            'DELETE FROM analytics_page_views WHERE viewed_at < :cutoff LIMIT ' . max(1, $limit),
            [':cutoff' => $cutoff]
        );
    }

    /**
     * Run the rollup for rows older than the cutoff on one transaction.
     *
     * @return int Number of raw rows removed
     */
    public function rollupOlderThan(
        string $cutoff,
        ViewsDaily $views,
        ReferrersDaily $referrers,
        BotsDaily $bots
    ): int {
        $removed = 0;
        $rowAlias = $this->supportsRowAlias();

        // The three merges and the delete commit together. A merge that
        // committed without the delete would leave the raw rows in place,
        // and the next run would add their counts again.
        $this->runInTransaction(function () use ($cutoff, $rowAlias, $views, $referrers, $bots, &$removed): void {
            $views->upsertFromPageViews($cutoff, $rowAlias);
            $referrers->upsertFromPageViews($cutoff, $rowAlias);
            $bots->upsertFromPageViews($cutoff, $rowAlias);

            $deleted = 0;
            do {
                $deleted = $this->deleteOlderThan($cutoff);
                $removed += $deleted;
            } while ($deleted > 0);
        });

        return $removed;
    }

    /**
     * Whether the server accepts the INSERT ... SELECT row-alias form that
     * replaces the deprecated VALUES() upsert (MySQL 8.0.19+; MariaDB does
     * not support it). Cached after the first server version probe.
     */
    public function supportsRowAlias(): bool
    {
        if ($this->rowAliasSupport === null) {
            $version = (string) $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $isMariaDb = stripos($version, 'mariadb') !== false;
            $clean = (string) preg_replace('/[^0-9.].*/', '', $version);
            $this->rowAliasSupport = !$isMariaDb && version_compare($clean, '8.0.19', '>=');
        }

        return $this->rowAliasSupport;
    }
}