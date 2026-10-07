<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SocialLinks\Services;

use Pubvana\Plugins\SocialLinks\Models\SocialLink;
use Pubvana\Services\UrlService;

/**
 * Social Links Service
 *
 * The $app->socialLinks() facade. Owns the platform catalog and all
 * reads and writes against the social_links table, plus the public
 * block provider consumed by the region renderer.
 *
 * @package Pubvana\Plugins\SocialLinks
 */
class SocialLinksService
{
    /**
     * Platform catalog: key => display label and Font Awesome class.
     * Every class is a Font Awesome 7 Free brand mark (verified in
     * brands.min.css); keys outside this map fall back to "custom".
     *
     * @var array<string, array{label: string, icon: string}>
     */
    private const PLATFORMS = [
        'facebook'   => ['label' => 'Facebook',    'icon' => 'fa-brands fa-facebook'],
        'x'          => ['label' => 'X',           'icon' => 'fa-brands fa-x-twitter'],
        'threads'    => ['label' => 'Threads',     'icon' => 'fa-brands fa-threads'],
        'bluesky'    => ['label' => 'Bluesky',     'icon' => 'fa-brands fa-bluesky'],
        'instagram'  => ['label' => 'Instagram',   'icon' => 'fa-brands fa-instagram'],
        'youtube'    => ['label' => 'YouTube',     'icon' => 'fa-brands fa-youtube'],
        'linkedin'   => ['label' => 'LinkedIn',    'icon' => 'fa-brands fa-linkedin'],
        'pinterest'  => ['label' => 'Pinterest',   'icon' => 'fa-brands fa-pinterest'],
        'tiktok'     => ['label' => 'TikTok',      'icon' => 'fa-brands fa-tiktok'],
        'snapchat'   => ['label' => 'Snapchat',    'icon' => 'fa-brands fa-snapchat'],
        'reddit'     => ['label' => 'Reddit',      'icon' => 'fa-brands fa-reddit'],
        'discord'    => ['label' => 'Discord',     'icon' => 'fa-brands fa-discord'],
        'twitch'     => ['label' => 'Twitch',      'icon' => 'fa-brands fa-twitch'],
        'github'     => ['label' => 'GitHub',      'icon' => 'fa-brands fa-github'],
        'gitlab'     => ['label' => 'GitLab',      'icon' => 'fa-brands fa-gitlab'],
        'whatsapp'   => ['label' => 'WhatsApp',    'icon' => 'fa-brands fa-whatsapp'],
        'telegram'   => ['label' => 'Telegram',    'icon' => 'fa-brands fa-telegram'],
        'mastodon'   => ['label' => 'Mastodon',    'icon' => 'fa-brands fa-mastodon'],
        'tumblr'     => ['label' => 'Tumblr',      'icon' => 'fa-brands fa-tumblr'],
        'vimeo'      => ['label' => 'Vimeo',       'icon' => 'fa-brands fa-vimeo'],
        'flickr'     => ['label' => 'Flickr',      'icon' => 'fa-brands fa-flickr'],
        'dribbble'   => ['label' => 'Dribbble',    'icon' => 'fa-brands fa-dribbble'],
        'behance'    => ['label' => 'Behance',     'icon' => 'fa-brands fa-behance'],
        'medium'     => ['label' => 'Medium',      'icon' => 'fa-brands fa-medium'],
        'spotify'    => ['label' => 'Spotify',     'icon' => 'fa-brands fa-spotify'],
        'soundcloud' => ['label' => 'SoundCloud',  'icon' => 'fa-brands fa-soundcloud'],
        'slack'      => ['label' => 'Slack',       'icon' => 'fa-brands fa-slack'],
        'skype'      => ['label' => 'Skype',       'icon' => 'fa-brands fa-skype'],
        'steam'      => ['label' => 'Steam',       'icon' => 'fa-brands fa-steam'],
        'patreon'    => ['label' => 'Patreon',     'icon' => 'fa-brands fa-patreon'],
        'paypal'     => ['label' => 'PayPal',      'icon' => 'fa-brands fa-paypal'],
        'wordpress'  => ['label' => 'WordPress',   'icon' => 'fa-brands fa-wordpress'],
        'kickstarter'=> ['label' => 'Kickstarter', 'icon' => 'fa-brands fa-kickstarter'],
        'etsy'       => ['label' => 'Etsy',        'icon' => 'fa-brands fa-etsy'],
        'bandcamp'   => ['label' => 'Bandcamp',    'icon' => 'fa-brands fa-bandcamp'],
        'signal'     => ['label' => 'Signal',      'icon' => 'fa-brands fa-signal-messenger'],
        'weibo'      => ['label' => 'Weibo',       'icon' => 'fa-brands fa-weibo'],
        'blogger'    => ['label' => 'Blogger',     'icon' => 'fa-brands fa-blogger'],
        'appstore'   => ['label' => 'App Store',   'icon' => 'fa-brands fa-app-store'],
        'itch'       => ['label' => 'itch.io',     'icon' => 'fa-brands fa-itch-io'],
        'android'    => ['label' => 'Android',     'icon' => 'fa-brands fa-android'],
        'apple'      => ['label' => 'Apple',       'icon' => 'fa-brands fa-apple'],
    ];

    /** @var list<string> Font Awesome family and style class tokens. */
    private const FA_FAMILY_TOKENS = ['brands', 'solid', 'regular', 'classic', 'fas', 'far', 'fab'];

    /**
     * Class names defined by the staged stylesheets, keyed without the
     * "fa-" prefix. A true value marks a glyph (a --fa rule), false marks
     * any other fa- class. Null when the stylesheets could not be read.
     *
     * @var array<string, bool>|null
     */
    private static ?array $faClasses = null;

    /** Whether the staged stylesheet class list has been read from disk. */
    private static bool $faClassesLoaded = false;

    private \PDO $pdo;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param \PDO                 $pdo    Active database connection.
     * @param array<string, mixed> $config Plugin configuration.
     */
    public function __construct(\PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /**
     * The full platform catalog.
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public static function platforms(): array
    {
        return self::PLATFORMS;
    }

    /**
     * Platform key => label, plus "custom" as the final option.
     *
     * @return array<string, string>
     */
    public function platformOptions(): array
    {
        $options = [];
        foreach (self::PLATFORMS as $key => $definition) {
            $options[$key] = $definition['label'];
        }
        $options['custom'] = 'Custom';
        return $options;
    }

    public function platformLabel(string $key): string
    {
        return self::PLATFORMS[$key]['label'] ?? (string) ($this->config['fallback_label'] ?? 'Website');
    }

    public function platformIcon(string $key): string
    {
        return self::PLATFORMS[$key]['icon'] ?? (string) ($this->config['fallback_icon'] ?? 'fa-solid fa-link');
    }

    /**
     * Every link, ordered for admin display and reordering.
     *
     * @return SocialLink[]
     */
    public function all(): array
    {
        return $this->model()->allOrdered();
    }

    /**
     * Active links only, ordered for public display.
     *
     * @return SocialLink[]
     */
    public function activeLinks(): array
    {
        return $this->model()->activeOrdered();
    }

    public function countActive(): int
    {
        return count($this->activeLinks());
    }

    public function find(int $id): ?SocialLink
    {
        return $this->model()->findById($id);
    }

    /**
     * Create a social link from validated form data.
     *
     * Known platforms resolve their own label and icon; "custom" takes
     * the posted label and Font Awesome class. Returns null when the
     * posted URL is missing or is not already a full http(s) URL: the law
     * is store-what-you-emit, so no scheme is assumed or prepended.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): ?SocialLink
    {
        $fields = $this->normalizeFields($data);
        if ($fields === null) {
            return null;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $link = $this->model();
        $link->platform = $fields['platform'];
        $link->label = $fields['label'];
        $link->url = $fields['url'];
        $link->icon = $fields['icon'];
        // One past the highest value in use, not the row count: a deletion
        // leaves a gap, and a count would land on a value another row already
        // holds. A new link must sort strictly last, so it must be strictly
        // greater than every existing one.
        $link->sort_order = $this->nextSortOrder();
        $link->is_active = 1;
        $link->created_at = $now;
        $link->updated_at = $now;
        $link->insert();

        return $link;
    }

    /**
     * Update an existing link from validated form data.
     *
     * Returns null when the link is gone, or when the posted URL is
     * missing or not already a full http(s) URL. A known platform resets
     * its label and icon from the catalog; "custom" keeps the posted
     * values. Order and active state are untouched.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): ?SocialLink
    {
        $link = $this->find($id);
        if ($link === null) {
            return null;
        }

        $fields = $this->normalizeFields($data);
        if ($fields === null) {
            return null;
        }

        $link->platform = $fields['platform'];
        $link->label = $fields['label'];
        $link->url = $fields['url'];
        $link->icon = $fields['icon'];
        $link->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $link->save();

        return $link;
    }

    public function toggle(int $id): bool
    {
        $link = $this->find($id);
        if ($link === null) {
            return false;
        }
        $link->is_active = (int) $link->is_active === 1 ? 0 : 1;
        $link->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $link->save();
        return true;
    }

    public function delete(int $id): bool
    {
        $link = $this->find($id);
        if ($link === null) {
            return false;
        }
        $link->delete();
        return true;
    }

    /**
     * Move a link one spot up or down and re-normalize the order.
     */
    public function move(int $id, string $direction): bool
    {
        $all = $this->all();
        $position = -1;
        foreach ($all as $index => $link) {
            if ((int) $link->id === $id) {
                $position = $index;
                break;
            }
        }
        if ($position < 0) {
            return false;
        }

        if ($direction === 'up') {
            $target = $position - 1;
        } elseif ($direction === 'down') {
            $target = $position + 1;
        } else {
            return false;
        }
        if (!isset($all[$target])) {
            return false;
        }

        $link = $all[$position];
        $all[$position] = $all[$target];
        $all[$target] = $link;
        $this->persistOrder($all);
        return true;
    }

    /**
     * Provider for the public "Social Links" block.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function socialLinksBlock(array $options): array
    {
        $links = [];
        foreach ($this->activeLinks() as $link) {
            $links[] = [
                'label' => (string) $link->label,
                'url'   => (string) $link->url,
                'icon'  => (string) $link->icon,
            ];
        }

        $title = trim((string) ($options['title'] ?? ''));
        if ($title === '') {
            $title = (string) ($this->config['block_title'] ?? 'Follow Us');
        }

        return [
            'title'  => $title,
            'links'  => $links,
            'target' => (string) ($this->config['default_target'] ?? '_blank'),
            'rel'    => (string) ($this->config['link_rel'] ?? 'noopener noreferrer'),
        ];
    }

    private function model(): SocialLink
    {
        return new SocialLink($this->pdo);
    }

    /**
     * Re-assign sequential sort_order values so the admin order maps
     * cleanly to display order.
     *
     * @param SocialLink[] $links
     */
    private function persistOrder(array $links): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        foreach ($links as $index => $link) {
            if ((int) $link->sort_order !== $index) {
                $link->sort_order = $index;
                $link->updated_at = $now;
                $link->save();
            }
        }
    }

    /**
     * The next sort_order a new link should take: one past the highest value
     * in use, or 0 for an empty table.
     */
    private function nextSortOrder(): int
    {
        $highest = -1;
        foreach ($this->all() as $link) {
            $highest = max($highest, (int) $link->sort_order);
        }

        return $highest + 1;
    }

    /**
     * Normalize posted form data into the four stored columns.
     *
     * Known platforms derive label and icon from the catalog. "custom"
     * takes the posted label and Font Awesome class, each with a config
     * fallback. Returns null when the URL is missing or is not already a
     * full http(s) URL: the law is store-what-you-emit, so no scheme is
     * assumed or prepended.
     *
     * @param array<string, mixed> $data
     * @return array{platform: string, label: string, url: string, icon: string}|null
     */
    private function normalizeFields(array $data): ?array
    {
        $platform = $this->normalizePlatform((string) ($data['platform'] ?? ''));
        $url = $this->normalizeUrl((string) ($data['url'] ?? ''));
        if ($url === null) {
            return null;
        }

        $isCustom = $platform === 'custom';

        return [
            'platform' => $platform,
            'label'    => $isCustom
                ? $this->normalizeText((string) ($data['label'] ?? ''), (string) ($this->config['fallback_label'] ?? 'Website'), 100)
                : $this->platformLabel($platform),
            'url'      => $url,
            'icon'     => $isCustom
                ? $this->normalizeIcon((string) ($data['icon'] ?? ''), (string) ($this->config['fallback_icon'] ?? 'fa-solid fa-link'))
                : $this->platformIcon($platform),
        ];
    }

    private function normalizePlatform(string $key): string
    {
        $key = strtolower(trim($key));
        return isset(self::PLATFORMS[$key]) ? $key : 'custom';
    }

    private function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || !UrlService::isSafeExternalUrl($url)) {
            return null;
        }

        return mb_substr($url, 0, 500);
    }

    private function normalizeText(string $value, string $fallback, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        return mb_substr($value, 0, $maxLength);
    }

    private function normalizeIcon(string $icon, string $fallback): string
    {
        $icon = trim($icon);
        if ($icon === '' || preg_match('#^fa-[a-z0-9]+( [a-z0-9-]+)*$#i', $icon) !== 1) {
            return $fallback;
        }
        if (!$this->isKnownIcon($icon)) {
            return $fallback;
        }
        return mb_substr($icon, 0, 100);
    }

    /**
     * True when every token in the icon is a Font Awesome class the staged
     * stylesheets define, with at least one family token and one glyph.
     *
     * A syntactically valid but unknown class (a typo like fa-twiter)
     * renders an empty box, so it falls back instead of storing a class
     * that shows nothing. When the stylesheets cannot be read the check
     * passes, so a missing asset never blanks a working icon.
     */
    private function isKnownIcon(string $icon): bool
    {
        $classes = $this->stagedFaClasses();
        if ($classes === null) {
            return true;
        }

        $tokens = preg_split('/\s+/', $icon);
        if ($tokens === false || $tokens === []) {
            return false;
        }

        $hasFamily = false;
        $hasGlyph = false;
        foreach ($tokens as $token) {
            $name = str_starts_with($token, 'fa-') ? substr($token, 3) : $token;
            if (in_array($name, self::FA_FAMILY_TOKENS, true)) {
                $hasFamily = true;
                continue;
            }
            if (!array_key_exists($name, $classes)) {
                return false;
            }
            if ($classes[$name]) {
                $hasGlyph = true;
            }
        }

        return $hasFamily && $hasGlyph;
    }

    /**
     * Font Awesome class names defined by the plugin's staged stylesheets.
     *
     * Keys are names without the "fa-" prefix. A true value marks a glyph
     * (a --fa rule), false marks any other fa- class such as a family or
     * sizing utility. Null when no stylesheet could be read, so the caller
     * skips the check.
     *
     * @return array<string, bool>|null
     */
    private function stagedFaClasses(): ?array
    {
        if (self::$faClassesLoaded) {
            return self::$faClasses;
        }
        self::$faClassesLoaded = true;

        $dir = dirname(__DIR__) . '/assets/css';
        $classes = [];
        $read = 0;

        foreach (['fontawesome.min.css', 'brands.min.css', 'solid.min.css'] as $file) {
            $path = $dir . '/' . $file;
            if (!is_file($path)) {
                continue;
            }
            $css = file_get_contents($path);
            if ($css === false) {
                continue;
            }
            $read++;

            // Every fa- class, marked as a non-glyph until a --fa rule
            // proves otherwise.
            if (preg_match_all('#\.fa-([a-z0-9-]+)#', $css, $matches) > 0) {
                foreach ($matches[1] as $name) {
                    $classes[$name] ??= false;
                }
            }

            // Names that define a glyph, including alias selector lists
            // such as ".fa-signal,.fa-signal-5{--fa:"\f012"}".
            if (preg_match_all('#([^{}]+)\{--fa:#', $css, $rules) > 0) {
                foreach ($rules[1] as $selector) {
                    if (preg_match_all('#\.fa-([a-z0-9-]+)#', $selector, $names) > 0) {
                        foreach ($names[1] as $name) {
                            $classes[$name] = true;
                        }
                    }
                }
            }
        }

        self::$faClasses = $read === 0 ? null : $classes;

        return self::$faClasses;
    }
}