<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Models;

/**
 * ViewsDaily - One rolled-up day of views for a single path.
 *
 * The rollup moves rows here once they are older than the hot window, so a
 * report covering older history reads this table instead.
 *
 * @property int    $id
 * @property string $day
 * @property string $page_group
 * @property string $page_path
 * @property int    $view_count
 */
class ViewsDaily extends AnalyticsModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'analytics_views_daily', $config);
    }

    /**
     * Total visitor views for rolled days at or after the boundary.
     */
    public function sumSince(?string $fromDay): int
    {
        if ($fromDay === null) {
            return $this->total('SELECT SUM(view_count) FROM analytics_views_daily');
        }

        return $this->total(
            'SELECT SUM(view_count) FROM analytics_views_daily WHERE day >= :day',
            [':day' => $fromDay]
        );
    }

    /**
     * Total visitor views for rolled days in [fromDay, toDay).
     */
    public function sumBetween(string $fromDay, string $toDay): int
    {
        return $this->total(
            'SELECT SUM(view_count) FROM analytics_views_daily WHERE day >= :from AND day < :to',
            [':from' => $fromDay, ':to' => $toDay]
        );
    }

    /**
     * Top paths for rolled days at or after the boundary.
     *
     * @return array<int, array{page_path:string, page_group:string, view_count:int}>
     */
    public function topContent(?string $fromDay, int $limit): array
    {
        $sql = 'SELECT page_path, page_group, SUM(view_count) AS view_count FROM analytics_views_daily';
        $params = [];

        if ($fromDay !== null) {
            $sql .= ' WHERE day >= :day';
            $params[':day'] = $fromDay;
        }

        $sql .= ' GROUP BY page_path, page_group'
            . ' ORDER BY view_count DESC, page_path ASC'
            . ' LIMIT ' . max(1, $limit);

        $rows = [];
        foreach ($this->fetchRows($sql, $params) as $row) {
            $rows[] = [
                'page_path'  => (string) $row['page_path'],
                'page_group' => (string) $row['page_group'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $rows;
    }

    /**
     * Group rows per time bucket for rolled days at or after the boundary.
     *
     * @return array<int, array{page_group:string, bucket:string, view_count:int}>
     */
    public function groupTrend(?string $fromDay, bool $monthly): array
    {
        $bucket = $monthly ? 'SUBSTRING(day, 1, 7)' : 'day';
        $sql = "SELECT page_group, {$bucket} AS bucket, SUM(view_count) AS view_count"
            . ' FROM analytics_views_daily';
        $params = [];

        if ($fromDay !== null) {
            $sql .= ' WHERE day >= :day';
            $params[':day'] = $fromDay;
        }

        $sql .= ' GROUP BY page_group, bucket ORDER BY bucket ASC';

        $rows = [];
        foreach ($this->fetchRows($sql, $params) as $row) {
            $rows[] = [
                'page_group' => (string) $row['page_group'],
                'bucket'     => (string) $row['bucket'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $rows;
    }

    /**
     * Merge raw visitor rows older than the cutoff into this table.
     */
    public function upsertFromPageViews(string $cutoff, bool $rowAlias): void
    {
        $this->run($this->upsertSql($rowAlias), [':cutoff' => $cutoff]);
    }

    /**
     * The merge statement. MySQL 8.0.19+ takes the row-alias form (VALUES()
     * in ON DUPLICATE KEY UPDATE is deprecated there); MySQL below that and
     * MariaDB still need the VALUES() form.
     */
    public function upsertSql(bool $rowAlias): string
    {
        if ($rowAlias) {
            return 'INSERT INTO analytics_views_daily (day, page_group, page_path, view_count)'
                . ' SELECT * FROM ('
                . ' SELECT DATE(viewed_at) AS day, page_group, page_path, COUNT(*) AS view_count'
                . ' FROM analytics_page_views'
                . ' WHERE viewed_at < :cutoff AND is_bot = 0'
                . ' GROUP BY DATE(viewed_at), page_group, page_path'
                . ' ) AS new_row'
                . ' ON DUPLICATE KEY UPDATE view_count = analytics_views_daily.view_count + new_row.view_count';
        }

        return 'INSERT INTO analytics_views_daily (day, page_group, page_path, view_count)'
            . ' SELECT DATE(viewed_at), page_group, page_path, COUNT(*)'
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at < :cutoff AND is_bot = 0'
            . ' GROUP BY DATE(viewed_at), page_group, page_path'
            . ' ON DUPLICATE KEY UPDATE view_count = analytics_views_daily.view_count + VALUES(view_count)';
    }
}
