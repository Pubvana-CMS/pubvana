<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

/**
 * AiRedirectsApiController - /api/ai/redirects/* endpoints.
 *
 * Reads and writes redirects through the Redirects plugin.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiRedirectsApiController extends AiApiController
{
    /**
     * List all redirects.
     */
    public function redirects(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.read');

        $rows = [];
        foreach ($this->svc('redirects')->all() as $redirect) {
            $rows[] = $this->app->ai()->serializeRedirect($redirect);
        }

        $this->log($key, 'ok', 'redirect', null, 'Listed redirects.');
        $this->ok(['total' => count($rows), 'items' => $rows]);
    }

    /**
     * Create a redirect.
     */
    public function createRedirect(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.create');

        $payload = $this->payload();
        $sourcePath = trim((string) ($payload['source_path'] ?? ''));
        $targetUrl = trim((string) ($payload['target_url'] ?? ''));
        if ($sourcePath === '' || $targetUrl === '') {
            $this->log($key, 'error', 'redirect', null, 'Missing source_path or target_url.');
            $this->fail(422, 'source_path and target_url are required.');
        }

        $redirect = $this->svc('redirects')->create([
            'source_path' => $sourcePath,
            'target_url'  => $targetUrl,
            'status_code' => (int) ($payload['status_code'] ?? 301),
            'enabled'     => !empty($payload['enabled']),
            'notes'       => $this->app->ai()->nullableString($payload['notes'] ?? null),
        ]);

        $this->log($key, 'ok', 'redirect', (int) $redirect->id, "Created redirect #{$redirect->id}.");
        $this->ok($this->app->ai()->serializeRedirect($redirect));
    }

    /**
     * Update a redirect with a partial payload. Omitted fields keep their
     * current values; the redirects service itself is full-field.
     */
    public function updateRedirect(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.update');

        $redirect = $this->svc('redirects')->find((int) $id);
        if ($redirect === null) {
            $this->log($key, 'error', 'redirect', (int) $id, 'Redirect not found.');
            $this->fail(404, 'Redirect not found.');
        }

        $payload = $this->payload();
        $update = [
            'source_path' => (string) ($payload['source_path'] ?? $redirect->source_path),
            'target_url'  => (string) ($payload['target_url'] ?? $redirect->target_url),
            'status_code' => (int) ($payload['status_code'] ?? $redirect->status_code),
            'enabled'     => array_key_exists('enabled', $payload) ? !empty($payload['enabled']) : ((int) $redirect->enabled === 1),
            'notes'       => $this->app->ai()->nullableString($payload['notes'] ?? $redirect->notes),
        ];

        $saved = $this->svc('redirects')->update((int) $id, $update);
        if ($saved === null) {
            $this->log($key, 'error', 'redirect', (int) $id, 'Redirect not found on save.');
            $this->fail(404, 'Redirect not found.');
        }

        $this->log($key, 'ok', 'redirect', (int) $id, "Updated redirect #{$id}.");
        $this->ok($this->app->ai()->serializeRedirect($saved));
    }

    /**
     * Delete a redirect.
     */
    public function deleteRedirect(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.delete');

        if (!$this->svc('redirects')->delete((int) $id)) {
            $this->log($key, 'error', 'redirect', (int) $id, 'Redirect not found.');
            $this->fail(404, 'Redirect not found.');
        }

        $this->log($key, 'ok', 'redirect', (int) $id, "Deleted redirect #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }
}