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
     */
    public function sitemap(): void
    {
        $settings = $this->app->settings();

        if (!$settings->get('Seo.sitemap_enabled', true)) {
            $this->app->halt(404);
            return;
        }

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
     */
    public function llmsTxt(): void
    {
        $settings = $this->app->settings();

        if (!$settings->get('Seo.llms_txt_enabled', true)) {
            $this->app->halt(404);
            return;
        }

        $llms = $this->app->seoLlmsTxt();

        $this->app->response()->header('Content-Type', 'text/plain; charset=UTF-8');
        $this->app->halt(200, $llms->generate());
    }
}
