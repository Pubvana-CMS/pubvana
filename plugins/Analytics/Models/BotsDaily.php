<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Models;

/**
 * BotsDaily - One rolled-up day of bot hits.
 *
 * Bot hits go in their own table, so no visitor total counts them.
 *
 * @property int    $id
 * @property string $day
 * @property int    $view_count
 */
class BotsDaily extends AnalyticsModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'analytics_bots_daily', $config);
    }

    /**
     * Bot hits per time bucket for rolled days at or after the boundary.
     *
     * @return array<int, array{bucket:string, view_count:int}>
     */
    public function trend(?string $fromDay, bool $monthly): array
    {
        $bucket = $monthly ? 'SUBSTRING(day, 1, 7)' : 'day';
        $sql = "SELECT {$bucket} AS bucket, SUM(view_count) AS view_count FROM analytics_bots_daily";
        $params = [];

        if ($fromDay !== null) {
            $sql .= ' WHERE day >= :day';
            $params[':day'] = $fromDay;
        }

        $sql .= ' GROUP BY bucket ORDER BY bucket ASC';

        $rows = [];
        foreach ($this->fetchRows($sql, $params) as $row) {
            $rows[] = [
                'bucket'     => (string) $row['bucket'],
                'view_count' => (int) $row['view_count'],
            ];
        }

        return $rows;
    }

    /**
     * Merge raw bot rows older than the cutoff into this table.
     */
    public function upsertFromPageViews(string $cutoff, bool $rowAlias): void
    {
        $this->run($this->upsertSql($rowAlias), [':cutoff' => $cutoff]);
    }

    /**
     * The merge statement, same version split as the views merge.
     */
    public function upsertSql(bool $rowAlias): string
    {
        if ($rowAlias) {
            return 'INSERT INTO analytics_bots_daily (day, view_count)'
                . ' SELECT * FROM ('
                . ' SELECT DATE(viewed_at) AS day, COUNT(*) AS view_count'
                . ' FROM analytics_page_views'
                . ' WHERE viewed_at < :cutoff AND is_bot = 1'
                . ' GROUP BY DATE(viewed_at)'
                . ' ) AS new_row'
                . ' ON DUPLICATE KEY UPDATE view_count = analytics_bots_daily.view_count + new_row.view_count';
        }

        return 'INSERT INTO analytics_bots_daily (day, view_count)'
            . ' SELECT DATE(viewed_at), COUNT(*)'
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at < :cutoff AND is_bot = 1'
            . ' GROUP BY DATE(viewed_at)'
            . ' ON DUPLICATE KEY UPDATE view_count = analytics_bots_daily.view_count + VALUES(view_count)';
    }
}
