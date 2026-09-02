<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
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
}
