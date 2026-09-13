<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * UrlService - safe same-site redirect targets.
 *
 * The single choke point that turns a candidate return URL or referrer into
 * a root-relative same-site path, or "/" when the value cannot be trusted.
 * Every redirect whose target is visitor-supplied (form _return_url fields,
 * comment Referer bounces, posted return_url values) must pass through
 * sameSite() before reaching location(). This keeps open-redirect defense
 * in one auditable place instead of per-plugin duplicates.
 *
 * @package Pubvana\Services
 */
class UrlService
{
    /** @var Engine<object> */
    private Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Reduce a candidate return URL to a safe root-relative same-site path.
     *
     * @param string|null $url      Direct return URL (e.g. a posted _return_url).
     * @param string|null $referrer Referrer fallback when $url is empty.
     */
    public function sameSite(?string $url, ?string $referrer = null): string
    {
        $candidate = trim((string) ($url ?: $referrer ?: '/'));

        // Backslashes normalize to "/" in special-scheme URLs, so a value
        // like "/\evil.com" would resolve as the scheme-relative "//evil.com"
        // in browsers. Reject outright; no legit return path needs a backslash.
        if ($candidate === '' || str_contains($candidate, '\\')) {
            return '/';
        }

        // Scheme-relative URLs ("//evil.com") are absolute to any scheme and
        // host; they must never reach a Location header.
        if (str_starts_with($candidate, '//')) {
            return '/';
        }

        if (preg_match('#^https?://#i', $candidate)) {
            $reduced = $this->sameHostPath($candidate);
            if ($reduced === null) {
                return '/';
            }
            // A same-origin URL can still resolve scheme-relative after the
            // host is stripped (e.g. https://example.com//evil.com).
            if (str_starts_with($reduced, '//')) {
                return '/';
            }
            return $reduced;
        }

        // Anything left is a same-origin path (or path-ish value); force a
        // single leading slash so the result is always root-relative.
        if (!str_starts_with($candidate, '/')) {
            $candidate = '/' . $candidate;
        }

        return $candidate;
    }

    /**
     * The site's absolute origin for emitted URLs.
     *
     * Every URL the application renders for the outside world (canonical
     * tags, og:url, JSON-LD ids, sitemaps, robots.txt, emailed links)
     * must build on this value. It resolves the DB-backed CMS.siteUrl
     * setting the admin UI writes and never consults the request: the
     * Host header is attacker-controlled, so it can never contribute.
     * When the setting is unconfigured the seeded default
     * (http://localhost) is returned, which is constant and not
     * attacker-influenced.
     */
    public function siteOrigin(): string
    {
        try {
            $siteUrl = trim((string) ($this->app->settings()->get('CMS.siteUrl', '') ?? ''));
        } catch (\Throwable) {
            $siteUrl = '';
        }

        if ($siteUrl !== '') {
            return rtrim($siteUrl, '/');
        }

        return 'http://localhost';
    }

    /**
     * Turn a root-relative path into an absolute URL on the site origin.
     *
     * @param string $path Root-relative path (leading slash optional)
     */
    public function absoluteUrl(string $path): string
    {
        return $this->siteOrigin() . '/' . ltrim(trim($path), '/');
    }

    /**
     * Scheme allowlist for user-supplied external URLs stored on entities
     * (profile website and similar fields) that the application later
     * renders inside href attributes or emits into structured data.
     *
     * A value with no scheme ("example.com") is rejected: assuming a scheme
     * would silently accept half-entered input, and the caller is expected
     * to ask the user for a full http:// or https:// URL. Anything not
     * http/https (javascript:, data:, //host, etc.) is rejected because the
     * value ends up in a navigable attribute context.
     *
     * Pure static so models (which have no Engine reference) can use it too.
     */
    public static function isSafeExternalUrl(?string $url): bool
    {
        $candidate = trim((string) ($url ?? ''));
        if ($candidate === '') {
            return true; // Optional field; emptiness is the caller's call.
        }

        if (str_contains($candidate, '\\') || preg_match('/[\x00-\x1f\x7f]/', $candidate)) {
            return false;
        }

        $scheme = (string) (parse_url($candidate, PHP_URL_SCHEME) ?? '');
        return preg_match('#^https?$#i', $scheme) === 1;
    }

    /**
     * Reduce an absolute http(s) URL to its path and query when its host and
     * port match the site's own, null otherwise. The host is compared with
     * parse_url(), never string prefixing, so lookalike hosts
     * (example.com.evil.com, example.com@evil.com) cannot slip through.
     */
    private function sameHostPath(string $url): ?string
    {
        $siteUrl = $this->siteHost();
        if ($siteUrl === null) {
            return null;
        }

        $siteParts  = parse_url($siteUrl);
        $urlParts   = parse_url($url);

        if (!is_array($siteParts) || !is_array($urlParts)) {
            return null;
        }

        $siteHost = strtolower((string) ($siteParts['host'] ?? ''));
        $urlHost  = strtolower((string) ($urlParts['host'] ?? ''));
        if ($siteHost === '' || $urlHost === '' || $siteHost !== $urlHost) {
            return null;
        }

        if (($siteParts['port'] ?? null) !== ($urlParts['port'] ?? null)) {
            return null;
        }

        $path = (string) ($urlParts['path'] ?? '');
        if ($path === '') {
            $path = '/';
        }

        $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
        $fragment = isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '';

        return $path . $query . $fragment;
    }

    /**
     * The site's authoritative origin: the configured CMS.siteUrl setting,
     * else an absolute flight.base_url, else the request host. Returns null
     * only when nothing is derivable; with no reference host, absolute URLs
     * must be refused.
     *
     * This is the validation-side reference for sameSite(), not the
     * emission-side origin. The base_url/request tiers stay because a
     * redirect comparison needs some reference host; URLs the application
     * emits must build on siteOrigin() instead.
     */
    private function siteHost(): ?string
    {
        $siteUrl = '';

        try {
            $settings = $this->app->settings()->get('CMS.siteUrl', '');
            $siteUrl = trim((string) ($settings ?? ''));
        } catch (\Throwable) {
            // Settings store unreadable; fall through to base/request host.
        }

        if ($siteUrl === '') {
            $baseUrl = (string) ($this->app->get('flight.base_url') ?? '');
            if (preg_match('#^https?://#i', $baseUrl)) {
                $siteUrl = rtrim($baseUrl, '/');
            }
        }

        if ($siteUrl === '') {
            $host = (string) ($this->app->request()->getVar('HTTP_HOST') ?? '');
            if ($host === '') {
                return null;
            }
            return 'http://' . $host;
        }

        return $siteUrl;
    }
}