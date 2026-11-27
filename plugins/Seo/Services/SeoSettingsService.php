<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Seo\Services;

use flight\Engine;

/**
 * Read and write the Seo.* settings.
 *
 * The field lists live here once. Two write methods sit on top of them:
 * saveAdminForm() keeps the settings form's behavior exactly as it was,
 * and saveApi() normalizes input for the AI Assistant API. Both paths
 * read through all(), so the admin screen and the API see the same data.
 */
class SeoSettingsService
{
    /**
     * String settings and the maximum length each one stores.
     *
     * @var array<string, int>
     */
    protected const STRING_LIMITS = [
        'title_separator'     => 10,
        'title_template'      => 255,
        'default_og_image'    => 2048,
        'default_language'    => 20,
        'organization_name'   => 255,
        'organization_description' => 500,
        'organization_contact_email' => 255,
        'organization_logo'   => 2048,
        'verification_google' => 255,
        'verification_bing'   => 255,
        'robots_txt_custom'   => 65535,
    ];

    /**
     * Boolean settings and their defaults.
     *
     * @var array<string, bool>
     */
    protected const BOOL_DEFAULTS = [
        'sitemap_include_pages'    => true,
        'sitemap_include_posts'    => true,
        'sitemap_include_archives' => true,
        'llms_txt_include_pages'   => true,
        'llms_txt_include_posts'   => true,
        'llms_txt_include_categories' => true,
        'ai_disclosure_enabled'    => true,
    ];

    /**
     * String defaults, read through settings()->get().
     *
     * @var array<string, string>
     */
    protected const STRING_DEFAULTS = [
        'title_separator'     => '|',
        'title_template'      => '{title} {sep} {site_name}',
        'default_og_image'    => '',
        'default_language'    => 'en',
        'organization_name'   => '',
        'organization_description' => '',
        'organization_contact_email' => '',
        'organization_logo'   => '',
        'verification_google' => '',
        'verification_bing'   => '',
        'robots_txt_custom'   => '',
    ];

    /** Upper bound on the social profile URLs one site may store. */
    protected const MAX_SOCIAL_PROFILES = 20;

    /** Maximum stored length of one social profile URL. */
    protected const MAX_SOCIAL_PROFILE_LENGTH = 2048;

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
     * Every setting with its current value, in the shape the admin form and
     * the API both return.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $settings = $this->app->settings();
        $values = [];

        foreach (self::STRING_DEFAULTS as $key => $default) {
            $values[$key] = $settings->get('Seo.' . $key, $default);
        }

        foreach (self::BOOL_DEFAULTS as $key => $default) {
            $values[$key] = (bool) $settings->get('Seo.' . $key, $default);
        }

        $profiles = $settings->get('Seo.social_profiles', []);
        $values['social_profiles'] = is_array($profiles) ? array_values($profiles) : [];

        return $values;
    }

    /**
     * Write the settings posted by the admin form.
     *
     * Strings are stored as posted, every boolean key is written (an
     * unticked checkbox is absent from POST and becomes false), and any
     * crawler name in the map is stored. This is the behavior the settings
     * screen has always had.
     *
     * @param array<string, mixed> $input
     */
    public function saveAdminForm(array $input): void
    {
        $settings = $this->app->settings();

        foreach (self::STRING_DEFAULTS as $key => $default) {
            if (array_key_exists($key, $input)) {
                $settings->set('Seo.' . $key, $input[$key]);
            }
        }

        foreach (self::BOOL_DEFAULTS as $key => $default) {
            $settings->set('Seo.' . $key, $this->toBool($input[$key] ?? false));
        }

        if (isset($input['social_profiles'])) {
            $profiles = array_filter(array_map('trim', explode("\n", (string) $input['social_profiles'])));
            $settings->set('Seo.social_profiles', array_values($profiles));
        }

        if (isset($input['ai_crawlers']) && is_array($input['ai_crawlers'])) {
            foreach ($input['ai_crawlers'] as $bot => $stance) {
                $settings->set(
                    'Seo.ai_crawler_' . $this->crawlerKey((string) $bot),
                    $stance === 'allow' ? 'allow' : 'block'
                );
            }
        }
    }

    /**
     * Write the settings present in $input for the API. Omitted keys keep
     * their value.
     *
     * Unknown keys are ignored. Strings are trimmed and capped, booleans
     * accept true/false and the usual form values, social_profiles accepts
     * an array or a newline/comma separated string, and ai_crawlers accepts
     * a bot => stance map for the crawlers RobotsTxtService knows.
     *
     * @param array<string, mixed> $input
     * @return list<string> Names of the settings that were written.
     */
    public function saveApi(array $input): array
    {
        $settings = $this->app->settings();
        $written = [];

        foreach (self::STRING_LIMITS as $key => $limit) {
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $value = $input[$key];
            if (!is_scalar($value)) {
                continue;
            }

            $settings->set('Seo.' . $key, mb_substr(trim((string) $value), 0, $limit));
            $written[] = $key;
        }

        foreach (self::BOOL_DEFAULTS as $key => $default) {
            if (!array_key_exists($key, $input)) {
                continue;
            }

            $settings->set('Seo.' . $key, $this->toBool($input[$key]));
            $written[] = $key;
        }

        if (array_key_exists('social_profiles', $input)) {
            $settings->set('Seo.social_profiles', $this->normalizeProfiles($input['social_profiles']));
            $written[] = 'social_profiles';
        }

        if (array_key_exists('ai_crawlers', $input)) {
            $stances = $this->normalizeCrawlers($input['ai_crawlers']);
            foreach ($stances as $bot => $stance) {
                $settings->set('Seo.ai_crawler_' . $this->crawlerKey($bot), $stance);
            }

            if ($stances !== []) {
                $written[] = 'ai_crawlers';
            }
        }

        return $written;
    }

    /**
     * Field names, types, defaults, and notes for the API help document.
     *
     * @return array<string, array{type: string, default: mixed, note: string}>
     */
    public function describe(): array
    {
        $fields = [];

        foreach (self::STRING_LIMITS as $key => $limit) {
            $fields[$key] = [
                'type'    => 'string',
                'default' => self::STRING_DEFAULTS[$key],
                'note'    => 'Maximum ' . $limit . ' characters.',
            ];
        }

        foreach (self::BOOL_DEFAULTS as $key => $default) {
            $fields[$key] = [
                'type'    => 'boolean',
                'default' => $default,
                'note'    => 'true or false.',
            ];
        }

        $fields['social_profiles'] = [
            'type'    => 'array',
            'default' => [],
            'note'    => 'Profile URLs. Send an array or a newline/comma separated string. Up to '
                . self::MAX_SOCIAL_PROFILES . ' are kept.',
        ];

        $fields['ai_crawlers'] = [
            'type'    => 'object',
            'default' => [],
            'note'    => 'Map of crawler name to allow or block. Only crawlers listed by the settings endpoint are accepted.',
        ];

        return $fields;
    }

    /**
     * Trim, drop blanks, cap the length, and cap the count.
     *
     * @return list<string>
     */
    protected function normalizeProfiles(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $profiles = [];
        foreach ($value as $profile) {
            if (!is_scalar($profile)) {
                continue;
            }

            $profile = mb_substr(trim((string) $profile), 0, self::MAX_SOCIAL_PROFILE_LENGTH);
            if ($profile !== '') {
                $profiles[] = $profile;
            }
        }

        return array_slice($profiles, 0, self::MAX_SOCIAL_PROFILES);
    }

    /**
     * Known bot => stance. Unknown bots and unrecognized stances are dropped
     * so a typo cannot silently block a crawler.
     *
     * @return array<string, string>
     */
    protected function normalizeCrawlers(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        try {
            $known = $this->app->seoRobots()->getAiCrawlerList();
        } catch (\Throwable) {
            return [];
        }

        $stances = [];
        foreach ($value as $bot => $stance) {
            $bot = (string) $bot;
            if (!isset($known[$bot]) || !is_scalar($stance)) {
                continue;
            }

            $stance = strtolower(trim((string) $stance));
            if ($stance === 'allow' || $stance === 'block') {
                $stances[$bot] = $stance;
            }
        }

        return $stances;
    }

    /**
     * Settings key suffix for a crawler name, matching the admin form.
     */
    protected function crawlerKey(string $bot): string
    {
        return strtolower(str_replace(['-', ' '], '_', $bot));
    }

    /**
     * Accept the form values a checkbox and a JSON client send.
     */
    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
