<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('site_url')) {
    /**
     * Locale-aware site_url() override.
     *
     * For non-default locales, prepends the locale segment to the path
     * so that /blog becomes /es/blog, etc. Default locale uses bare URLs.
     *
     * @param array|string $relativePath URI string or array of URI segments.
     * @param string|null  $scheme       URI scheme. E.g., http, ftp.
     * @param \Config\App|null $config   Alternate configuration to use.
     */
    function site_url($relativePath = '', ?string $scheme = null, ?\Config\App $config = null): string
    {
        $currentURI = service('request')->getUri();
        assert($currentURI instanceof \CodeIgniter\HTTP\SiteURI);

        if (! is_cli()) {
            $path    = is_array($relativePath) ? implode('/', $relativePath) : (string) $relativePath;
            $locale  = service('request')->getLocale();
            $default = config('App')->defaultLocale;

            if ($locale !== $default) {
                $relativePath = ($path === '' || $path === '/')
                    ? $locale
                    : $locale . '/' . ltrim($path, '/');
            }
        }

        return $currentURI->siteUrl($relativePath, $scheme, $config);
    }
}
