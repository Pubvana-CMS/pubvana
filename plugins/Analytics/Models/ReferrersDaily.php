<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Models;

/**
 * ReferrersDaily - One rolled-up day of views per referrer domain.
 *
 * A view with no referrer is stored with an empty domain, which the report
 * shows as Direct.
 *
 * @property int    $id
 * @property string $day
 * @property string $referrer_domain
 * @property int    $view_count
 */
class ReferrersDaily extends AnalyticsModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'analytics_referrers_daily', $config);
    }

    /**
     * Referrer rows for rolled days at or after the boundary.
     *
     * @return array<int, array{referrer_domain:string, view_count:int}>
     */
    public function rows(?string $fromDay, int $limit): array
    {
        $sql = 'SELECT referrer_domain, SUM(view_count) AS view_count FROM analytics_referrers_daily';
        $params = [];

        if ($fromDay !== null) {
            $sql .= ' WHERE day >= :day';
            $params[':day'] = $fromDay;
        }

        $sql .= ' GROUP BY referrer_domain'
            . ' ORDER BY view_count DESC, referrer_domain ASC'
            . ' LIMIT ' . max(1, $limit);

        $rows = [];
        foreach ($this->fetchRows($sql, $params) as $row) {
            $rows[] = [
                'referrer_domain' => (string) $row['referrer_domain'],
                'view_count'      => (int) $row['view_count'],
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
     * The merge statement, same version split as the views merge.
     */
    public function upsertSql(bool $rowAlias): string
    {
        if ($rowAlias) {
            return 'INSERT INTO analytics_referrers_daily (day, referrer_domain, view_count)'
                . ' SELECT * FROM ('
                . " SELECT DATE(viewed_at) AS day, COALESCE(referrer_domain, '') AS referrer_domain, COUNT(*) AS view_count"
                . ' FROM analytics_page_views'
                . ' WHERE viewed_at < :cutoff AND is_bot = 0'
                . " GROUP BY DATE(viewed_at), COALESCE(referrer_domain, '')"
                . ' ) AS new_row'
                . ' ON DUPLICATE KEY UPDATE view_count = analytics_referrers_daily.view_count + new_row.view_count';
        }

        return 'INSERT INTO analytics_referrers_daily (day, referrer_domain, view_count)'
            . " SELECT DATE(viewed_at), COALESCE(referrer_domain, ''), COUNT(*)"
            . ' FROM analytics_page_views'
            . ' WHERE viewed_at < :cutoff AND is_bot = 0'
            . " GROUP BY DATE(viewed_at), COALESCE(referrer_domain, '')"
            . ' ON DUPLICATE KEY UPDATE view_count = analytics_referrers_daily.view_count + VALUES(view_count)';
    }
}
