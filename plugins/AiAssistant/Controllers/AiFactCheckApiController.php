<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

use Pubvana\Plugins\AiAssistant\Models\AiKey;

/**
 * AiFactCheckApiController - /api/ai/fact-check/* endpoints.
 *
 * Handles the site-level fact checking API: the versioned prompt,
 * reading reports, and submitting reports for posts and pages. These
 * endpoints open to any authenticated key when the admin accepts the
 * current prompt's terms and enables the service. The prompt endpoint
 * is the exception, readable even while the service is off.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiFactCheckApiController extends AiApiController
{
    /**
     * Serve the current fact-checking prompt: versioned terms and
     * instructions. Reachable to any authenticated key, even while the
     * service is off, so an assistant can always read the terms it would
     * be bound by.
     */
    public function factCheckPrompt(): void
    {
        $key = $this->requireKey();

        $factCheck = $this->app->aiFactCheck();
        $prompt = $factCheck->currentPrompt();

        $this->log($key, 'ok', 'fact_check', null, "Fetched fact-checking prompt v{$prompt['version']}.");
        $this->ok([
            'prompt'                => $prompt,
            'fact_checking_enabled' => $factCheck->isEnabled(),
            'terms_current'         => $factCheck->termsCurrent(),
            'note'                  => 'Fetch this prompt before every fact check and follow its terms. Submissions must attest to this prompt version.',
        ]);
    }

    /**
     * List stored fact-check reports, newest first.
     */
    public function factChecks(): void
    {
        $key = $this->requireKey();
        $this->requireFactCheckGate($key);

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));

        $contentType = $query->content_type ?? null;
        if ($contentType !== null && !in_array((string) $contentType, ['post', 'page'], true)) {
            $this->log($key, 'error', 'fact_check', null, "Invalid content_type '{$contentType}'.");
            $this->fail(422, 'content_type filter must be one of: post, page.');
        }

        $contentId = $query->content_id ?? null;
        if ($contentId !== null && (int) $contentId <= 0) {
            $this->log($key, 'error', 'fact_check', null, "Invalid content_id '{$contentId}'.");
            $this->fail(422, 'content_id filter must be a positive id.');
        }

        $result = $this->app->aiFactCheck()->listReports(
            $page,
            $perPage,
            $contentType !== null ? (string) $contentType : null,
            $contentId !== null ? (int) $contentId : null
        );

        $detail = 'Listed fact-check reports';
        if ($contentType !== null) {
            $detail .= " (content_type: {$contentType})";
        }
        if ($contentId !== null) {
            $detail .= " (content_id: {$contentId})";
        }
        $this->log($key, 'ok', 'fact_check', null, $detail . '.');
        $this->ok($result);
    }

    /**
     * Fetch one stored fact-check report.
     */
    public function factCheck(string $id): void
    {
        $key = $this->requireKey();
        $this->requireFactCheckGate($key);

        $report = $this->app->aiFactCheck()->findReport((int) $id);
        if ($report === null) {
            $this->log($key, 'error', 'fact_check', (int) $id, 'Fact-check report not found.');
            $this->fail(404, 'Fact-check report not found.');
        }

        $this->log($key, 'ok', 'fact_check', (int) $report->id, "Fetched fact-check report #{$report->id}.");
        $this->ok($this->app->aiFactCheck()->serializeReport($report));
    }

    /**
     * Submit a fact-check report for a post. Requires the posts.read
     * grant (the checker must be able to pull the article through the
     * API) and the site-level fact-checking gate.
     */
    public function submitPostFactCheck(string $id): void
    {
        $this->submitFactCheck('post', (int) $id);
    }

    /**
     * Submit a fact-check report for a page.
     */
    public function submitPageFactCheck(string $id): void
    {
        $this->submitFactCheck('page', (int) $id);
    }

    /**
     * Shared submit path for post and page fact-check reports.
     *
     * @param string $contentType 'post' or 'page'
     */
    protected function submitFactCheck(string $contentType, int $contentId): void
    {
        $key = $this->requireKey();

        // The checker must be able to pull the article through the API:
        // a key that cannot read content has no business writing reports
        // about it.
        $this->requireGrant($key, $contentType === 'page' ? 'pages.read' : 'posts.read');

        $this->requireFactCheckGate($key);

        $result = $this->app->aiFactCheck()->submitReport($contentType, $contentId, $this->payload(), $key);
        if (!$result['ok'] || !($result['report'] instanceof \Pubvana\Plugins\AiAssistant\Models\AiFactCheck)) {
            $this->log($key, 'error', 'fact_check', $contentId, $result['error']);
            $this->fail($result['code'], $result['error']);
        }

        $report = $result['report'];
        $this->log(
            $key,
            'ok',
            'fact_check',
            $contentId,
            "Submitted fact check for {$contentType} #{$contentId} "
            . "(overall: {$report->overall_verdict}, claims: {$report->claim_count}, prompt v{$report->prompt_version})"
            . ($report->prompt_interference === 1 ? ' [interference flagged]' : '') . '.'
        );
        $this->ok($this->app->aiFactCheck()->serializeReport($report));
    }

    /**
     * Site-level fact-checking gate. The toggle is the grant.
     *
     * Runs before every fact-check endpoint except the prompt endpoint.
     */
    protected function requireFactCheckGate(AiKey $key): void
    {
        $gate = $this->app->aiFactCheck()->gateStatus();
        if (!$gate['ok']) {
            $this->log($key, 'denied', 'fact_check', null, $gate['message']);
            $this->fail($gate['code'], $gate['message']);
        }
    }
}