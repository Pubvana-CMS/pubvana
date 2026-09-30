<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Pubvana\Middleware\SecurityHeadersMiddleware;
use Pubvana\Tests\Support\TestCase;

/**
 * SecurityHeadersMiddleware has no external dependencies; its constructor
 * merges default headers with overrides and before() appends the CSP.
 */
#[CoversClass(SecurityHeadersMiddleware::class)]
final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testConstructorAppliesDefaultHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $headers = $this->property($middleware, 'headers');

        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        self::assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
        self::assertSame('camera=(), microphone=(), geolocation=()', $headers['Permissions-Policy']);
    }

    public function testConstructorMergesConfigOverrides(): void
    {
        $middleware = new SecurityHeadersMiddleware(['X-Frame-Options' => 'DENY']);
        $headers = $this->property($middleware, 'headers');

        self::assertSame('DENY', $headers['X-Frame-Options']);
        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
    }

    public function testConstructorAllowsCustomExtraHeader(): void
    {
        $middleware = new SecurityHeadersMiddleware(['X-Custom' => 'value']);
        $headers = $this->property($middleware, 'headers');

        self::assertSame('value', $headers['X-Custom']);
    }

    public function testBeforeAddsContentSecurityPolicyHeader(): void
    {
        if (headers_sent()) {
            self::markTestSkipped('Cannot send headers after output has started.');
        }

        $middleware = new SecurityHeadersMiddleware();
        $middleware->before();

        $headers = $this->property($middleware, 'headers');
        self::assertArrayHasKey('Content-Security-Policy', $headers);
        self::assertStringContainsString("default-src 'self'", $headers['Content-Security-Policy']);
        self::assertStringContainsString('frame-ancestors', $headers['Content-Security-Policy']);
    }

    public function testBeforeKeepsDefaultHeadersWhenSending(): void
    {
        if (headers_sent()) {
            self::markTestSkipped('Cannot send headers after output has started.');
        }

        $middleware = new SecurityHeadersMiddleware();
        $middleware->before();

        $headers = $this->property($middleware, 'headers');
        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
    }

    public function testBeforeBuildsAllCspParts(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $middleware->before();

        $csp = $this->property($middleware, 'headers')['Content-Security-Policy'];

        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("style-src 'self'", $csp);
        self::assertStringContainsString("font-src 'self'", $csp);
        self::assertStringContainsString("img-src 'self' data: blob:", $csp);
        self::assertStringContainsString("connect-src 'self'", $csp);
    }

    public function testCspExcludesUnpkgAndAddsHardening(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $middleware->before();

        $csp = $this->property($middleware, 'headers')['Content-Security-Policy'];

        // No app code references unpkg.com; it must not be an allowed source.
        self::assertStringNotContainsString('unpkg.com', $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringContainsString("base-uri 'self'", $csp);
    }

    /**
     * CaptchaService::snippet() inlines the provider's api.js, so the CSP has
     * to allow both providers' script, challenge iframe, styles, and XHR.
     * Without these the widget div renders and the browser blocks the script,
     * leaving an empty box that reads as a missing field.
     *
     * Checks the URLs the widgets actually load against the parsed sources
     * rather than string matching, so the providers' documented wildcard and
     * path forms (https://*.hcaptcha.com, https://www.google.com/recaptcha/)
     * count as covering the concrete hosts.
     *
     * @param non-empty-string $directive CSP fetch directive
     * @param non-empty-string $url       URL the widget loads under it
     */
    #[DataProvider('captchaUrlProvider')]
    public function testCspAllowsCaptchaProviders(string $directive, string $url): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $middleware->before();

        $csp = $this->property($middleware, 'headers')['Content-Security-Policy'];

        self::assertTrue(
            self::sourceAllows(self::directiveSources($csp, $directive), $url),
            sprintf('CSP %s does not allow %s. Header: %s', $directive, $url, $csp)
        );
    }

    /**
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public static function captchaUrlProvider(): array
    {
        return [
            'hcaptcha script'   => ['script-src', 'https://js.hcaptcha.com/1/api.js'],
            'hcaptcha frame'    => ['frame-src', 'https://newassets.hcaptcha.com/captcha/v1/0/api.js'],
            'hcaptcha style'    => ['style-src', 'https://newassets.hcaptcha.com/captcha/v1/0/style.css'],
            'hcaptcha connect'  => ['connect-src', 'https://api.hcaptcha.com/captcha/v1/getcaptcha'],
            'hcaptcha api host' => ['script-src', 'https://hcaptcha.com/1/api.js'],
            'recaptcha script'  => ['script-src', 'https://www.google.com/recaptcha/api.js'],
            'recaptcha gstatic' => ['script-src', 'https://www.gstatic.com/recaptcha/releases/'],
            'recaptcha frame'   => ['frame-src', 'https://recaptcha.google.com/recaptcha/api2/anchor'],
            'recaptcha style'   => ['style-src', 'https://www.google.com/recaptcha/api2/anchor'],
            'recaptcha connect' => ['connect-src', 'https://www.google.com/recaptcha/api2/anchor'],
        ];
    }

    /**
     * Split one directive's source list out of a CSP header.
     *
     * @return list<string>
     */
    private static function directiveSources(string $csp, string $directive): array
    {
        foreach (explode(';', $csp) as $part) {
            $tokens = preg_split('/\s+/', trim($part)) ?: [];
            if (($tokens[0] ?? '') === $directive) {
                return array_slice($tokens, 1);
            }
        }

        return [];
    }

    /**
     * Whether any source in a directive covers the given URL.
     *
     * Honours the two CSP source forms in use here: a bare host
     * (https://hcaptcha.com), a subdomain wildcard (https://*.hcaptcha.com),
     * and a host with a path prefix (https://www.google.com/recaptcha/).
     *
     * @param list<string> $sources
     */
    private static function sourceAllows(array $sources, string $url): bool
    {
        $target = parse_url($url);
        $targetHost = strtolower((string) ($target['host'] ?? ''));
        $targetPath = (string) ($target['path'] ?? '/');

        foreach ($sources as $source) {
            if (str_contains($source, "'")) {
                continue;
            }

            $parts = parse_url($source);
            $host = strtolower((string) ($parts['host'] ?? ''));
            if ($host === '') {
                continue;
            }

            if (str_starts_with($host, '*.')) {
                if (!str_ends_with($targetHost, substr($host, 1))) {
                    continue;
                }
            } elseif ($host !== $targetHost) {
                continue;
            }

            $sourcePath = $parts['path'] ?? null;
            if ($sourcePath !== null && !str_starts_with($targetPath, $sourcePath)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
