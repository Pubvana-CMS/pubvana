<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Controllers;

use flight\Engine;

/**
 * Public controller for SEO endpoints: sitemap.xml, robots.txt, llms.txt
 */
class SeoPublicController
{
    /** @var Engine<object> */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Serve the XML sitemap.
     *
     * Always served: the sitemap is a permanent crawl surface, and
     * robots.txt references it unconditionally.
     */
    public function sitemap(): void
    {
        $sitemap = $this->app->seoSitemap();

        $this->app->response()->header('Content-Type', 'application/xml; charset=UTF-8');
        $this->app->response()->header('X-Robots-Tag', 'noindex');
        $this->app->halt(200, $sitemap->generate());
    }

    /**
     * Serve robots.txt.
     */
    public function robotsTxt(): void
    {
        $robots = $this->app->seoRobots();

        $this->app->response()->header('Content-Type', 'text/plain; charset=UTF-8');
        $this->app->halt(200, $robots->generate());
    }

    /**
     * Serve llms.txt for AI crawlers.
     *
     * Always served; there is no on/off switch for the fetch files.
     */
    public function llmsTxt(): void
    {
        $llms = $this->app->seoLlmsTxt();

        $this->app->response()->header('Content-Type', 'text/plain; charset=UTF-8');
        $this->app->halt(200, $llms->generate());
    }
}
