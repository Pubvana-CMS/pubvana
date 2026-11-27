<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Controllers;

use Pubvana\Plugins\AiAssistant\Controllers\AiApiController;

/**
 * SeoApiController - /api/ai/seo/* endpoints for the AI Assistant.
 *
 * Lets a key with the right grant read the SEO settings, change them, and
 * score a draft. Auth, grants, and the audit log come from AiApiController.
 * The SEO plugin registers these routes itself, the same way it registers
 * its admin routes.
 *
 * @package Pubvana\Plugins\Seo\Controllers
 */
class SeoApiController extends AiApiController
{
    /** Used when Config/Config.php does not set aiApiPrefix. */
    protected const DEFAULT_BASE = '/api/ai/seo';

    /**
     * Route base for this controller, from the plugin config.
     */
    protected function base(): string
    {
        $config = $this->app->get('pubvana.seo');
        $prefix = is_array($config) ? ($config['aiApiPrefix'] ?? self::DEFAULT_BASE) : self::DEFAULT_BASE;

        return rtrim((string) $prefix, '/');
    }

    /**
     * Refuse with 503 when the AI Assistant plugin is not loaded.
     *
     * The SEO routes are registered either way, so a client gets a plain
     * answer instead of a call to a service that is not mapped.
     */
    protected function guardAssistant(): void
    {
        try {
            $this->app->ai();
        } catch (\Throwable) {
            $this->fail(503, 'The AI Assistant is not available. Enable it to use the SEO API.');
        }
    }

    /**
     * GET /api/ai/seo/help
     *
     * The field guide for the SEO endpoints. Any valid key may read it,
     * granted or not, so an assistant can see what it is missing.
     */
    public function help(): void
    {
        $this->guardAssistant();
        $key = $this->requireKey();

        $base = $this->base();

        $this->log($key, 'ok', 'seo', null, 'SEO guide requested.');
        $this->ok([
            'base'      => $base,
            'endpoints' => [
                [
                    'grant'   => 'seo.read',
                    'method'  => 'GET',
                    'path'    => $base . '/settings',
                    'summary' => 'Every SEO setting plus the AI crawler stances.',
                ],
                [
                    'grant'   => 'seo.read',
                    'method'  => 'POST',
                    'path'    => $base . '/analyze',
                    'summary' => 'Score a draft against the SEO checks.',
                ],
                [
                    'grant'   => 'seo.manage',
                    'method'  => 'POST',
                    'path'    => $base . '/settings',
                    'summary' => 'Change SEO settings. Only the fields you send are written.',
                ],
            ],
            'fields'    => $this->app->seoSettings()->describe(),
            'crawlers'  => $this->app->seoRobots()->getAiCrawlerList(),
            'rules'     => [
                'Settings writes are partial. Omit a key to leave it alone.',
                'Booleans accept true/false, 1/0, "true"/"false", "yes"/"no", "on"/"off".',
                'ai_crawlers is a map of crawler name to allow or block. Unknown names and other values are ignored.',
                'analyze needs title or content. focus_keywords may be an array or a comma separated string. author and updated_at feed the AI readiness checks.',
                'Per-content SEO (meta title, description, canonical, robots, social, focus keywords) stays on the posts and pages endpoints, in the nested seo object.',
            ],
        ]);
    }

    /**
     * GET /api/ai/seo/settings
     */
    public function settings(): void
    {
        $this->guardAssistant();
        $key = $this->requireKey();
        $this->requireGrant($key, 'seo.read');

        $this->log($key, 'ok', 'seo', null, 'SEO settings read.');
        $this->ok([
            'settings' => $this->app->seoSettings()->all(),
            'crawlers' => $this->app->seoRobots()->getAiCrawlerList(),
        ]);
    }

    /**
     * POST /api/ai/seo/settings
     *
     * Accepts flat keys, or the shape the settings endpoint returns
     * ({"settings": {...}, "crawlers": {...}}), so a client can read, edit,
     * and send back the same document.
     */
    public function saveSettings(): void
    {
        $this->guardAssistant();
        $key = $this->requireKey();
        $this->requireGrant($key, 'seo.manage');

        $input = $this->flattenPayload($this->payload());
        if ($input === []) {
            $this->log($key, 'error', 'seo', null, 'Empty settings payload.');
            $this->fail(422, 'No settings provided. Send a JSON object of setting names to values.');
        }

        $written = $this->app->seoSettings()->saveApi($input);
        if ($written === []) {
            $this->log($key, 'error', 'seo', null, 'No recognized setting names.');
            $this->fail(422, 'No recognized setting names. GET ' . $this->base() . '/help for the field list.');
        }

        $this->log($key, 'ok', 'seo', null, 'SEO settings saved: ' . implode(', ', $written) . '.');
        $this->ok([
            'saved'    => $written,
            'settings' => $this->app->seoSettings()->all(),
            'crawlers' => $this->app->seoRobots()->getAiCrawlerList(),
        ]);
    }

    /**
     * POST /api/ai/seo/analyze
     *
     * POST because the article travels in the body, the same rule the
     * admin analyze endpoint follows.
     */
    public function analyze(): void
    {
        $this->guardAssistant();
        $key = $this->requireKey();
        $this->requireGrant($key, 'seo.read');

        $data = $this->payload();

        $title = is_scalar($data['title'] ?? null) ? trim((string) $data['title']) : '';
        $content = is_scalar($data['content'] ?? null) ? (string) $data['content'] : '';

        if ($title === '' && trim(strip_tags($content)) === '') {
            $this->log($key, 'error', 'seo', null, 'Analysis without title or content.');
            $this->fail(422, 'Send "title" or "content" to analyze.');
        }

        $data['title'] = $title;
        $data['content'] = $content;
        $data['focus_keywords'] = $this->keywordList($data['focus_keywords'] ?? []);
        $data['image_alts'] = $this->stringList($data['image_alts'] ?? []);
        $data['has_images'] = filter_var($data['has_images'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['author'] = is_scalar($data['author'] ?? null) ? trim((string) $data['author']) : '';
        $data['updated_at'] = is_scalar($data['updated_at'] ?? null) ? trim((string) $data['updated_at']) : '';

        $result = $this->app->seoAnalysis()->analyze($data);

        $this->log($key, 'ok', 'seo', null, 'SEO analysis scored ' . $result['score'] . '/100.');
        $this->ok($result);
    }

    /**
     * Merge the documented nested shape into one flat input map.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function flattenPayload(array $payload): array
    {
        $crawlers = $payload['crawlers'] ?? $payload['ai_crawlers'] ?? null;
        $settings = $payload['settings'] ?? null;

        unset($payload['settings'], $payload['crawlers']);

        if (is_array($settings)) {
            $payload = array_merge($payload, $settings);
        }

        if (is_array($crawlers)) {
            $payload['ai_crawlers'] = $crawlers;
        }

        return $payload;
    }

    /**
     * Focus keywords as a clean list, from an array or a CSV string.
     *
     * @return list<string>
     */
    protected function keywordList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return $this->stringList($value);
    }

    /**
     * Trimmed non-empty strings from an array.
     *
     * @return list<string>
     */
    protected function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }
}
