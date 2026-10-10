<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use Pubvana\Plugins\Seo\Models\SeoMeta;
use flight\Engine;

/**
 * XML Sitemap generation.
 *
 * Only includes canonical, published, indexable URLs.
 * Only uses <lastmod>. Google/Bing ignore <changefreq> and <priority>.
 */
class SitemapService
{
    /**
     * Upper bound on the items pulled from each host for one sitemap.
     *
     * A single flat sitemap is the documented scope (no sitemap index), so
     * this caps memory on very large sites rather than paging.
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
     * Generate the full XML sitemap string.
     */
    public function generate(): string
    {
        $settings = $this->app->settings();
        $siteUrl = $this->getSiteUrl();
        $content = [];

        // Pages
        if ($settings->get('Seo.sitemap_include_pages', true)) {
            $content = array_merge($content, $this->getPageUrls($siteUrl));
        }

        // Posts
        if ($settings->get('Seo.sitemap_include_posts', true)) {
            $content = array_merge($content, $this->getPostUrls($siteUrl));
        }

        // Category/tag archives
        if ($settings->get('Seo.sitemap_include_archives', true)) {
            $content = array_merge($content, $this->getCategoryUrls($siteUrl));
            $content = array_merge($content, $this->getTagUrls($siteUrl));
        }

        // Homepage first, dated by the newest content it wraps so its
        // <lastmod> tracks real edits instead of resetting every day.
        $urls = [[
            'loc'     => $siteUrl . '/',
            'lastmod' => $this->latestLastmod($content),
        ]];

        return $this->buildXml(array_merge($urls, $content));
    }

    /**
     * Get published page URLs, excluding noindex pages.
     */
    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    protected function getPageUrls(string $siteUrl): array
    {
        try {
            $pages = $this->app->pages()->listPublished(self::MAX_ITEMS);
        } catch (\Throwable) {
            // Pages disabled or unavailable: omit that section, do not 500.
            return [];
        }

        $hidden = $this->noindexIds('page');
        $urls = [];

        foreach ($pages as $page) {
            if (isset($hidden[(int) $page->id])) {
                continue;
            }
            $urls[] = [
                'loc'     => $siteUrl . '/page/' . $page->slug,
                'lastmod' => $this->formatDate($page->updated_at),
            ];
        }

        return $urls;
    }

    /**
     * Get published post URLs, excluding noindex posts.
     */
    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    protected function getPostUrls(string $siteUrl): array
    {
        try {
            $result = $this->app->blog()->listPosts(1, self::MAX_ITEMS, 'published');
        } catch (\Throwable) {
            // Blog disabled or unavailable: omit that section, do not 500.
            return [];
        }

        $hidden = $this->noindexIds('post');
        $urls = [];

        foreach ($result['items'] as $post) {
            if (isset($hidden[(int) $post->id])) {
                continue;
            }
            $urls[] = [
                'loc'     => $siteUrl . '/blog/' . $post->slug,
                'lastmod' => $this->formatDate($post->updated_at),
            ];
        }

        return $urls;
    }

    /**
     * Get category archive URLs.
     */
    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    protected function getCategoryUrls(string $siteUrl): array
    {
        try {
            $categories = $this->app->blog()->listCategories();
        } catch (\Throwable) {
            return [];
        }

        $urls = [];

        foreach ($categories as $cat) {
            $urls[] = [
                'loc'     => $siteUrl . '/blog/category/' . $cat->slug,
                'lastmod' => '',
            ];
        }

        return $urls;
    }

    /**
     * Get tag archive URLs.
     */
    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    protected function getTagUrls(string $siteUrl): array
    {
        try {
            $tags = $this->app->blog()->listTags();
        } catch (\Throwable) {
            return [];
        }

        $urls = [];

        foreach ($tags as $tag) {
            $urls[] = [
                'loc'     => $siteUrl . '/blog/tag/' . $tag->slug,
                'lastmod' => '',
            ];
        }

        return $urls;
    }

    /**
     * Ids of content in a type whose robots directive carries noindex.
     *
     * One query for the whole type, instead of one query per item.
     *
     * @return array<int, true>
     */
    protected function noindexIds(string $contentType): array
    {
        $model = new SeoMeta($this->pdo);
        $ids = [];

        foreach ($model->findByContentType($contentType) as $meta) {
            if ($meta->isNoindex()) {
                $ids[(int) $meta->content_id] = true;
            }
        }

        return $ids;
    }

    /**
     * Newest lastmod among the entries, or '' when none carry a date.
     *
     * @param array<int, array{loc: string, lastmod: string}> $entries
     */
    protected function latestLastmod(array $entries): string
    {
        $latest = '';

        foreach ($entries as $entry) {
            if ($entry['lastmod'] !== '' && strcmp($entry['lastmod'], $latest) > 0) {
                $latest = $entry['lastmod'];
            }
        }

        return $latest;
    }

    /**
     * Build the XML string from URL entries.
     */
    /**
     * @param array<int, array{loc: string, lastmod: string}> $urls
     */
    protected function buildXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $entry) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                $xml .= '    <lastmod>' . $entry['lastmod'] . "</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    protected function formatDate(?string $datetime): string
    {
        if (empty($datetime)) {
            return date('Y-m-d');
        }
        $ts = strtotime($datetime);
        return $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
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
