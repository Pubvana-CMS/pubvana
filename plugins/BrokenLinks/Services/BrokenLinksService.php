<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\Services;

use Pubvana\Plugins\BrokenLinks\Models\BrokenLink;
use flight\Engine;

/**
 * Service layer for scanning outbound links and managing broken link results.
 */
class BrokenLinksService
{
    private \PDO $pdo;
    /** @var Engine<object> */
    private Engine $app;
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param Engine<object>      $app
     * @param array<string, mixed> $config
     */
    public function __construct(\PDO $pdo, Engine $app, array $config = [])
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Run a full scan of all registered content sources.
     *
     * @return array{total: int, broken: int, sources: int}
     */
    public function scan(): array
    {
        $sources = $this->collectSources();
        $totalLinks = 0;
        $brokenCount = 0;

        foreach ($sources as $source) {
            $links = $this->extractLinks($source['content']);

            foreach ($links as $url) {
                $totalLinks++;
                $result = $this->checkUrl($url);

                $this->upsert([
                    'source_type'   => $source['type'],
                    'source_id'     => $source['id'],
                    'source_title'  => $source['title'],
                    'url'           => $url,
                    'http_status'   => $result['status'],
                    'error_message' => $result['error'] ?? null,
                ]);

                if (!$this->isOk($result['status'])) {
                    $brokenCount++;
                }
            }

            $this->deleteOk($source['type'], $source['id']);
        }

        return [
            'total'   => $totalLinks,
            'broken'  => $brokenCount,
            'sources' => count($sources),
        ];
    }

    /**
     * List broken link results, optionally including dismissed entries.
     *
     * @return array<int, array{source_type: string, source_id: int, source_title: string, links: BrokenLink[]}>
     */
    public function all(bool $showDismissed = false): array
    {
        $rows = $this->model()->allOrdered($showDismissed);
        $grouped = [];

        foreach ($rows as $row) {
            $key = $row->source_type . ':' . $row->source_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'source_type'  => $row->source_type,
                    'source_id'    => (int) $row->source_id,
                    'source_title' => $row->source_title,
                    'links'        => [],
                ];
            }
            $grouped[$key]['links'][] = $row;
        }

        return array_values($grouped);
    }

    /**
     * Recheck a single broken link by ID.
     *
     * @return array{status: int|null, error: string|null} The check result
     */
    public function recheck(int $id): array
    {
        $entry = $this->model()->findById($id);
        if ($entry === null) {
            return ['status' => null, 'error' => 'Entry not found.'];
        }

        $result = $this->checkUrl($entry->url);

        $this->upsert([
            'source_type'   => $entry->source_type,
            'source_id'     => (int) $entry->source_id,
            'source_title'  => $entry->source_title,
            'url'           => $entry->url,
            'http_status'   => $result['status'],
            'error_message' => $result['error'] ?? null,
        ]);

        if ($this->isOk($result['status'])) {
            $this->deleteBySourceAndHash(
                $entry->source_type,
                (int) $entry->source_id,
                $entry->url_hash
            );
        }

        return $result;
    }

    /**
     * Permanently dismiss a broken link entry.
     *
     * @return BrokenLink|null Null if not found
     */
    public function dismiss(int $id): ?BrokenLink
    {
        $entry = $this->model()->findById($id);
        if ($entry === null) {
            return null;
        }

        $entry->dismissed = 1;
        $entry->save();

        return $entry;
    }

    /**
     * Insert or update a broken link result keyed on (source_type, source_id, url_hash).
     *
     * Dismissed rows are never modified.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(array $data): void
    {
        $now = $this->now();
        $hash = sha1((string) ($data['url'] ?? ''));

        $existing = $this->model()->findBySourceAndHash(
            (string) ($data['source_type'] ?? ''),
            (int) ($data['source_id'] ?? 0),
            $hash
        );

        $httpStatus = isset($data['http_status']) ? (int) $data['http_status'] : null;
        $errorMessage = isset($data['error_message']) ? mb_substr((string) $data['error_message'], 0, 255) : null;

        if ($existing !== null) {
            if ($existing->dismissed === 1) {
                return;
            }

            $existing->http_status = $httpStatus;
            $existing->error_message = $errorMessage;
            $existing->last_checked_at = $now;
            $existing->updated_at = $now;
            $existing->save();
        } else {
            $entry = $this->model();
            $entry->source_type = (string) ($data['source_type'] ?? '');
            $entry->source_id = (int) ($data['source_id'] ?? 0);
            $entry->source_title = mb_substr((string) ($data['source_title'] ?? ''), 0, 255);
            $entry->url = (string) ($data['url'] ?? '');
            $entry->url_hash = $hash;
            $entry->http_status = $httpStatus;
            $entry->error_message = $errorMessage;
            $entry->dismissed = 0;
            $entry->last_checked_at = $now;
            $entry->created_at = $now;
            $entry->updated_at = $now;
            $entry->insert();
        }
    }

    /**
     * Delete a result row by ID.
     *
     * @return bool False if not found
     */
    public function delete(int $id): bool
    {
        $entry = $this->model()->findById($id);
        if ($entry === null) {
            return false;
        }

        $entry->delete();
        return true;
    }

    /**
     * Remove results for URLs that are now OK after a rescan of a source.
     */
    public function deleteOk(string $sourceType, int $sourceId): void
    {
        $conn = $this->pdo;
        $stmt = $conn->prepare(
            'DELETE FROM broken_links WHERE source_type = ? AND source_id = ? AND http_status >= 200 AND http_status < 300'
        );
        $stmt->execute([$sourceType, $sourceId]);
    }

    /**
     * Delete a specific result by source and URL hash.
     */
    public function deleteBySourceAndHash(string $sourceType, int $sourceId, string $urlHash): void
    {
        $conn = $this->pdo;
        $stmt = $conn->prepare(
            'DELETE FROM broken_links WHERE source_type = ? AND source_id = ? AND url_hash = ?'
        );
        $stmt->execute([$sourceType, $sourceId, $urlHash]);
    }

    /**
     * Check if an HTTP status code indicates success.
     */
    public function isOk(?int $status): bool
    {
        return $status !== null && $status >= 200 && $status < 300;
    }

    /**
     * Count undismissed broken link entries.
     *
     * @return int
     */
    public function countBroken(): int
    {
        $conn = $this->pdo;
        $stmt = $conn->query(
            'SELECT COUNT(*) FROM broken_links WHERE dismissed = 0 AND (http_status IS NULL OR http_status < 200 OR http_status >= 300)'
        );
        if ($stmt === false) {
            return 0;
        }
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get recent undismissed broken link entries for the dashboard.
     *
     * @return BrokenLink[]
     */
    public function recent(int $limit = 5): array
    {
        $conn = $this->pdo;
        $limit = max(1, $limit);
        $stmt = $conn->prepare(
            'SELECT * FROM broken_links WHERE dismissed = 0 ORDER BY last_checked_at DESC LIMIT ' . $limit
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $entry = $this->model();
            foreach ($row as $key => $value) {
                $entry->$key = $value;
            }
            $results[] = $entry;
        }

        return $results;
    }

    /**
     * Collect all registered content sources via adext.
     *
     * @return array<int, array{type: string, id: int, title: string, content: string}>
     */
    public function collectSources(): array
    {
        $sources = [];
        $registered = $this->app->adext()->get('brokenlinks', 'source');

        foreach ($registered as $contribution) {
            if (!isset($contribution['callable']) || !is_callable($contribution['callable'])) {
                continue;
            }

            $items = call_user_func($contribution['callable']);
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (
                    is_array($item)
                    && isset($item['type'], $item['id'], $item['title'], $item['content'])
                ) {
                    $sources[] = $item;
                }
            }
        }

        return $sources;
    }

    /**
     * Extract unique external URLs from HTML or Markdown content.
     *
     * @return string[]
     */
    public function extractLinks(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        $urls = [];
        $siteHost = strtolower((string) parse_url(
            $this->app->settings()->get('CMS.siteUrl', ''),
            PHP_URL_HOST
        ));

        // HTML <a href="..."> tags
        $doc = new \DOMDocument();
        @$doc->loadHTML('<meta charset="utf-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);

        foreach ($doc->getElementsByTagName('a') as $node) {
            $href = trim($node->getAttribute('href'));
            if ($href !== '') {
                $urls[$href] = true;
            }
        }

        // Markdown [text](url) links
        if (preg_match_all('/\[(?:[^\]]*)\]\((https?:\/\/[^\s\)]+)\)/', $content, $matches)) {
            foreach ($matches[1] as $href) {
                $urls[$href] = true;
            }
        }

        // Bare URLs not inside markup
        if (preg_match_all('/(?<!["\(=])(https?:\/\/[^\s<>\)\"]+)/', $content, $matches)) {
            foreach ($matches[1] as $href) {
                $urls[$href] = true;
            }
        }

        // Filter: external, checkable URLs only
        $filtered = [];
        foreach (array_keys($urls) as $href) {
            if (preg_match('/^(#|mailto:|tel:|javascript:|data:)/i', $href)) {
                continue;
            }
            if (!preg_match('/^https?:\/\//i', $href)) {
                continue;
            }

            $host = strtolower((string) parse_url($href, PHP_URL_HOST));
            if ($host !== '' && $host === $siteHost) {
                continue;
            }

            $filtered[$href] = true;
        }

        return array_keys($filtered);
    }

    /**
     * Check a single URL via HTTP.
     *
     * @return array{status: int|null, error: string|null}
     */
    public function checkUrl(string $url): array
    {
        $timeout = (int) ($this->config['timeout'] ?? 10);
        $maxRedirects = (int) ($this->config['max_redirects'] ?? 5);
        $userAgent = (string) ($this->config['user_agent'] ?? 'Pubvana-LinkChecker/1.0');

        try {
            $status = $this->doHttpRequest('HEAD', $url, $timeout, $maxRedirects, $userAgent);

            if ($status === 405) {
                $status = $this->doHttpRequest('GET', $url, $timeout, $maxRedirects, $userAgent);
            }

            return ['status' => $status, 'error' => null];
        } catch (\Throwable $e) {
            return ['status' => null, 'error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }

    /**
     * Perform an HTTP request and return the status code.
     *
     * @throws \RuntimeException on connection failure
     */
    private function doHttpRequest(string $method, string $url, int $timeout, int $maxRedirects, string $userAgent): int
    {
        if ($url === '' || $method === '' || $userAgent === '') {
            throw new \RuntimeException('A non-empty URL, method, and user agent are required.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_NOBODY, $method === 'HEAD');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirects);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($error !== '' ? $error : 'curl request failed');
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function model(): BrokenLink
    {
        return new BrokenLink($this->pdo);
    }
}
