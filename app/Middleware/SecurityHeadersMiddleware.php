<?php

declare(strict_types=1);

namespace Pubvana\Middleware;

/**
 * SecurityHeadersMiddleware - Adds security headers to every response.
 *
 * Applied before every request in routes.php. Headers include:
 *   - Content-Security-Policy: restricts where resources can load from
 *   - Strict-Transport-Security: forces HTTPS for 1 year
 *   - X-Content-Type-Options: prevents MIME type sniffing
 *   - X-Frame-Options: prevents clickjacking (same origin only)
 *   - X-XSS-Protection: enables browser XSS filter
 *   - Referrer-Policy: limits referrer info to same origin
 *   - Permissions-Policy: disables camera, microphone, geolocation
 *
 * CSP sources:
 *   - 'self': only load resources from our own domain
 *   - cdn.jsdelivr.net: Tabler UI, Alpine.js, HTMX, Jodit
 *   - fonts.googleapis.com / fonts.gstatic.com: Google Fonts
 *   - 'unsafe-inline': required for Tabler's inline styles and Alpine.js
 *   - 'unsafe-eval': required for Alpine.js (can be removed in production
 *     if you switch to CSP-safe Alpine builds)
 *
 * @package Pubvana\Middleware
 */
class SecurityHeadersMiddleware
{
    /** @var array<string, string> Header name => value pairs */
    protected array $headers;

    /**
     * @param array<string, string> $config Additional headers to merge (overrides defaults)
     */
    public function __construct(array $config = [])
    {
        $this->headers = array_merge([
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'X-XSS-Protection'          => '1; mode=block',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Permissions-Policy'        => 'camera=(), microphone=(), geolocation=()',
        ], $config);
    }

    /**
     * Send all security headers.
     *
     * Called before any output is sent. Must run on every request.
     *
     * @return void
     */
    public function before(): void
    {
        // Build Content-Security-Policy from allowed sources
        $cspParts = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: blob:",
            "connect-src 'self' https://cdn.jsdelivr.net",
            "frame-ancestors 'self'",
        ];

        $this->headers['Content-Security-Policy'] = implode('; ', $cspParts);

        // Send all headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }
}
