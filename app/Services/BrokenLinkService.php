<?php

namespace App\Services;

use App\Models\BrokenLinkModel;
use App\Models\PageModel;
use App\Models\PostModel;

class BrokenLinkService
{
    protected BrokenLinkModel $linkModel;
    protected string $siteHost;

    public function __construct()
    {
        $this->linkModel = new BrokenLinkModel();
        $this->siteHost  = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');
    }

    /**
     * Run a full scan of all published posts and pages.
     * Returns summary: ['total' => int, 'broken' => int, 'sources' => int]
     */
    public function scan(): array
    {
        $client  = \Config\Services::curlrequest(['timeout' => 10]);
        $sources = $this->collectSources();

        $totalLinks  = 0;
        $brokenCount = 0;

        foreach ($sources as $source) {
            $links = $this->extractLinks($source['content']);

            foreach ($links as $url) {
                $totalLinks++;
                $result = $this->checkUrl($client, $url);

                $this->linkModel->upsert([
                    'source_type'   => $source['type'],
                    'source_id'     => $source['id'],
                    'source_title'  => $source['title'],
                    'url'           => $url,
                    'http_status'   => $result['status'],
                    'error_message' => $result['error'] ?? null,
                ]);

                if (! $this->linkModel->isOk($result['status'])) {
                    $brokenCount++;
                }
            }

            $this->linkModel->deleteOk($source['type'], $source['id']);
        }

        return [
            'total'   => $totalLinks,
            'broken'  => $brokenCount,
            'sources' => count($sources),
        ];
    }

    /**
     * Check a single URL. Returns ['status' => int|null, 'error' => string|null].
     */
    public function checkUrl($client, string $url): array
    {
        try {
            $response = $client->request('HEAD', $url, [
                'http_errors'     => false,
                'allow_redirects' => ['max' => 5],
                'headers'         => ['User-Agent' => 'Pubvana-LinkChecker/1.0'],
            ]);

            $status = $response->getStatusCode();

            if ($status === 405) {
                $response = $client->request('GET', $url, [
                    'http_errors'     => false,
                    'allow_redirects' => ['max' => 5],
                    'headers'         => ['User-Agent' => 'Pubvana-LinkChecker/1.0'],
                ]);
                $status = $response->getStatusCode();
            }

            return ['status' => $status, 'error' => null];
        } catch (\Throwable $e) {
            return ['status' => null, 'error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }

    /**
     * Collect all published posts and pages as source arrays.
     */
    public function collectSources(): array
    {
        $sources = [];

        foreach ((new PostModel())->published()->select('id, title, content')->findAll() as $post) {
            $sources[] = [
                'type'    => 'post',
                'id'      => (int) $post->id,
                'title'   => $post->title,
                'content' => (string) ($post->content ?? ''),
            ];
        }

        foreach ((new PageModel())->published()->select('id, title, content')->findAll() as $page) {
            $sources[] = [
                'type'    => 'page',
                'id'      => (int) $page->id,
                'title'   => $page->title,
                'content' => (string) ($page->content ?? ''),
            ];
        }

        return $sources;
    }

    /**
     * Extract unique external URLs from HTML or Markdown content.
     */
    public function extractLinks(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        $urls = [];

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
            if (preg_match('/^(#|mailto:|tel:|javascript:|data:)/i', $href)) continue;
            if (! preg_match('/^https?:\/\//i', $href)) continue;

            $host = strtolower(parse_url($href, PHP_URL_HOST) ?? '');
            if ($host === $this->siteHost) continue;

            $filtered[$href] = true;
        }

        return array_keys($filtered);
    }
}
