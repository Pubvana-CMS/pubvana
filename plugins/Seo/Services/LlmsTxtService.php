<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use Pubvana\Plugins\Seo\Models\SeoMeta;
use flight\Engine;

/**
 * llms.txt generation — provides AI crawlers with a curated site map.
 *
 * Format follows the llmstxt.org specification:
 * - H1: site name
 * - Blockquote: site description
 * - H2 sections grouping content by type
 * - Markdown links with optional descriptions
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
        $siteName = $settings->get('CMS.siteName');
        $siteDescription = $settings->get('CMS.siteByline');
        $siteUrl = $this->getSiteUrl();

        $lines = [];

        // H1 — site name (mandatory per spec)
        $lines[] = '# ' . $siteName;
        $lines[] = '';

        // Blockquote — site description (optional per spec)
        if (!empty($siteDescription)) {
            $lines[] = '> ' . $siteDescription;
            $lines[] = '';
        }

        // Pages section
        if ($settings->get('Seo.llms_txt_include_pages', true)) {
            $pages = $this->getPublishedPages();
            if (!empty($pages)) {
                $lines[] = '## Pages';
                $lines[] = '';
                foreach ($pages as $page) {
                    $desc = !empty($page['meta_description']) ? ': ' . $page['meta_description'] : '';
                    $lines[] = '- [' . $page['title'] . '](' . $siteUrl . '/page/' . $page['slug'] . ')' . $desc;
                }
                $lines[] = '';
            }
        }

        // Blog posts section
        if ($settings->get('Seo.llms_txt_include_posts', true)) {
            $posts = $this->getPublishedPosts();
            if (!empty($posts)) {
                $lines[] = '## Blog';
                $lines[] = '';
                foreach ($posts as $post) {
                    $desc = !empty($post['meta_description']) ? ': ' . $post['meta_description'] : '';
                    $lines[] = '- [' . $post['title'] . '](' . $siteUrl . '/blog/' . $post['slug'] . ')' . $desc;
                }
                $lines[] = '';
            }
        }

        // Categories section
        $categories = $this->getCategories();
        if (!empty($categories)) {
            $lines[] = '## Topics';
            $lines[] = '';
            foreach ($categories as $cat) {
                $lines[] = '- [' . $cat['name'] . '](' . $siteUrl . '/blog/category/' . $cat['slug'] . ')';
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getPublishedPages(): array
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
            if (count($result) >= self::MAX_SECTION_ITEMS) {
                break;
            }

            $result[] = [
                'title'            => $page->title,
                'slug'             => $page->slug,
                'meta_description' => isset($meta[$id]) ? (string) ($meta[$id]->meta_description ?? '') : '',
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getPublishedPosts(): array
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
            if (count($result) >= self::MAX_SECTION_ITEMS) {
                break;
            }

            $result[] = [
                'title'            => $post->title,
                'slug'             => $post->slug,
                'meta_description' => isset($meta[$id]) ? (string) ($meta[$id]->meta_description ?? '') : '',
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
