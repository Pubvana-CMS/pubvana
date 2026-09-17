<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

/**
 * AiBrokenLinksApiController - /api/ai/broken-links/* endpoints.
 *
 * Lists, scans, rechecks, and dismisses broken link entries through the
 * BrokenLinks plugin. Scanning is the only way new links are discovered.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiBrokenLinksApiController extends AiApiController
{
    /**
     * List broken link results, grouped by the source they appear in.
     *
     * Pass `dismissed=1` to include permanently dismissed entries.
     */
    public function brokenLinks(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'brokenlinks.read');

        $showDismissed = (bool) filter_var(
            $this->app->request()->query->dismissed ?? false,
            FILTER_VALIDATE_BOOL
        );

        $service = $this->svc('brokenLinks');

        $sources = [];
        foreach ($service->all($showDismissed) as $group) {
            $links = [];
            foreach ($group['links'] as $link) {
                $links[] = $this->app->ai()->serializeBrokenLink($link);
            }
            $sources[] = [
                'source_type'  => (string) $group['source_type'],
                'source_id'    => (int) $group['source_id'],
                'source_title' => (string) $group['source_title'],
                'links'        => $links,
            ];
        }

        $this->log(
            $key,
            'ok',
            'broken_link',
            null,
            'Listed broken links' . ($showDismissed ? ' (including dismissed).' : '.')
        );
        $this->ok([
            'total'     => $service->countBroken(),
            'dismissed' => $showDismissed,
            'sources'   => $sources,
        ]);
    }

    /**
     * Run a full scan of every registered content source.
     */
    public function scanBrokenLinks(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'brokenlinks.scan');

        $result = $this->svc('brokenLinks')->scan();

        $this->log(
            $key,
            'ok',
            'broken_link',
            null,
            "Scanned broken links: {$result['total']} checked, {$result['broken']} broken."
        );
        $this->ok($result);
    }

    /**
     * Recheck a single known broken link entry.
     */
    public function recheckBrokenLink(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'brokenlinks.recheck');

        $result = $this->svc('brokenLinks')->recheck((int) $id);

        if ($result['error'] === 'Entry not found.') {
            $this->log($key, 'error', 'broken_link', (int) $id, 'Entry not found.');
            $this->fail(404, 'Broken link entry not found.');
        }

        $this->log($key, 'ok', 'broken_link', (int) $id, "Rechecked broken link #{$id}.");
        $this->ok([
            'id'          => (int) $id,
            'http_status' => $result['status'],
            'error'       => $result['error'],
            'resolved'    => $this->svc('brokenLinks')->isOk($result['status']),
        ]);
    }

    /**
     * Permanently dismiss a broken link entry.
     */
    public function dismissBrokenLink(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'brokenlinks.dismiss');

        $entry = $this->svc('brokenLinks')->dismiss((int) $id);

        if ($entry === null) {
            $this->log($key, 'error', 'broken_link', (int) $id, 'Entry not found.');
            $this->fail(404, 'Broken link entry not found.');
        }

        $this->log($key, 'ok', 'broken_link', (int) $id, "Dismissed broken link #{$id}.");
        $this->ok($this->app->ai()->serializeBrokenLink($entry));
    }
}