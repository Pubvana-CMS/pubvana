<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use flight\Engine;

/**
 * robots.txt generation with AI crawler management.
 *
 * Supports per-bot allow/block toggles for AI crawlers alongside
 * traditional robots.txt directives.
 */
class RobotsTxtService
{
    /** @var Engine<object> */
    protected Engine $app;

    /**
     * Known AI crawlers with their default stance.
     * 'block' = training bots (default block)
     * 'allow' = retrieval/citation bots (default allow)
     */
    protected const AI_CRAWLERS = [
        'GPTBot'             => 'block',
        'ChatGPT-User'       => 'allow',
        'Google-Extended'    => 'block',
        'ClaudeBot'          => 'block',
        'Claude-SearchBot'   => 'allow',
        'CCBot'              => 'block',
        'PerplexityBot'      => 'allow',
        'Bytespider'         => 'block',
        'Applebot-Extended'  => 'block',
        'FacebookBot'        => 'block',
        'anthropic-ai'       => 'block',
        'cohere-ai'          => 'block',
    ];

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Generate the full robots.txt content.
     */
    public function generate(): string
    {
        $settings = $this->app->settings();
        $lines = [];

        // Custom robots.txt body from settings (user-edited)
        $customBody = $settings->get('Seo.robots_txt_custom');
        if (!empty($customBody)) {
            $lines[] = $customBody;
            $lines[] = '';
        } else {
            // Default general rules
            $lines[] = 'User-agent: *';
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin/';
            $lines[] = '';
        }

        // AI crawler directives
        $aiLines = $this->buildAiCrawlerDirectives();
        if (!empty($aiLines)) {
            $lines[] = '# AI Crawler Directives';
            $lines = array_merge($lines, $aiLines);
            $lines[] = '';
        }

        // Sitemap reference
        $siteUrl = $this->getSiteUrl();
        $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Build AI crawler allow/block directives from settings.
     */
    /**
     * @return array<int, string>
     */
    protected function buildAiCrawlerDirectives(): array
    {
        $settings = $this->app->settings();
        $lines = [];

        foreach (self::AI_CRAWLERS as $bot => $defaultStance) {
            $settingKey = 'Seo.ai_crawler_' . $this->botToSettingKey($bot);
            $stance = $settings->get($settingKey) ?? $defaultStance;

            if ($stance === 'block') {
                $lines[] = 'User-agent: ' . $bot;
                $lines[] = 'Disallow: /';
                $lines[] = '';
            }
        }

        return $lines;
    }

    /**
     * Get the list of known AI crawlers with their current stance.
     *
     * @return array<string, array{bot: string, default: string, current: string, description: string}>
     */
    public function getAiCrawlerList(): array
    {
        $settings = $this->app->settings();
        $descriptions = [
            'GPTBot'            => 'OpenAI uses your content to train their AI models',
            'ChatGPT-User'     => 'ChatGPT shows your content in answers when users ask questions',
            'Google-Extended'   => 'Google uses your content to train Gemini AI',
            'ClaudeBot'         => 'Anthropic uses your content to train Claude AI',
            'Claude-SearchBot'  => 'Claude shows your content in answers when users ask questions',
            'CCBot'             => 'Common Crawl collects web data used by many AI companies for training',
            'PerplexityBot'     => 'Perplexity shows your content in search answers',
            'Bytespider'        => 'ByteDance/TikTok uses your content to train their AI',
            'Applebot-Extended' => 'Apple uses your content to train Apple AI',
            'FacebookBot'       => 'Meta uses your content to train their AI models',
            'anthropic-ai'      => 'Older Anthropic training crawler (replaced by ClaudeBot)',
            'cohere-ai'         => 'Cohere uses your content to train their AI models',
        ];

        $list = [];
        foreach (self::AI_CRAWLERS as $bot => $defaultStance) {
            $settingKey = 'Seo.ai_crawler_' . $this->botToSettingKey($bot);
            $current = $settings->get($settingKey) ?? $defaultStance;
            $list[$bot] = [
                'bot'         => $bot,
                'default'     => $defaultStance,
                'current'     => $current,
                'description' => $descriptions[$bot],
            ];
        }

        return $list;
    }

    /**
     * Convert bot name to settings key format.
     */
    protected function botToSettingKey(string $bot): string
    {
        return strtolower(str_replace(['-', ' '], '_', $bot));
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
