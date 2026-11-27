<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use Pubvana\Plugins\Seo\Models\SeoMeta;
use flight\Engine;

/**
 * llms.txt generation, provides AI crawlers with a curated site map.
 *
 * Format follows the llmstxt.org specification:
 * - H1: site name
 * - Blockquote: site description
 * - H2 sections grouping content by type
 * - Markdown links with optional descriptions
 *
 * The companion llms-full.txt carries the full text of every published,
 * indexable page and post, so an agent can read the site in one request.
 */
class LlmsTxtService
{
    /**
     * Per-section item cap, matching the llms.txt convention of a short,
     * curated list rather than a full dump.
     */
    protected const MAX_SECTION_ITEMS = 50;

    /**
     * Upper bound on the items pulled from a host before filtering.
     */
    protected const MAX_ITEMS = 5000;

    /**
     * Per-section item cap for the full content file.
     */
    protected const MAX_FULL_ITEMS = 500;

    /**
     * Upper bound on the full content file, so one large site cannot build
     * an unbounded response in memory.
     */
    protected const MAX_FULL_LENGTH = 2_000_000;

    protected \PDO $pdo;
    /** @var Engine<object> */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(\PDO $pdo, Engine $app)
    {
        $this->pdo = $pdo;
        $this->app = $app;
    }

    /**
     * Generate the llms.txt content.
     */
    public function generate(): string
    {
        $settings = $this->app->settings();
        $siteName = (string) $settings->get('CMS.siteName');
        $siteDescription = (string) $settings->get('CMS.siteByline');
        $siteUrl = $this->getSiteUrl();

        $lines = [];

        // H1: site name (mandatory per spec)
        $lines[] = '# ' . $siteName;
        $lines[] = '';

        // Blockquote: site description (optional per spec)
        if ($siteDescription !== '') {
            $lines[] = '> ' . $siteDescription;
            $lines[] = '';
        }

        // Pages section
        if ($settings->get('Seo.llms_txt_include_pages', true)) {
            $pages = $this->getPublishedPages();
            if ($pages !== []) {
                $lines[] = '## Pages';
                $lines[] = '';
                foreach ($pages as $page) {
                    $lines[] = '- [' . $page['title'] . '](' . $siteUrl . '/page/' . $page['slug'] . ')'
                        . $this->descriptionSuffix($page);
                }
                $lines[] = '';
            }
        }

        // Blog posts section
        if ($settings->get('Seo.llms_txt_include_posts', true)) {
            $posts = $this->getPublishedPosts();
            if ($posts !== []) {
                $lines[] = '## Blog';
                $lines[] = '';
                foreach ($posts as $post) {
                    $lines[] = '- [' . $post['title'] . '](' . $siteUrl . '/blog/' . $post['slug'] . ')'
                        . $this->descriptionSuffix($post);
                }
                $lines[] = '';
            }
        }

        // Categories section
        if ($settings->get('Seo.llms_txt_include_categories', true)) {
            $categories = $this->getCategories();
            if ($categories !== []) {
                $lines[] = '## Topics';
                $lines[] = '';
                foreach ($categories as $cat) {
                    $lines[] = '- [' . $cat['name'] . '](' . $siteUrl . '/blog/category/' . $cat['slug'] . ')';
                }
                $lines[] = '';
            }
        }

        // Optional: lower-priority links, including the full content file.
        $lines[] = '## Optional';
        $lines[] = '';
        $lines[] = '- [Home](' . $siteUrl . ')';
        $lines[] = '- [Full content](' . $siteUrl . '/llms-full.txt): Complete text of every published page and post.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Generate llms-full.txt, the full text of every published, indexable
     * page and post.
     */
    public function generateFull(): string
    {
        $settings = $this->app->settings();
        $siteName = (string) $settings->get('CMS.siteName');
        $siteDescription = (string) $settings->get('CMS.siteByline');
        $siteUrl = $this->getSiteUrl();

        $lines = ['# ' . $siteName, ''];
        if ($siteDescription !== '') {
            $lines[] = '> ' . $siteDescription;
            $lines[] = '';
        }

        $length = strlen(implode("\n", $lines));

        if ($settings->get('Seo.llms_txt_include_pages', true)) {
            $pages = $this->getPublishedPages(self::MAX_FULL_ITEMS);
            if ($pages !== []) {
                $lines[] = '## Pages';
                $lines[] = '';
                foreach ($pages as $page) {
                    $entry = $this->fullEntry($siteUrl . '/page/' . $page['slug'], $page);
                    if ($length + strlen($entry) > self::MAX_FULL_LENGTH) {
                        break;
                    }
                    $lines[] = $entry;
                    $length += strlen($entry);
                }
            }
        }

        if ($settings->get('Seo.llms_txt_include_posts', true)) {
            $posts = $this->getPublishedPosts(self::MAX_FULL_ITEMS);
            if ($posts !== []) {
                $lines[] = '## Blog';
                $lines[] = '';
                foreach ($posts as $post) {
                    $entry = $this->fullEntry($siteUrl . '/blog/' . $post['slug'], $post);
                    if ($length + strlen($entry) > self::MAX_FULL_LENGTH) {
                        break;
                    }
                    $lines[] = $entry;
                    $length += strlen($entry);
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getPublishedPages(int $limit = self::MAX_SECTION_ITEMS): array
    {
        try {
            $pages = $this->app->pages()->listPublished(self::MAX_ITEMS);
        } catch (\Throwable) {
            // Pages disabled or unavailable: omit that section, do not 500.
            return [];
        }

        $meta = $this->metaByContent('page');
        $result = [];

        foreach ($pages as $page) {
            $id = (int) $page->id;

            // Filter noindex before the cap, so a hidden item never eats a slot.
            if (isset($meta[$id]) && $meta[$id]->isNoindex()) {
                continue;
            }
            if (count($result) >= $limit) {
                break;
            }

            $result[] = [
                'title'            => $page->title,
                'slug'             => $page->slug,
                'meta_description' => isset($meta[$id]) ? (string) ($meta[$id]->meta_description ?? '') : '',
                'excerpt'          => '',
                'content'          => (string) ($page->content ?? ''),
                'published_at'     => null,
                'updated_at'       => $page->updated_at ?? null,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getPublishedPosts(int $limit = self::MAX_SECTION_ITEMS): array
    {
        try {
            $posts = $this->app->blog()->listPosts(1, self::MAX_ITEMS, 'published');
        } catch (\Throwable) {
            // Blog disabled or unavailable: omit that section, do not 500.
            return [];
        }

        $meta = $this->metaByContent('post');
        $result = [];

        foreach ($posts['items'] as $post) {
            $id = (int) $post->id;

            // Filter noindex before the cap, so a hidden item never eats a slot.
            if (isset($meta[$id]) && $meta[$id]->isNoindex()) {
                continue;
            }
            if (count($result) >= $limit) {
                break;
            }

            $result[] = [
                'title'            => $post->title,
                'slug'             => $post->slug,
                'meta_description' => isset($meta[$id]) ? (string) ($meta[$id]->meta_description ?? '') : '',
                'excerpt'          => (string) ($post->excerpt ?? ''),
                'content'          => (string) ($post->content ?? ''),
                'published_at'     => $post->published_at ?? null,
                'updated_at'       => $post->updated_at ?? null,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getCategories(): array
    {
        try {
            $categories = $this->app->blog()->listCategories();
        } catch (\Throwable) {
            return [];
        }

        $result = [];

        foreach ($categories as $cat) {
            $result[] = [
                'name' => $cat->name,
                'slug' => $cat->slug,
            ];
        }

        return $result;
    }

    /**
     * One entry in llms-full.txt: heading, URL, dates, description, body.
     *
     * @param array<string, mixed> $item
     */
    protected function fullEntry(string $url, array $item): string
    {
        $lines = ['### ' . (string) ($item['title'] ?? ''), '', 'URL: ' . $url];

        $published = trim((string) ($item['published_at'] ?? ''));
        if ($published !== '') {
            $lines[] = 'Published: ' . $published;
        }

        $updated = trim((string) ($item['updated_at'] ?? ''));
        if ($updated !== '') {
            $lines[] = 'Updated: ' . $updated;
        }

        $description = $this->shortDescription($item);
        if ($description !== '') {
            $lines[] = '';
            $lines[] = $description;
        }

        $body = $this->htmlToText((string) ($item['content'] ?? ''));
        if ($body !== '') {
            $lines[] = '';
            $lines[] = $body;
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Description for an index line, from meta, excerpt, or body text.
     *
     * @param array<string, mixed> $item
     */
    protected function descriptionSuffix(array $item): string
    {
        $description = $this->shortDescription($item);

        return $description === '' ? '' : ': ' . $description;
    }

    /**
     * @param array<string, mixed> $item
     */
    protected function shortDescription(array $item): string
    {
        $meta = $this->oneLine((string) ($item['meta_description'] ?? ''));
        if ($meta !== '') {
            return $this->truncate($meta);
        }

        $excerpt = $this->oneLine((string) ($item['excerpt'] ?? ''));
        if ($excerpt !== '') {
            return $this->truncate($excerpt);
        }

        return $this->truncate($this->oneLine($this->htmlToText((string) ($item['content'] ?? ''))));
    }

    /**
     * Collapse whitespace, so a description fits one Markdown line.
     */
    protected function oneLine(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    protected function truncate(string $text, int $limit = 160): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 3)) . '...';
    }

    /**
     * Plain text from stored HTML, keeping paragraph breaks.
     */
    protected function htmlToText(string $html): string
    {
        $text = preg_replace('#<(br|/p|/div|/li|/h[1-6])\b[^>]*>#i', "\n", $html) ?? $html;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * SEO meta for one content type, keyed by content id.
     *
     * One query for the whole type, instead of one per item.
     *
     * @return array<int, SeoMeta>
     */
    protected function metaByContent(string $contentType): array
    {
        $model = new SeoMeta($this->pdo);
        $map = [];

        foreach ($model->findByContentType($contentType) as $meta) {
            $map[(int) $meta->content_id] = $meta;
        }

        return $map;
    }

    /**
     * Absolute site base URL from the SITE_URL deployment value via
     * UrlService::siteOrigin(); never the request Host header (AUDIT M4).
     */
    protected function getSiteUrl(): string
    {
        return $this->app->url()->siteOrigin();
    }
}
