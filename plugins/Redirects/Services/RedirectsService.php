<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Services;

use Pubvana\Plugins\Redirects\Models\Redirect;
use Pubvana\Plugins\Redirects\Models\RedirectLink;
use Pubvana\Services\UrlService;
use flight\Engine;

/**
 * Service layer for URL redirects: CRUD, matching, and request handling.
 */
class RedirectsService
{
    private const UNSAFE_TARGET_MESSAGE = 'Target URL must be a relative path or a full http:// or https:// URL.';
    private const DUPLICATE_SOURCE_MESSAGE = 'A redirect for that path already exists.';

    private \PDO $pdo;
    /** @var Engine<object> */
    private Engine $app;
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param Engine<object> $app
     * @param array<string, mixed> $config
     */
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
     * Paginated redirects.
     *
     * @return array{items: array<int, Redirect>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 25): array
    {
        return [
            'items'    => $this->model()->paginate($page, $perPage),
            'total'    => $this->model()->countAll(),
            'page'     => $page,
            'per_page' => $perPage,
        ];
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
        return $this->model()->countAll();
    }

    /**
     * Count enabled redirects.
     *
     * @return int
     */
    public function countEnabled(): int
    {
        return $this->model()->countEnabled();
    }

    /**
     * Create a new redirect from form data.
     *
     * @param array<string, mixed> $data POST data with source_path, target_url, status_code, enabled, notes
     * @return Redirect
     */
    public function create(array $data): Redirect
    {
        $this->assertSafeTarget((string) ($data['target_url'] ?? ''));

        $payload = $this->preparePayload($data);
        $this->validateWildcardPattern((string) $payload['source_path'], (string) $payload['target_url']);
        $this->assertSourcePathFree((string) $payload['source_path'], null);

        $now = $this->now();
        $redirect = $this->model();

        $redirect->source_path = $payload['source_path'];
        $redirect->target_url = $payload['target_url'];
        $redirect->status_code = $payload['status_code'];
        $redirect->enabled = $payload['enabled'];
        $redirect->notes = $payload['notes'];
        $redirect->hit_count = 0;
        $redirect->last_hit_at = null;
        $redirect->created_at = $now;
        $redirect->updated_at = $now;

        try {
            $redirect->insert();
        } catch (\PDOException $e) {
            throw $this->duplicateOrRethrow($e);
        }

        return $redirect;
    }

    /**
     * Update an existing redirect.
     *
     * @param int   $id   Redirect ID
     * @param array<string, mixed> $data Updated field values
     * @return Redirect|null Null if not found
     */
    public function update(int $id, array $data): ?Redirect
    {
        $redirect = $this->find($id);
        if ($redirect === null) {
            return null;
        }

        $this->assertSafeTarget((string) ($data['target_url'] ?? ''));

        $payload = $this->preparePayload($data);
        $this->validateWildcardPattern((string) $payload['source_path'], (string) $payload['target_url']);
        $this->assertSourcePathFree((string) $payload['source_path'], $id);

        $redirect->source_path = $payload['source_path'];
        $redirect->target_url = $payload['target_url'];
        $redirect->status_code = $payload['status_code'];
        $redirect->enabled = $payload['enabled'];
        $redirect->notes = $payload['notes'];
        $redirect->updated_at = $this->now();

        try {
            $redirect->save();
        } catch (\PDOException $e) {
            throw $this->duplicateOrRethrow($e);
        }

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

        // The entries this redirect resolved are unresolved again, so they
        // return to the 404 manager's Active list instead of staying Resolved.
        foreach ((new RedirectLink($this->pdo))->allResolvedBy($id) as $link) {
            $link->resolved_redirect_id = null;
            $link->resolved_at = null;
            $link->save();
        }

        return true;
    }

    /**
     * Get grouped target URL suggestions from pages and blog posts.
     *
     * @return array<string, array<int, array<string, string|null>>> Group label => list of [label, url] items
     */
    public function getTargetSuggestions(): array
    {
        $groups = [];

        try {
            $pages = $this->app->pages()->listPublished(100);
            $prefix = $this->app->pluginLoader()->routePrefix('pubvana/pages');
            $items = [];
            foreach ($pages as $page) {
                $items[] = [
                    'label' => $page->title,
                    'url'   => $prefix . '/' . $page->slug,
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
            foreach ($result['items'] as $post) {
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

        $result = $this->model()->findActiveBySourcePath($path);
        $redirect = $result['redirect'];
        if ($redirect === null) {
            return;
        }

        // Handle wildcard substitution
        $targetUrl = (string) $redirect->target_url;
        $captures = $result['captures'];
        if (!empty($captures)) {
            // Replace $1, $2, etc. with captured groups
            for ($i = 1; $i < count($captures); $i++) {
                $targetUrl = str_replace('$' . $i, $captures[$i], $targetUrl);
            }
        }

        $location = $this->buildRedirectLocation($targetUrl);
        if ($this->isSelfRedirect($location, $path, $request->host)) {
            return;
        }

        $redirect->hit_count = ((int) $redirect->hit_count) + 1;
        $redirect->last_hit_at = $this->now();
        $redirect->save();

        $this->app->redirect($location, (int) $redirect->status_code);
        exit;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
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

    /**
     * Refuse a hostile target before anything is written.
     *
     * Relative paths are fine. A scheme-bearing target must be http/https
     * (UrlService::isSafeExternalUrl, the same allowlist profile website
     * fields use): the value ends up in a Location header, so javascript:,
     * data:, ftp: and friends are refused. Scheme-relative "//host"
     * targets would navigate visitors off-site, and backslashes or control
     * characters are malformed header material.
     *
     * @throws \InvalidArgumentException When the target cannot be trusted.
     */
    private function assertSafeTarget(string $target): void
    {
        $target = trim($target);
        if ($target === '') {
            return; // normalizeTargetUrl() stores '/'.
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $target) === 1) {
            if (!UrlService::isSafeExternalUrl($target)) {
                throw new \InvalidArgumentException(self::UNSAFE_TARGET_MESSAGE);
            }
            return;
        }

        if (
            str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x1f\x7f]/', $target) === 1
        ) {
            throw new \InvalidArgumentException(self::UNSAFE_TARGET_MESSAGE);
        }
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

        // Allow trailing * for wildcards, otherwise strip trailing slash
        if (!str_ends_with($path, '*') && $path !== '/') {
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

    /**
     * Validate the source pattern and the placeholders in its target.
     *
     * The source may hold one wildcard, the trailing '*'. '$1' in the
     * target inserts the captured text and is optional: a wildcard target
     * without it drops the capture and sends every match to the same
     * place. '$1' with no wildcard source would reach the visitor
     * literally, and a wildcard captures exactly one group, so '$1' is
     * the only placeholder accepted.
     *
     * @throws \InvalidArgumentException When the pattern is invalid
     */
    private function validateWildcardPattern(string $sourcePath, string $targetUrl): void
    {
        $normalized = $this->normalizeSourcePath($sourcePath);
        $star = strpos($normalized, '*');

        if ($star !== false && $star !== strlen($normalized) - 1) {
            throw new \InvalidArgumentException('A source path may hold one wildcard, the trailing * (for example /old/*).');
        }

        if (preg_match('/\$(?!1(?!\d))\d/', $targetUrl) === 1) {
            throw new \InvalidArgumentException('Target URL may use $1 only, the single captured wildcard value.');
        }

        if ($star === false && preg_match('/\$1(?!\d)/', $targetUrl) === 1) {
            throw new \InvalidArgumentException('Target URL contains $1 but the source path is not a wildcard, which must end with *.');
        }
    }

    /**
     * Refuse a source path another redirect already holds.
     *
     * The unique index on redirects.source_path would otherwise raise a
     * query error and hand the admin a 500 instead of a form message.
     *
     * @param string   $sourcePath
     * @param int|null $exceptId   Row allowed to keep the path (the one being edited)
     * @throws \InvalidArgumentException When another row holds the path
     */
    private function assertSourcePathFree(string $sourcePath, ?int $exceptId): void
    {
        $existing = $this->model()->findBySourcePath($sourcePath);
        if ($existing !== null && (int) $existing->id !== $exceptId) {
            throw new \InvalidArgumentException(self::DUPLICATE_SOURCE_MESSAGE);
        }
    }

    /**
     * Turn a duplicate-key failure into the same form error the pre-check
     * raises. Any other database error is rethrown untouched.
     *
     * @param \PDOException $e
     * @return \Throwable
     */
    private function duplicateOrRethrow(\PDOException $e): \Throwable
    {
        if ((string) $e->getCode() === '23000') {
            return new \InvalidArgumentException(self::DUPLICATE_SOURCE_MESSAGE, 0, $e);
        }

        return $e;
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
