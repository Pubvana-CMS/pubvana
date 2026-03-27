<?php

namespace App\Libraries;

class LanguageSwitcher
{
    /**
     * Active language rows (objects with code, name, native_name, direction, is_active, is_default).
     *
     * @var object[]
     */
    protected array $languages;

    /**
     * Current URI string (e.g. '/en/blog/my-post').
     */
    protected string $currentUri;

    /**
     * Current locale code (e.g. 'en').
     */
    protected string $currentLocale;

    /**
     * All valid locale codes derived from active languages.
     *
     * @var string[]
     */
    protected array $localeCodes;

    /**
     * @param object[] $languages    Active language rows.
     * @param string   $currentUri   Current URI string.
     * @param string   $currentLocale Current locale code.
     */
    public function __construct(array $languages, string $currentUri, string $currentLocale)
    {
        $this->languages     = $languages;
        $this->currentUri    = $currentUri;
        $this->currentLocale = $currentLocale;
        $this->localeCodes   = array_map(
            static fn (object $lang): string => $lang->code,
            $languages
        );
    }

    /**
     * Build and return the switcher data arrays.
     *
     * @return array{buttons: array, dropdown: array, ul: array}
     */
    public function build(): array
    {
        $buttons  = [];
        $dropdown = [];
        $ul       = [];

        foreach ($this->languages as $lang) {
            $code      = $lang->code;
            $name      = $lang->name;
            $native    = $lang->native_name;
            $direction = $lang->direction ?? 'ltr';
            $active    = ($code === $this->currentLocale);
            $url       = $this->buildUrl($code);

            $dirModifier = ($direction === 'rtl') ? '--rtl' : '--ltr';

            // buttons format
            $btnClasses = trim(
                "pv-lang-btn pv-lang-btn--{$code} pv-lang-btn{$dirModifier}"
                . ($active ? ' pv-lang-btn--active' : '')
            );

            $buttons[] = [
                'code'        => $code,
                'name'        => $name,
                'native_name' => $native,
                'url'         => $url,
                'direction'   => $direction,
                'active'      => $active,
                'css'         => [
                    'btn' => $btnClasses,
                ],
            ];

            // dropdown format — select is container-level (same string for all items)
            $optionClasses = trim(
                "pv-lang-option pv-lang-option--{$code} pv-lang-option{$dirModifier}"
                . ($active ? ' pv-lang-option--active' : '')
            );

            $dropdown[] = [
                'code'        => $code,
                'name'        => $name,
                'native_name' => $native,
                'url'         => $url,
                'direction'   => $direction,
                'active'      => $active,
                'css'         => [
                    'select' => 'pv-lang-select',
                    'option' => $optionClasses,
                ],
            ];

            // ul format — list is container-level (same string for all items)
            $itemClasses = trim(
                "pv-lang-item pv-lang-item--{$code} pv-lang-item{$dirModifier}"
                . ($active ? ' pv-lang-item--active' : '')
            );

            $linkClasses = "pv-lang-link pv-lang-link--{$code}";

            $ul[] = [
                'code'        => $code,
                'name'        => $name,
                'native_name' => $native,
                'url'         => $url,
                'direction'   => $direction,
                'active'      => $active,
                'css'         => [
                    'list' => 'pv-lang-list',
                    'item' => $itemClasses,
                    'link' => $linkClasses,
                ],
            ];
        }

        return [
            'buttons'  => $buttons,
            'dropdown' => $dropdown,
            'ul'       => $ul,
        ];
    }

    /**
     * Build the URL for the given locale code.
     *
     * - Strips existing locale prefix if present.
     * - Prepends new locale code (unless it's the default — bare path).
     * - Root `/` becomes `/{locale}` (or just `/` for default).
     */
    protected function buildUrl(string $locale): string
    {
        $uri       = $this->currentUri;
        $isDefault = ($locale === config('App')->defaultLocale);

        // Normalise: ensure leading slash, strip trailing slash (except root).
        if ($uri === '' || $uri === '/') {
            return $isDefault ? '/' : '/' . $locale;
        }

        // Ensure leading slash for reliable prefix detection.
        if ($uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        // Strip existing locale prefix when it matches a known locale code.
        // Pattern: /^\/({codes})(\/.*)?$/
        if (! empty($this->localeCodes)) {
            $codesPattern = implode('|', array_map('preg_quote', $this->localeCodes, array_fill(0, count($this->localeCodes), '/')));
            if (preg_match('#^/(' . $codesPattern . ')(/.*)?$#', $uri, $matches)) {
                // $matches[2] is the rest of the path (may be empty).
                $uri = $matches[2] ?? '';
                if ($uri === '') {
                    $uri = '/';
                }
            }
        }

        // Default locale uses bare paths (no prefix).
        if ($isDefault) {
            return $uri;
        }

        // $uri is now without a locale prefix (starts with / or is /).
        if ($uri === '/') {
            return '/' . $locale;
        }

        // Ensure it starts with a slash before concatenating.
        if ($uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        return '/' . $locale . $uri;
    }
}
