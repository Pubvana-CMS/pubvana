<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Services;

use Pubvana\Plugins\Redirects\Models\Redirect;
use flight\Engine;

/**
 * Service layer for URL redirects — CRUD, matching, and request handling.
 */
class RedirectsService
{
    private \PDO $pdo;
    private Engine $app;
    private array $config;

    public function __construct(\PDO $pdo, Engine $app, array $config = [])
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * @return Redirect[]
     */
    public function all(): array
    {
        return $this->model()->allOrdered();
    }

    /**
     * Find a redirect by ID.
     *
     * @param int $id
     * @return Redirect|null
     */
    public function find(int $id): ?Redirect
    {
        return $this->model()->findById($id);
    }

    /**
     * Count all redirects.
     *
     * @return int
     */
    public function countAll(): int
    {
        return count($this->all());
    }

    /**
     * Count enabled redirects.
     *
     * @return int
     */
    public function countEnabled(): int
    {
        return count(array_filter($this->all(), static fn(Redirect $redirect): bool => (int) $redirect->enabled === 1));
    }

    /**
     * Create a new redirect from form data.
     *
     * @param array $data POST data with source_path, target_url, status_code, enabled, notes
     * @return Redirect
     */
    public function create(array $data): Redirect
    {
        $now = $this->now();
        $redirect = $this->model();
        $payload = $this->preparePayload($data);

        $redirect->source_path = $payload['source_path'];
        $redirect->target_url = $payload['target_url'];
        $redirect->status_code = $payload['status_code'];
        $redirect->enabled = $payload['enabled'];
        $redirect->notes = $payload['notes'];
        $redirect->hit_count = 0;
        $redirect->last_hit_at = null;
        $redirect->created_at = $now;
        $redirect->updated_at = $now;
        $redirect->insert();

        return $redirect;
    }

    /**
     * Update an existing redirect.
     *
     * @param int   $id   Redirect ID
     * @param array $data Updated field values
     * @return Redirect|null Null if not found
     */
    public function update(int $id, array $data): ?Redirect
    {
        $redirect = $this->find($id);
        if ($redirect === null) {
            return null;
        }

        $payload = $this->preparePayload($data);
        $redirect->source_path = $payload['source_path'];
        $redirect->target_url = $payload['target_url'];
        $redirect->status_code = $payload['status_code'];
        $redirect->enabled = $payload['enabled'];
        $redirect->notes = $payload['notes'];
        $redirect->updated_at = $this->now();
        $redirect->save();

        return $redirect;
    }

    /**
     * Delete a redirect by ID.
     *
     * @param int $id
     * @return bool False if not found
     */
    public function delete(int $id): bool
    {
        $redirect = $this->find($id);
        if ($redirect === null) {
            return false;
        }

        $redirect->delete();
        return true;
    }

    /**
     * Get grouped target URL suggestions from pages and blog posts.
     *
     * @return array<string, array> Group label => list of [label, url] items
     */
    public function getTargetSuggestions(): array
    {
        $groups = [];

        try {
            $pages = $this->app->pages()->listPublished(100);
            $items = [];
            foreach ($pages as $page) {
                $items[] = [
                    'label' => $page->title,
                    'url'   => '/page/' . $page->slug,
                ];
            }
            if (!empty($items)) {
                $groups['Pages'] = $items;
            }
        } catch (\Throwable $e) {
        }

        try {
            $result = $this->app->blog()->listPosts(1, 100, 'published');
            $items = [];
            foreach (($result['items'] ?? []) as $post) {
                $items[] = [
                    'label' => $post->title,
                    'url'   => $this->app->pluginLoader()->routePrefix('pubvana/blog') . '/' . $post->slug,
                ];
            }
            if (!empty($items)) {
                $groups['Blog Posts'] = $items;
            }
        } catch (\Throwable $e) {
        }

        return $groups;
    }

    /**
     * Check the current request against active redirects and issue a redirect if matched.
     *
     * @return void
     */
    public function handleCurrentRequest(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        $request = $this->app->request();
        if (!in_array($request->method, ['GET', 'HEAD'], true)) {
            return;
        }

        $path = $this->normalizeIncomingPath($request->url, $request->base);
        if ($this->shouldSkipPath($path)) {
            return;
        }

        $redirect = $this->model()->findActiveBySourcePath($path);
        if ($redirect === null) {
            return;
        }

        $location = $this->buildRedirectLocation($redirect->target_url);
        if ($this->isSelfRedirect($location, $path, $request->host)) {
            return;
        }

        $redirect->hit_count = ((int) $redirect->hit_count) + 1;
        $redirect->last_hit_at = $this->now();
        $redirect->save();

        $this->app->redirect($location, (int) $redirect->status_code);
        exit;
    }

    private function preparePayload(array $data): array
    {
        $statusCode = (int) ($data['status_code'] ?? 301);
        if (!in_array($statusCode, [301, 302], true)) {
            $statusCode = 301;
        }

        return [
            'source_path' => $this->normalizeSourcePath((string) ($data['source_path'] ?? '/')),
            'target_url'  => $this->normalizeTargetUrl((string) ($data['target_url'] ?? '/')),
            'status_code' => $statusCode,
            'enabled'     => !empty($data['enabled']) ? 1 : 0,
            'notes'       => $this->normalizeNotes($data['notes'] ?? null),
        ];
    }

    private function normalizeSourcePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }

        $parsed = parse_url($path, PHP_URL_PATH);
        $path = is_string($parsed) ? $parsed : $path;

        if ($path === '') {
            $path = '/';
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

    private function normalizeTargetUrl(string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return '/';
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $target) === 1) {
            return $target;
        }

        if ($target[0] !== '/') {
            $target = '/' . $target;
        }

        return $target;
    }

    private function normalizeIncomingPath(string $url, string $base): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        return $this->normalizeSourcePath($path);
    }

    private function buildRedirectLocation(string $targetUrl): string
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        if ($query === '') {
            return $targetUrl;
        }

        $separator = str_contains($targetUrl, '?') ? '&' : '?';
        return $targetUrl . $separator . $query;
    }

    private function shouldSkipPath(string $path): bool
    {
        foreach (($this->config['skip_prefixes'] ?? []) as $prefix) {
            $prefix = $this->normalizeSourcePath((string) $prefix);
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function isSelfRedirect(string $targetUrl, string $currentPath, string $currentHost): bool
    {
        $targetHost = (string) parse_url($targetUrl, PHP_URL_HOST);
        if ($targetHost !== '' && $targetHost !== $currentHost) {
            return false;
        }

        $targetPath = parse_url($targetUrl, PHP_URL_PATH);
        if (!is_string($targetPath) || $targetPath === '') {
            return false;
        }

        return $this->normalizeSourcePath($targetPath) === $currentPath;
    }

    private function normalizeNotes(mixed $notes): ?string
    {
        $notes = is_string($notes) ? trim($notes) : '';
        return $notes === '' ? null : $notes;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function model(): Redirect
    {
        return new Redirect($this->pdo);
    }
}
