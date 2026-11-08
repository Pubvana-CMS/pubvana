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
 * siteUrlRedirect() also sends a visitor to the SITE_URL domain when the
 * request arrives on another domain, or over HTTP while FORCE_HTTPS is on.
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
     * must build on this value. It resolves the SITE_URL deployment value
     * from the app store and never consults the request: the Host header
     * is attacker-controlled, so it can never contribute. When SITE_URL is
     * unset the constant default (http://localhost) is returned.
     */
    public function siteOrigin(): string
    {
        $siteUrl = trim((string) ($this->app->get('siteUrl') ?? ''));

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
     * True when the request arrived over HTTPS.
     *
     * The app trusts X-Forwarded-Proto as-is. A TLS proxy sets it, so keep
     * the app reachable only through that proxy.
     *
     * @param array<string, mixed> $server Typically $_SERVER
     */
    public static function isHttpsRequest(array $server): bool
    {
        if (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            return true;
        }

        return ($server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * The URL to send the visitor to, or null when they are already on it.
     *
     * Returns a redirect when the request domain is not the SITE_URL domain,
     * or when FORCE_HTTPS is on and the request is not HTTPS. The app builds
     * the target from SITE_URL and keeps the request path and query, so a
     * deep link goes to the same page on the right domain.
     *
     * @param string               $siteUrl    The SITE_URL value
     * @param array<string, mixed> $server     Typically $_SERVER
     * @param bool                 $forceHttps Whether FORCE_HTTPS is on
     */
    public static function siteUrlRedirect(string $siteUrl, array $server, bool $forceHttps): ?string
    {
        $siteUrl = rtrim(trim($siteUrl), '/');
        if ($siteUrl === '') {
            return null;
        }

        $site = parse_url($siteUrl);
        if (!is_array($site)) {
            return null;
        }

        $siteScheme = strtolower((string) ($site['scheme'] ?? ''));
        if ($siteScheme !== 'http' && $siteScheme !== 'https') {
            return null;
        }

        $siteHost = strtolower(trim((string) ($site['host'] ?? '')));
        if ($siteHost === '') {
            return null;
        }

        $requestHost = self::domainFromHeader((string) ($server['HTTP_HOST'] ?? ''));
        $needsHttps = $forceHttps && !self::isHttpsRequest($server);

        if ($requestHost === $siteHost && !$needsHttps) {
            return null;
        }

        // With FORCE_HTTPS on, https replaces http. Any path in SITE_URL stays.
        $location = $needsHttps
            ? (string) preg_replace('#^https?://#i', 'https://', $siteUrl, 1)
            : $siteUrl;

        return $location . self::requestPath($server);
    }

    /**
     * Lowercases the domain from a Host header and drops the port. Returns
     * an empty string when the header is missing or unreadable, which counts
     * as a wrong domain.
     */
    private static function domainFromHeader(string $hostHeader): string
    {
        $hostHeader = trim($hostHeader);
        if ($hostHeader === '') {
            return '';
        }

        $parts = parse_url('http://' . $hostHeader);
        if (!is_array($parts)) {
            return '';
        }

        return strtolower((string) ($parts['host'] ?? ''));
    }

    /**
     * The request path and query as a root-relative path.
     *
     * A control character in REQUEST_URI would split the Location header, so
     * return "/" instead of repairing it.
     *
     * @param array<string, mixed> $server Typically $_SERVER
     */
    private static function requestPath(array $server): string
    {
        $uri = (string) ($server['REQUEST_URI'] ?? '/');

        if ($uri === '' || preg_match('/[\x00-\x1f\x7f]/', $uri) === 1) {
            return '/';
        }

        return $uri[0] === '/' ? $uri : '/' . $uri;
    }

    /**
     * The external URL law: an admin-supplied external URL is stored as a
     * full URL or it is not stored at all.
     *
     * One field, one form. A value must already carry an http or https
     * scheme ("https://twitter.com/ada"); it is trimmed and written
     * verbatim. Nothing assumes, prepends, or infers a scheme or a
     * platform, so no reader ever has to guess how a value was entered.
     *
     * Rejected: a bare host or handle ("example.com", "@ada"), and every
     * scheme that is not http/https (javascript:, data:, and the
     * scheme-relative "//host"), because the value ends up in a navigable
     * attribute or in structured data.
     *
     * Call it on the way in (reject the write) and on the way out (drop a
     * legacy row that predates the law). Emptiness is the caller's call:
     * an empty value passes, so optional fields can clear themselves.
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
     * The site's authoritative origin: the configured SITE_URL deployment
     * value, else an absolute flight.base_url, else the request host.
     * Returns null only when nothing is derivable; with no reference host,
     * absolute URLs must be refused.
     *
     * This is the validation-side reference for sameSite(), not the
     * emission-side origin. The base_url/request tiers stay because a
     * redirect comparison needs some reference host; URLs the application
     * emits must build on siteOrigin() instead.
     */
    private function siteHost(): ?string
    {
        $siteUrl = trim((string) ($this->app->get('siteUrl') ?? ''));

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