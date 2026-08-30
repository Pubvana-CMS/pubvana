<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Services;

use Pubvana\Plugins\Redirects\Models\RedirectLink;
use flight\Engine;

/**
 * Service layer for tracking and managing incoming 404 requests.
 */
class RedirectLinksService
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
     * @return RedirectLink[]
     */
    public function all(string $status = 'active'): array
    {
        return $this->model()->allByStatus($status);
    }

    /**
     * Find an entry by ID.
     *
     * @param int $id
     * @return RedirectLink|null
     */
    public function find(int $id): ?RedirectLink
    {
        return $this->model()->findById($id);
    }

    /**
     * Count entries by status.
     *
     * @param string $status One of 'active', 'ignored', 'resolved', 'all'
     * @return int
     */
    public function count(string $status = 'active'): int
    {
        return count($this->all($status));
    }

    /**
     * @return RedirectLink[]
     */
    public function recent(string $status = 'active', int $limit = 5): array
    {
        return array_slice($this->all($status), 0, $limit);
    }

    /**
     * Delete an entry by ID.
     *
     * @param int $id
     * @return bool False if not found
     */
    public function delete(int $id): bool
    {
        $entry = $this->find($id);
        if ($entry === null) {
            return false;
        }

        $entry->delete();
        return true;
    }

    /**
     * Set or clear the ignored flag on an entry.
     *
     * @param int  $id
     * @param bool $ignored
     * @return RedirectLink|null Null if not found
     */
    public function setIgnored(int $id, bool $ignored): ?RedirectLink
    {
        $entry = $this->find($id);
        if ($entry === null) {
            return null;
        }

        $entry->ignored = $ignored ? 1 : 0;
        $entry->save();

        return $entry;
    }

    /**
     * Mark an entry as resolved by linking it to a redirect.
     *
     * @param int $id         Entry ID
     * @param int $redirectId The redirect that resolves this 404
     * @return RedirectLink|null Null if not found
     */
    public function markResolved(int $id, int $redirectId): ?RedirectLink
    {
        $entry = $this->find($id);
        if ($entry === null) {
            return null;
        }

        $entry->resolved_redirect_id = $redirectId;
        $entry->resolved_at = $this->now();
        $entry->ignored = 0;
        $entry->save();

        return $entry;
    }

    /**
     * Mark an entry as resolved by matching its source path.
     *
     * @param string $sourcePath The 404 path to look up
     * @param int    $redirectId The redirect that resolves it
     * @return RedirectLink|null Null if no entry matches the path
     */
    public function markResolvedByPath(string $sourcePath, int $redirectId): ?RedirectLink
    {
        $entry = $this->model()->findBySourcePath($sourcePath);
        if ($entry === null) {
            return null;
        }

        return $this->markResolved((int) $entry->id, $redirectId);
    }

    /**
     * Log the current request as an incoming 404, creating or updating the entry.
     *
     * @return void
     */
    public function logCurrentRequest(): void
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

        $now = $this->now();
        $entry = $this->model()->findBySourcePath($path) ?? $this->model();

        if (!$entry->isHydrated()) {
            $entry->source_path = $path;
            $entry->first_seen_at = $now;
            $entry->ignored = 0;
            $entry->hit_count = 0;
        }

        $entry->hit_count = ((int) $entry->hit_count) + 1;
        $entry->last_seen_at = $now;
        $entry->last_query_string = $this->normalizeNullableString($_SERVER['QUERY_STRING'] ?? null);
        $entry->last_referrer = $this->normalizeNullableString($_SERVER['HTTP_REFERER'] ?? null);
        $entry->last_user_agent = $this->normalizeNullableString($_SERVER['HTTP_USER_AGENT'] ?? null);
        $entry->resolved_redirect_id = null;
        $entry->resolved_at = null;

        if ($entry->isHydrated()) {
            $entry->save();
        } else {
            $entry->insert();
        }
    }

    private function normalizeIncomingPath(string $url, string $base): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

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

    private function shouldSkipPath(string $path): bool
    {
        $prefixes = $this->config['incoming_404s']['skip_prefixes'] ?? $this->config['skip_prefixes'] ?? [];
        foreach ($prefixes as $prefix) {
            $prefix = $this->normalizeIncomingPath((string) $prefix, '/');
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        return $value === '' ? null : $value;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function model(): RedirectLink
    {
        return new RedirectLink($this->pdo);
    }
}
