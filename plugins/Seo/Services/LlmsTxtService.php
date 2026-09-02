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
        $siteDescription = $settings->get('CMS.siteByline') ?? '';
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
        $pages = $this->app->pages()->listPublished();
        $seoModel = new SeoMeta($this->pdo);
        $result = [];

        foreach (array_slice($pages, 0, 50) as $page) {
            $meta = $seoModel->findByContent('page', (int) $page->id);
            if ($meta && $meta->isNoindex()) {
                continue;
            }
            $result[] = [
                'title'            => $page->title,
                'slug'             => $page->slug,
                'meta_description' => $meta->meta_description ?? '',
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getPublishedPosts(): array
    {
        $posts = $this->app->blog()->listPosts(1, 50, 'published');
        $seoModel = new SeoMeta($this->pdo);
        $result = [];

        foreach ($posts['items'] as $post) {
            $meta = $seoModel->findByContent('post', (int) $post->id);
            if ($meta && $meta->isNoindex()) {
                continue;
            }
            $result[] = [
                'title'            => $post->title,
                'slug'             => $post->slug,
                'meta_description' => $meta->meta_description ?? '',
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getCategories(): array
    {
        $categories = $this->app->blog()->listCategories();
        $result = [];

        foreach ($categories as $cat) {
            $result[] = [
                'name' => $cat->name,
                'slug' => $cat->slug,
            ];
        }

        return $result;
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
