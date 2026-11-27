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
     * Known AI crawlers with their default stance, purpose, and description.
     *
     * Discovery bots feed an answer engine's search index. Retrieval bots
     * fetch a page at a user's request. Training bots collect content for
     * model development. Search and training are separate decisions.
     *
     * @var array<string, array{stance: string, purpose: string, description: string}>
     */
    protected const AI_CRAWLERS = [
        'OAI-SearchBot' => [
            'stance'      => 'allow',
            'purpose'     => 'discovery',
            'description' => 'OpenAI search index for ChatGPT search results',
        ],
        'PerplexityBot' => [
            'stance'      => 'allow',
            'purpose'     => 'discovery',
            'description' => 'Perplexity search index for cited answers',
        ],
        'Claude-SearchBot' => [
            'stance'      => 'allow',
            'purpose'     => 'discovery',
            'description' => 'Anthropic search index for Claude web answers',
        ],
        'meta-webindexer' => [
            'stance'      => 'allow',
            'purpose'     => 'discovery',
            'description' => 'Meta AI search index for cited answers',
        ],
        'DuckAssistBot' => [
            'stance'      => 'allow',
            'purpose'     => 'discovery',
            'description' => 'DuckDuckGo crawler for AI-assisted answers',
        ],
        'ChatGPT-User' => [
            'stance'      => 'allow',
            'purpose'     => 'retrieval',
            'description' => 'Fetches a page when a ChatGPT user asks about it',
        ],
        'Claude-User' => [
            'stance'      => 'allow',
            'purpose'     => 'retrieval',
            'description' => 'Fetches a page when a Claude user asks about it',
        ],
        'Perplexity-User' => [
            'stance'      => 'allow',
            'purpose'     => 'retrieval',
            'description' => 'Fetches a page when a Perplexity user asks about it',
        ],
        'meta-externalfetcher' => [
            'stance'      => 'allow',
            'purpose'     => 'retrieval',
            'description' => 'Fetches a page when a Meta AI user asks about it',
        ],
        'MistralAI-User' => [
            'stance'      => 'allow',
            'purpose'     => 'retrieval',
            'description' => 'Fetches a page when a Mistral user asks about it',
        ],
        'facebookexternalhit' => [
            'stance'      => 'allow',
            'purpose'     => 'preview',
            'description' => 'Builds link previews when a URL is shared on Meta apps',
        ],
        'GPTBot' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'OpenAI model training',
        ],
        'ClaudeBot' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'Anthropic model training',
        ],
        'Google-Extended' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'Gemini training and grounding; does not affect Google Search',
        ],
        'CCBot' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'Common Crawl dataset used by many model trainers',
        ],
        'Bytespider' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'ByteDance and TikTok model training',
        ],
        'Applebot-Extended' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'Apple model training',
        ],
        'meta-externalagent' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'Meta model training',
        ],
        'AI2Bot' => [
            'stance'      => 'block',
            'purpose'     => 'training',
            'description' => 'Allen Institute for AI model training',
        ],
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

        foreach (self::AI_CRAWLERS as $bot => $info) {
            $settingKey = 'Seo.ai_crawler_' . $this->botToSettingKey($bot);
            $stance = $settings->get($settingKey) ?? $info['stance'];

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
     * @return array<string, array{bot: string, default: string, current: string, purpose: string, description: string}>
     */
    public function getAiCrawlerList(): array
    {
        $settings = $this->app->settings();
        $list = [];

        foreach (self::AI_CRAWLERS as $bot => $info) {
            $settingKey = 'Seo.ai_crawler_' . $this->botToSettingKey($bot);
            $current = $settings->get($settingKey) ?? $info['stance'];
            $list[$bot] = [
                'bot'         => $bot,
                'default'     => $info['stance'],
                'current'     => $current,
                'purpose'     => $info['purpose'],
                'description' => $info['description'],
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

    /**
     * Absolute site base URL from the SITE_URL deployment value via
     * UrlService::siteOrigin(); never the request Host header (AUDIT M4).
     */
    protected function getSiteUrl(): string
    {
        return $this->app->url()->siteOrigin();
    }
}
