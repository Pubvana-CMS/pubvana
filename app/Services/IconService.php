<?php

namespace App\Services;

class IconService
{
    /**
     * Base CSS class for brand/logo icons per pack and major version.
     */
    private static array $baseClasses = [
        'FontAwesome' => [
            '7' => 'fa-brands',
            '6' => 'fa-brands',
            '5' => 'fab',
        ],
        'BootstrapIcons' => [
            '1' => 'bi',
        ],
        'RemixIcon' => [
            '4' => 'ri',
        ],
        'Boxicons' => [
            '2' => 'bx',
        ],
        'TablerIcons' => [
            '3' => 'ti',
        ],
        'PhosphorIcons' => [
            '2' => 'ph',
        ],
        'Lineicons' => [
            '4' => 'lni',
        ],
    ];

    /**
     * Icon pack mappings: pack → major_version → platform_key → css_class
     *
     * Platform keys are lowercase labels matching the admin icon picker.
     * To add a new icon pack, add a new top-level key with version sub-keys.
     * Version keys are major version only (e.g., "6" not "6.x" or "6.2.14").
     */
    private static array $packs = [
        'FontAwesome' => [
            '7' => [
                'facebook'   => 'fa-brands fa-facebook',
                'messenger'  => 'fa-brands fa-facebook-messenger',
                'x'          => 'fa-brands fa-x-twitter',
                'instagram'  => 'fa-brands fa-instagram',
                'youtube'    => 'fa-brands fa-youtube',
                'linkedin'   => 'fa-brands fa-linkedin',
                'pinterest'  => 'fa-brands fa-pinterest',
                'tiktok'     => 'fa-brands fa-tiktok',
                'snapchat'   => 'fa-brands fa-snapchat',
                'reddit'     => 'fa-brands fa-reddit',
                'discord'    => 'fa-brands fa-discord',
                'twitch'     => 'fa-brands fa-twitch',
                'github'     => 'fa-brands fa-github',
                'whatsapp'   => 'fa-brands fa-whatsapp',
                'telegram'   => 'fa-brands fa-telegram',
                'mastodon'   => 'fa-brands fa-mastodon',
                'tumblr'     => 'fa-brands fa-tumblr',
                'vimeo'      => 'fa-brands fa-vimeo-v',
                'flickr'     => 'fa-brands fa-flickr',
                'dribbble'   => 'fa-brands fa-dribbble',
                'behance'    => 'fa-brands fa-behance',
                'medium'     => 'fa-brands fa-medium',
                'spotify'    => 'fa-brands fa-spotify',
                'soundcloud' => 'fa-brands fa-soundcloud',
                'slack'      => 'fa-brands fa-slack',
                'skype'      => 'fa-brands fa-skype',
                'steam'      => 'fa-brands fa-steam',
                'patreon'    => 'fa-brands fa-patreon',
                'paypal'     => 'fa-brands fa-paypal',
            ],
            '6' => [
                'facebook'   => 'fa-brands fa-facebook',
                'messenger'  => 'fa-brands fa-facebook-messenger',
                'x'          => 'fa-brands fa-x-twitter',
                'instagram'  => 'fa-brands fa-instagram',
                'youtube'    => 'fa-brands fa-youtube',
                'linkedin'   => 'fa-brands fa-linkedin',
                'pinterest'  => 'fa-brands fa-pinterest',
                'tiktok'     => 'fa-brands fa-tiktok',
                'snapchat'   => 'fa-brands fa-snapchat',
                'reddit'     => 'fa-brands fa-reddit',
                'discord'    => 'fa-brands fa-discord',
                'twitch'     => 'fa-brands fa-twitch',
                'github'     => 'fa-brands fa-github',
                'whatsapp'   => 'fa-brands fa-whatsapp',
                'telegram'   => 'fa-brands fa-telegram',
                'mastodon'   => 'fa-brands fa-mastodon',
                'tumblr'     => 'fa-brands fa-tumblr',
                'vimeo'      => 'fa-brands fa-vimeo-v',
                'flickr'     => 'fa-brands fa-flickr',
                'dribbble'   => 'fa-brands fa-dribbble',
                'behance'    => 'fa-brands fa-behance',
                'medium'     => 'fa-brands fa-medium',
                'spotify'    => 'fa-brands fa-spotify',
                'soundcloud' => 'fa-brands fa-soundcloud',
                'slack'      => 'fa-brands fa-slack',
                'skype'      => 'fa-brands fa-skype',
                'steam'      => 'fa-brands fa-steam',
                'patreon'    => 'fa-brands fa-patreon',
                'paypal'     => 'fa-brands fa-paypal',
            ],
            '5' => [
                'facebook'   => 'fab fa-facebook',
                'messenger'  => 'fab fa-facebook-messenger',
                'x'          => 'fab fa-twitter',
                'instagram'  => 'fab fa-instagram',
                'youtube'    => 'fab fa-youtube',
                'linkedin'   => 'fab fa-linkedin',
                'pinterest'  => 'fab fa-pinterest',
                'tiktok'     => 'fab fa-tiktok',
                'snapchat'   => 'fab fa-snapchat',
                'reddit'     => 'fab fa-reddit',
                'discord'    => 'fab fa-discord',
                'twitch'     => 'fab fa-twitch',
                'github'     => 'fab fa-github',
                'whatsapp'   => 'fab fa-whatsapp',
                'telegram'   => 'fab fa-telegram',
                'mastodon'   => 'fab fa-mastodon',
                'tumblr'     => 'fab fa-tumblr',
                'vimeo'      => 'fab fa-vimeo-v',
                'flickr'     => 'fab fa-flickr',
                'dribbble'   => 'fab fa-dribbble',
                'behance'    => 'fab fa-behance',
                'medium'     => 'fab fa-medium',
                'spotify'    => 'fab fa-spotify',
                'soundcloud' => 'fab fa-soundcloud',
                'slack'      => 'fab fa-slack',
                'skype'      => 'fab fa-skype',
                'steam'      => 'fab fa-steam',
                'patreon'    => 'fab fa-patreon',
                'paypal'     => 'fab fa-paypal',
            ],
        ],
        'BootstrapIcons' => [
            '1' => [
                'facebook'   => 'bi bi-facebook',
                'messenger'  => 'bi bi-messenger',
                'x'          => 'bi bi-twitter-x',
                'instagram'  => 'bi bi-instagram',
                'youtube'    => 'bi bi-youtube',
                'linkedin'   => 'bi bi-linkedin',
                'pinterest'  => 'bi bi-pinterest',
                'tiktok'     => 'bi bi-tiktok',
                'snapchat'   => 'bi bi-snapchat',
                'reddit'     => 'bi bi-reddit',
                'discord'    => 'bi bi-discord',
                'twitch'     => 'bi bi-twitch',
                'github'     => 'bi bi-github',
                'whatsapp'   => 'bi bi-whatsapp',
                'telegram'   => 'bi bi-telegram',
                'mastodon'   => 'bi bi-mastodon',
                'tumblr'     => 'bi bi-globe',
                'vimeo'      => 'bi bi-vimeo',
                'flickr'     => 'bi bi-globe',
                'dribbble'   => 'bi bi-dribbble',
                'behance'    => 'bi bi-behance',
                'medium'     => 'bi bi-medium',
                'spotify'    => 'bi bi-spotify',
                'soundcloud' => 'bi bi-globe',
                'slack'      => 'bi bi-slack',
                'skype'      => 'bi bi-skype',
                'steam'      => 'bi bi-steam',
                'patreon'    => 'bi bi-globe',
                'paypal'     => 'bi bi-paypal',
            ],
        ],
        'RemixIcon' => [
            '4' => [
                'facebook'   => 'ri-facebook-fill',
                'messenger'  => 'ri-messenger-fill',
                'x'          => 'ri-twitter-x-fill',
                'instagram'  => 'ri-instagram-fill',
                'youtube'    => 'ri-youtube-fill',
                'linkedin'   => 'ri-linkedin-fill',
                'pinterest'  => 'ri-pinterest-fill',
                'tiktok'     => 'ri-tiktok-fill',
                'snapchat'   => 'ri-snapchat-fill',
                'reddit'     => 'ri-reddit-fill',
                'discord'    => 'ri-discord-fill',
                'twitch'     => 'ri-twitch-fill',
                'github'     => 'ri-github-fill',
                'whatsapp'   => 'ri-whatsapp-fill',
                'telegram'   => 'ri-telegram-fill',
                'mastodon'   => 'ri-mastodon-fill',
                'tumblr'     => 'ri-tumblr-fill',
                'vimeo'      => 'ri-vimeo-fill',
                'flickr'     => 'ri-flickr-fill',
                'dribbble'   => 'ri-dribbble-fill',
                'behance'    => 'ri-behance-fill',
                'medium'     => 'ri-medium-fill',
                'spotify'    => 'ri-spotify-fill',
                'soundcloud' => 'ri-soundcloud-fill',
                'slack'      => 'ri-slack-fill',
                'skype'      => 'ri-skype-fill',
                'steam'      => 'ri-steam-fill',
                'patreon'    => 'ri-patreon-fill',
                'paypal'     => 'ri-paypal-fill',
            ],
        ],
        'Boxicons' => [
            '2' => [
                'facebook'   => 'bx bxl-facebook',
                'messenger'  => 'bx bxl-messenger',
                'x'          => 'bx bxl-twitter',
                'instagram'  => 'bx bxl-instagram',
                'youtube'    => 'bx bxl-youtube',
                'linkedin'   => 'bx bxl-linkedin',
                'pinterest'  => 'bx bxl-pinterest',
                'tiktok'     => 'bx bxl-tiktok',
                'snapchat'   => 'bx bxl-snapchat',
                'reddit'     => 'bx bxl-reddit',
                'discord'    => 'bx bxl-discord',
                'twitch'     => 'bx bxl-twitch',
                'github'     => 'bx bxl-github',
                'whatsapp'   => 'bx bxl-whatsapp',
                'telegram'   => 'bx bxl-telegram',
                'mastodon'   => 'bx bxl-mastodon',
                'tumblr'     => 'bx bxl-tumblr',
                'vimeo'      => 'bx bxl-vimeo',
                'flickr'     => 'bx bxl-flickr',
                'dribbble'   => 'bx bxl-dribbble',
                'behance'    => 'bx bxl-behance',
                'medium'     => 'bx bxl-medium',
                'spotify'    => 'bx bxl-spotify',
                'soundcloud' => 'bx bxl-soundcloud',
                'slack'      => 'bx bxl-slack',
                'skype'      => 'bx bxl-skype',
                'steam'      => 'bx bxl-steam',
                'patreon'    => 'bx bxl-patreon',
                'paypal'     => 'bx bxl-paypal',
            ],
        ],
        'TablerIcons' => [
            '3' => [
                'facebook'   => 'ti ti-brand-facebook',
                'messenger'  => 'ti ti-brand-messenger',
                'x'          => 'ti ti-brand-x',
                'instagram'  => 'ti ti-brand-instagram',
                'youtube'    => 'ti ti-brand-youtube',
                'linkedin'   => 'ti ti-brand-linkedin',
                'pinterest'  => 'ti ti-brand-pinterest',
                'tiktok'     => 'ti ti-brand-tiktok',
                'snapchat'   => 'ti ti-brand-snapchat',
                'reddit'     => 'ti ti-brand-reddit',
                'discord'    => 'ti ti-brand-discord',
                'twitch'     => 'ti ti-brand-twitch',
                'github'     => 'ti ti-brand-github',
                'whatsapp'   => 'ti ti-brand-whatsapp',
                'telegram'   => 'ti ti-brand-telegram',
                'mastodon'   => 'ti ti-brand-mastodon',
                'tumblr'     => 'ti ti-brand-tumblr',
                'vimeo'      => 'ti ti-brand-vimeo',
                'flickr'     => 'ti ti-brand-flickr',
                'dribbble'   => 'ti ti-brand-dribbble',
                'behance'    => 'ti ti-brand-behance',
                'medium'     => 'ti ti-brand-medium',
                'spotify'    => 'ti ti-brand-spotify',
                'soundcloud' => 'ti ti-brand-soundcloud',
                'slack'      => 'ti ti-brand-slack',
                'skype'      => 'ti ti-brand-skype',
                'steam'      => 'ti ti-brand-steam',
                'patreon'    => 'ti ti-brand-patreon',
                'paypal'     => 'ti ti-brand-paypal',
            ],
        ],
        'PhosphorIcons' => [
            '2' => [
                'facebook'   => 'ph ph-facebook-logo',
                'messenger'  => 'ph ph-messenger-logo',
                'x'          => 'ph ph-x-logo',
                'instagram'  => 'ph ph-instagram-logo',
                'youtube'    => 'ph ph-youtube-logo',
                'linkedin'   => 'ph ph-linkedin-logo',
                'pinterest'  => 'ph ph-pinterest-logo',
                'tiktok'     => 'ph ph-tiktok-logo',
                'snapchat'   => 'ph ph-snapchat-logo',
                'reddit'     => 'ph ph-reddit-logo',
                'discord'    => 'ph ph-discord-logo',
                'twitch'     => 'ph ph-twitch-logo',
                'github'     => 'ph ph-github-logo',
                'whatsapp'   => 'ph ph-whatsapp-logo',
                'telegram'   => 'ph ph-telegram-logo',
                'mastodon'   => 'ph ph-mastodon-logo',
                'tumblr'     => 'ph ph-tumblr-logo',
                'vimeo'      => 'ph ph-vimeo-logo',
                'flickr'     => 'ph ph-flickr-logo',
                'dribbble'   => 'ph ph-dribbble-logo',
                'behance'    => 'ph ph-behance-logo',
                'medium'     => 'ph ph-medium-logo',
                'spotify'    => 'ph ph-spotify-logo',
                'soundcloud' => 'ph ph-soundcloud-logo',
                'slack'      => 'ph ph-slack-logo',
                'skype'      => 'ph ph-skype-logo',
                'steam'      => 'ph ph-steam-logo',
                'patreon'    => 'ph ph-patreon-logo',
                'paypal'     => 'ph ph-paypal-logo',
            ],
        ],
        'Lineicons' => [
            '4' => [
                'facebook'   => 'lni lni-facebook',
                'messenger'  => 'lni lni-facebook-messenger',
                'x'          => 'lni lni-twitter-x',
                'instagram'  => 'lni lni-instagram',
                'youtube'    => 'lni lni-youtube',
                'linkedin'   => 'lni lni-linkedin',
                'pinterest'  => 'lni lni-pinterest',
                'tiktok'     => 'lni lni-tiktok',
                'snapchat'   => 'lni lni-snapchat',
                'reddit'     => 'lni lni-reddit',
                'discord'    => 'lni lni-discord',
                'twitch'     => 'lni lni-twitch',
                'github'     => 'lni lni-github',
                'whatsapp'   => 'lni lni-whatsapp',
                'telegram'   => 'lni lni-telegram',
                'mastodon'   => 'lni lni-link',
                'tumblr'     => 'lni lni-tumblr',
                'vimeo'      => 'lni lni-vimeo',
                'flickr'     => 'lni lni-flickr',
                'dribbble'   => 'lni lni-dribbble',
                'behance'    => 'lni lni-behance',
                'medium'     => 'lni lni-medium',
                'spotify'    => 'lni lni-spotify',
                'soundcloud' => 'lni lni-soundcloud',
                'slack'      => 'lni lni-slack',
                'skype'      => 'lni lni-skype',
                'steam'      => 'lni lni-steam',
                'patreon'    => 'lni lni-link',
                'paypal'     => 'lni lni-paypal',
            ],
        ],
    ];

    /**
     * Generic/utility icons (non-brand): pack → major_version → key → css_class
     *
     * For icons like globe, link, envelope, etc. that aren't brand-specific.
     */
    private static array $genericIcons = [
        'FontAwesome' => [
            '7' => [
                'website' => 'fa-solid fa-globe',
            ],
            '6' => [
                'website' => 'fa-solid fa-globe',
            ],
            '5' => [
                'website' => 'fas fa-globe',
            ],
        ],
        'BootstrapIcons' => [
            '1' => [
                'website' => 'bi bi-globe',
            ],
        ],
        'RemixIcon' => [
            '4' => [
                'website' => 'ri-global-fill',
            ],
        ],
        'Boxicons' => [
            '2' => [
                'website' => 'bx bx-globe',
            ],
        ],
        'TablerIcons' => [
            '3' => [
                'website' => 'ti ti-world',
            ],
        ],
        'PhosphorIcons' => [
            '2' => [
                'website' => 'ph ph-globe',
            ],
        ],
        'Lineicons' => [
            '4' => [
                'website' => 'lni lni-world',
            ],
        ],
    ];

    /**
     * Default fallback icon per pack when a platform has no mapping.
     */
    private static array $fallbacks = [
        'fontawesome'    => 'fa-solid fa-link',
        'bootstrapicons' => 'bi bi-link-45deg',
        'remixicon'      => 'ri-link',
        'boxicons'       => 'bx bx-link',
        'tablericons'    => 'ti ti-link',
        'phosphoricons'  => 'ph ph-link',
        'lineicons'      => 'lni lni-link',
    ];

    /**
     * Normalize an icon pack name for matching.
     * "Font Awesome", "FontAwesome", "font_awesome", "font-awesome" all → "fontawesome"
     */
    private static function normalizeName(string $name): string
    {
        return strtolower(preg_replace('/[\s_\-]+/', '', $name));
    }

    /**
     * Extract major version from a version string.
     * "6.2.14" → "6", "5.x" → "5", "1" → "1"
     */
    private static function majorVersion(string $version): string
    {
        return strtok(trim($version), '.');
    }

    /**
     * Find the pack entry matching a (possibly non-normalized) icon pack name.
     */
    private static function findPack(string $iconPack, array $source): ?array
    {
        $needle = self::normalizeName($iconPack);
        foreach ($source as $name => $data) {
            if (self::normalizeName($name) === $needle) {
                return $data;
            }
        }
        return null;
    }

    /**
     * Get the CSS class for a platform or generic icon in the given icon pack/version.
     * Searches brand icons first, then generic icons.
     */
    public static function getClass(string $platform, string $iconPack, string $version): string
    {
        $key        = strtolower(trim($platform));
        $major      = self::majorVersion($version);
        $normalized = self::normalizeName($iconPack);

        // Check brand icons
        $pack  = self::findPack($iconPack, self::$packs);
        $icons = $pack[$major] ?? [];
        if (isset($icons[$key])) {
            return $icons[$key];
        }

        // Check generic icons
        $genericPack = self::findPack($iconPack, self::$genericIcons);
        $genericMap  = $genericPack[$major] ?? [];
        if (isset($genericMap[$key])) {
            return $genericMap[$key];
        }

        return self::$fallbacks[$normalized] ?? 'fa-solid fa-link';
    }

    /**
     * Get the base class for brand icons in the given pack/version.
     */
    public static function getBaseClass(string $iconPack, string $version): string
    {
        $major = self::majorVersion($version);
        $pack  = self::findPack($iconPack, self::$baseClasses);

        return $pack[$major] ?? '';
    }

    /**
     * Get all platform → class mappings for a given icon pack/version.
     */
    public static function getBrandMap(string $iconPack, string $version): array
    {
        $major = self::majorVersion($version);
        $pack  = self::findPack($iconPack, self::$packs);

        return $pack[$major] ?? [];
    }

    /**
     * Get all generic icon mappings for a given icon pack/version.
     */
    public static function getGenericMap(string $iconPack, string $version): array
    {
        $major = self::majorVersion($version);
        $pack  = self::findPack($iconPack, self::$genericIcons);

        return $pack[$major] ?? [];
    }

    /**
     * Reverse lookup: given a stored icon class string, find the platform key.
     * Searches brand icons and generic icons across all packs and versions.
     */
    public static function getPlatformFromIcon(string $iconClass): ?string
    {
        $iconClass = trim($iconClass);

        foreach ([self::$packs, self::$genericIcons] as $source) {
            foreach ($source as $packVersions) {
                foreach ($packVersions as $icons) {
                    $key = array_search($iconClass, $icons, true);
                    if ($key !== false) {
                        return $key;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get the list of supported icon packs (with versions).
     */
    public static function getSupportedPacks(): array
    {
        $result = [];
        foreach (self::$packs as $pack => $versions) {
            $result[$pack] = array_keys($versions);
        }
        return $result;
    }
}
