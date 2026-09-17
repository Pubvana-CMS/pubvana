<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

/**
 * AiAnalyticsApiController - /api/ai/analytics/* endpoints.
 *
 * Returns the Analytics dashboard traffic report for a period.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiAnalyticsApiController extends AiApiController
{
    /**
     * Site traffic report for a period.
     *
     * `range` is one of 7, 30, 90, 180, 365, all. The response mirrors the
     * Analytics dashboard payload: range, totalViews, trends, topContent,
     * referrers.
     */
    public function analytics(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'analytics.read');

        $range = (string) ($this->app->request()->query->range ?? '30');
        $report = $this->svc('analytics')->dashboard($range);

        $this->log($key, 'ok', 'analytics', null, "Analytics report for range {$report['range']}.");
        $this->ok($report);
    }
}