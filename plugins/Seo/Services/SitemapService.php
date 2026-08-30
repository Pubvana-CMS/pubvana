<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use Pubvana\Plugins\Seo\Models\SeoMeta;
use flight\Engine;

/**
 * XML Sitemap generation.
 *
 * Only includes canonical, published, indexable URLs.
 * Only uses <lastmod> — Google/Bing ignore <changefreq> and <priority>.
 */
class SitemapService
{
    protected \PDO $pdo;
    protected Engine $app;

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
        $urls = [];

        // Homepage
        $urls[] = [
            'loc'     => $siteUrl . '/',
            'lastmod' => date('Y-m-d'),
        ];

        // Pages
        if ($settings->get('Seo.sitemap_include_pages', true)) {
            $urls = array_merge($urls, $this->getPageUrls($siteUrl));
        }

        // Posts
        if ($settings->get('Seo.sitemap_include_posts', true)) {
            $urls = array_merge($urls, $this->getPostUrls($siteUrl));
        }

        // Category/tag archives
        $urls = array_merge($urls, $this->getCategoryUrls($siteUrl));
        $urls = array_merge($urls, $this->getTagUrls($siteUrl));

        return $this->buildXml($urls);
    }

    /**
     * Get published page URLs, excluding noindex pages.
     */
    protected function getPageUrls(string $siteUrl): array
    {
        $pages = $this->app->pages()->listPublished();
        $seoModel = new SeoMeta($this->pdo);
        $urls = [];

        foreach ($pages as $page) {
            $meta = $seoModel->findByContent('page', (int) $page->id);
            if ($meta && $meta->isNoindex()) {
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
    protected function getPostUrls(string $siteUrl): array
    {
        $result = $this->app->blog()->listPosts(1, 1000, 'published');
        $seoModel = new SeoMeta($this->pdo);
        $urls = [];

        foreach ($result['items'] as $post) {
            $meta = $seoModel->findByContent('post', (int) $post->id);
            if ($meta && $meta->isNoindex()) {
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
    protected function getCategoryUrls(string $siteUrl): array
    {
        $categories = $this->app->blog()->listCategories();
        $urls = [];

        foreach ($categories as $cat) {
            $urls[] = [
                'loc'     => $siteUrl . '/blog/category/' . $cat->slug,
                'lastmod' => date('Y-m-d'),
            ];
        }

        return $urls;
    }

    /**
     * Get tag archive URLs.
     */
    protected function getTagUrls(string $siteUrl): array
    {
        $tags = $this->app->blog()->listTags();
        $urls = [];

        foreach ($tags as $tag) {
            $urls[] = [
                'loc'     => $siteUrl . '/blog/tag/' . $tag->slug,
                'lastmod' => date('Y-m-d'),
            ];
        }

        return $urls;
    }

    /**
     * Build the XML string from URL entries.
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
        return date('Y-m-d', strtotime($datetime));
    }

    protected function getSiteUrl(): string
    {
        $settings = $this->app->settings();
        $siteUrl = $settings->get('CMS.siteUrl');
        if (!empty($siteUrl)) {
            return rtrim($siteUrl, '/');
        }

        $request = $this->app->request();
        $scheme = $request->secure ? 'https' : 'http';
        $host = $request->host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host;
    }
}
