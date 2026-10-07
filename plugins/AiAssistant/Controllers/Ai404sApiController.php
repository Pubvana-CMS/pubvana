<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

/**
 * Ai404sApiController - /api/ai/404s/* endpoints.
 *
 * Lists the tracked incoming 404s and triages them through the Redirects
 * plugin: ignore, unignore, delete, or point the path at a new URL by
 * creating a redirect rule.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class Ai404sApiController extends AiApiController
{
    /** @var list<string> Statuses the list endpoint accepts. */
    private const STATUSES = ['active', 'ignored', 'resolved', 'all'];

    /**
     * List the tracked 404 entries.
     *
     * Query params: status (default active, an unknown value falls back to
     * it), page, per_page. The response repeats the status and carries the
     * count for every status, so a caller sees the whole picture from one
     * call. The AiService serializer holds the entry shape.
     */
    public function entries(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, '404s.read');

        $query = $this->app->request()->query;
        $status = (string) ($query->status ?? 'active');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));

        $service = $this->svc('redirectLinks');
        $links = $service->paginate($status, $page, $perPage);

        $items = [];
        foreach ($links['items'] as $entry) {
            $items[] = $this->app->ai()->serializeRedirectLink($entry);
        }

        $this->log($key, 'ok', 'redirect_link', null, "Listed 404 entries ({$status}).");
        $this->ok([
            'status'   => $status,
            'page'     => $links['page'],
            'per_page' => $links['per_page'],
            'total'    => $links['total'],
            'counts'   => [
                'active'   => $service->count('active'),
                'ignored'  => $service->count('ignored'),
                'resolved' => $service->count('resolved'),
            ],
            'items'    => $items,
        ]);
    }

    /**
     * Ignore a 404 entry, or put an ignored one back in the open list.
     */
    public function ignoreEntry(string $id): void
    {
        $this->setIgnored($id, true);
    }

    /**
     * Remove the ignored flag from a 404 entry.
     */
    public function unignoreEntry(string $id): void
    {
        $this->setIgnored($id, false);
    }

    /**
     * Delete a 404 entry.
     */
    public function deleteEntry(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, '404s.delete');

        if (!$this->svc('redirectLinks')->delete((int) $id)) {
            $this->log($key, 'error', 'redirect_link', (int) $id, 'Entry not found.');
            $this->fail(404, '404 entry not found.');
        }

        $this->log($key, 'ok', 'redirect_link', (int) $id, "Deleted 404 entry #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    /**
     * Point a 404 path at a new URL and mark the entry resolved.
     *
     * The source path comes from the entry, never from the request, so the
     * redirect always covers the path that actually 404ed. The call writes
     * a redirect rule, so it needs redirects.create on top of its own grant.
     */
    public function createRedirect(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, '404s.resolve');
        $this->requireGrant($key, 'redirects.create');

        $entry = $this->svc('redirectLinks')->find((int) $id);
        if ($entry === null) {
            $this->log($key, 'error', 'redirect_link', (int) $id, 'Entry not found.');
            $this->fail(404, '404 entry not found.');
        }

        $payload = $this->payload();
        $targetUrl = trim((string) ($payload['target_url'] ?? ''));
        if ($targetUrl === '') {
            $this->log($key, 'error', 'redirect_link', (int) $id, 'Missing target_url.');
            $this->fail(422, 'target_url is required.');
        }

        try {
            $redirect = $this->svc('redirects')->create([
                'source_path' => (string) $entry->source_path,
                'target_url'  => $targetUrl,
                'status_code' => (int) ($payload['status_code'] ?? 301),
                // Absent means enabled, matching the admin create form.
                'enabled'     => array_key_exists('enabled', $payload) ? !empty($payload['enabled']) : true,
                'notes'       => $this->app->ai()->nullableString($payload['notes'] ?? null),
            ]);
        } catch (\InvalidArgumentException $e) {
            // A source path that already has a redirect, or a target the
            // redirects service refuses, is the caller's to fix.
            $this->log($key, 'error', 'redirect_link', (int) $id, $e->getMessage());
            $this->fail(422, $e->getMessage());
        }

        $resolved = $this->svc('redirectLinks')->markResolved((int) $id, (int) $redirect->id);
        if ($resolved === null) {
            $this->log($key, 'error', 'redirect_link', (int) $id, 'Entry not found on resolve.');
            $this->fail(404, '404 entry not found.');
        }

        $this->log(
            $key,
            'ok',
            'redirect_link',
            (int) $id,
            "Created redirect #{$redirect->id} for 404 entry #{$id}."
        );
        $this->ok([
            'redirect' => $this->app->ai()->serializeRedirect($redirect),
            'entry'    => $this->app->ai()->serializeRedirectLink($resolved),
        ]);
    }

    /**
     * Shared ignore and unignore body: both need the same grant, differ
     * only in the flag, and answer with the updated entry.
     */
    private function setIgnored(string $id, bool $ignored): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, '404s.ignore');

        $entry = $this->svc('redirectLinks')->setIgnored((int) $id, $ignored);
        if ($entry === null) {
            $this->log($key, 'error', 'redirect_link', (int) $id, 'Entry not found.');
            $this->fail(404, '404 entry not found.');
        }

        $this->log(
            $key,
            'ok',
            'redirect_link',
            (int) $id,
            ($ignored ? 'Ignored' : 'Unignored') . " 404 entry #{$id}."
        );
        $this->ok($this->app->ai()->serializeRedirectLink($entry));
    }
}
