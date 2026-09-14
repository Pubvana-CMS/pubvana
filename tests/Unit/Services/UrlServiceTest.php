<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\UrlService;
use Pubvana\Tests\Support\TestCase;

/**
 * UrlService open-redirect defense.
 *
 * Covers the sameSite() contract for every visitor-supplied redirect value:
 * scheme-relative and backslash variants are refused, foreign-host absolute
 * URLs are refused, same-host absolute URLs reduce to a root-relative path,
 * and host comparison is parse-based so lookalike hosts never pass.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(UrlService::class)]
final class UrlServiceTest extends TestCase
{
    public function testNullAndEmptyResolveToRoot(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/', $service->sameSite(null));
        self::assertSame('/', $service->sameSite(''));
        self::assertSame('/', $service->sameSite('', ''));
    }

    public function testSchemeRelativeUrlsAreRefused(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/', $service->sameSite('//evil.com'));
        self::assertSame('/', $service->sameSite('///evil.com'));
        self::assertSame('/', $service->sameSite('http://localhost//evil.com'));
    }

    public function testBackslashVariantsAreRefused(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/', $service->sameSite('/\\evil.com'));
        self::assertSame('/', $service->sameSite('\\\\evil.com'));
    }

    public function testForeignHostAbsoluteUrlsAreRefused(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/', $service->sameSite('https://evil.com/phish'));
        self::assertSame('/', $service->sameSite('http://localhost.evil.com/phish'));
        self::assertSame('/', $service->sameSite('https://localhost:8080/phish'));
    }

    public function testSameHostAbsoluteUrlReducesToRootRelativePath(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/', $service->sameSite('http://localhost'));
        self::assertSame('/contact', $service->sameSite('http://localhost/contact'));
        self::assertSame('/blog/thanks?done=1#top', $service->sameSite('http://localhost/blog/thanks?done=1#top'));
        self::assertSame('/', $service->sameSite('https://evil.com@localhost'));
        self::assertSame('/ok', $service->sameSite('https://evil.com@localhost/ok'));
        self::assertSame('/', $service->sameSite('https://localhost@evil.com/ok'));
    }

    public function testPortMatchesWhenConfigured(): void
    {
        $service = $this->service('http://localhost:8080');

        self::assertSame('/x', $service->sameSite('http://localhost:8080/x'));
        self::assertSame('/', $service->sameSite('http://localhost/x'));
    }

    public function testPlainPathsPassThroughUntouched(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/contact', $service->sameSite('/contact'));
        self::assertSame('/blog/1?page=2', $service->sameSite('/blog/1?page=2'));
        self::assertSame('/forms', $service->sameSite('forms'));
        self::assertSame('/', $service->sameSite('/'));
    }

    public function testReferrerFallsBackWhenUrlIsEmpty(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/fallback', $service->sameSite('', 'http://localhost/fallback'));
        self::assertSame('/fallback', $service->sameSite(null, 'http://localhost/fallback'));
        self::assertSame('/', $service->sameSite(null, 'https://evil.com/fallback'));
    }

    public function testMultipleSchemeSlashVariantsStaySameSitePaths(): void
    {
        $service = $this->service('http://localhost');

        self::assertSame('/https:/evil.com', $service->sameSite('https:/evil.com'));
        self::assertSame('/http:evil.com', $service->sameSite('http:evil.com'));
    }

    public function testNoSiteHostConfiguredRefusesAbsoluteUrls(): void
    {
        $service = $this->service('');

        self::assertSame('/', $service->sameSite('http://localhost/contact'));
        self::assertSame('/contact', $service->sameSite('/contact'));
    }

    public function testRequestHostIsTheFallbackReference(): void
    {
        $_SERVER['HTTP_HOST'] = 'pull.example.com';
        try {
            $service = $this->service('');

            self::assertSame('/ok', $service->sameSite('http://pull.example.com/ok'));
            self::assertSame('/', $service->sameSite('http://evil.example.com/ok'));
        } finally {
            unset($_SERVER['HTTP_HOST']);
        }
    }

    public function testSiteOriginPrefersTheConfiguredSetting(): void
    {
        $service = $this->service('https://example.com/');

        self::assertSame('https://example.com', $service->siteOrigin());
        self::assertSame('https://example.com', $this->service('https://example.com')->siteOrigin());
    }

    public function testSiteOriginFallsBackToSeededDefaultAndIgnoresHostHeader(): void
    {
        $_SERVER['HTTP_HOST'] = 'evil.example';
        try {
            $service = $this->service('');

            self::assertSame('http://localhost', $service->siteOrigin(), 'the Host header must never contribute to emitted URLs');
        } finally {
            unset($_SERVER['HTTP_HOST']);
        }
    }

    public function testAbsoluteUrlJoinsPathOnTheOrigin(): void
    {
        $service = $this->service('https://example.com');

        self::assertSame('https://example.com/blog/post', $service->absoluteUrl('/blog/post'));
        self::assertSame('https://example.com/blog/post', $service->absoluteUrl('blog/post'));
        self::assertSame('https://example.com/', $service->absoluteUrl(''));
    }

    public function testEmptyOrNullIsASafeExternalUrl(): void
    {
        // Fields are optional; emptiness is the caller's call, not a scheme error.
        self::assertTrue(UrlService::isSafeExternalUrl(null));
        self::assertTrue(UrlService::isSafeExternalUrl(''));
        self::assertTrue(UrlService::isSafeExternalUrl('   '));
    }

    public function testHttpAndHttpsSchemesAreSafe(): void
    {
        self::assertTrue(UrlService::isSafeExternalUrl('https://example.com/page'));
        self::assertTrue(UrlService::isSafeExternalUrl('http://example.com/page'));
        self::assertTrue(UrlService::isSafeExternalUrl('HTTPS://EXAMPLE.COM/PAGE'));
        self::assertTrue(UrlService::isSafeExternalUrl('https://example.com:8080/x?a=1#b'));
    }

    public function testNonHttpSchemesAreRefused(): void
    {
        self::assertFalse(UrlService::isSafeExternalUrl('javascript:alert(1)'));
        self::assertFalse(UrlService::isSafeExternalUrl('data:text/html;base64,PHNjcmlwdD4='));
        self::assertFalse(UrlService::isSafeExternalUrl('vbscript:msgbox(1)'));
        self::assertFalse(UrlService::isSafeExternalUrl('file:///etc/passwd'));
        self::assertFalse(UrlService::isSafeExternalUrl('ftp://example.com/file'));
        self::assertFalse(UrlService::isSafeExternalUrl('javascript://example.com/%0aalert(1)'));
    }

    public function testSchemelessValuesAreRefused(): void
    {
        self::assertFalse(UrlService::isSafeExternalUrl('example.com'));
        self::assertFalse(UrlService::isSafeExternalUrl('www.example.com'));
        self::assertFalse(UrlService::isSafeExternalUrl('//evil.com'));
        self::assertFalse(UrlService::isSafeExternalUrl('/page'));
    }

    public function testControlCharactersAndBackslashesAreRefused(): void
    {
        self::assertFalse(UrlService::isSafeExternalUrl("java\tscript://evil.com"));
        self::assertFalse(UrlService::isSafeExternalUrl("https://exa\x00mple.com/"));
        self::assertFalse(UrlService::isSafeExternalUrl("https://example.com/a\nb"));
        self::assertFalse(UrlService::isSafeExternalUrl('/\evil.com'));
        self::assertFalse(UrlService::isSafeExternalUrl("https://evil.com/\\normal"));
    }

    public function testEdgeWhitespaceIsTrimmedNotRejected(): void
    {
        // Trim strips edge whitespace (including null bytes) before the
        // checks, so padded values store clean instead of being refused.
        self::assertTrue(UrlService::isSafeExternalUrl(" https://example.com/page\t"));
    }

    // -----------------------------------------------------------------
    // normalizeExternalUrl()
    // -----------------------------------------------------------------

    public function testNormalizeExternalUrlReturnsNullForEmpty(): void
    {
        self::assertNull(UrlService::normalizeExternalUrl(null, 'https://twitter.com/'));
        self::assertNull(UrlService::normalizeExternalUrl('', 'https://twitter.com/'));
        self::assertNull(UrlService::normalizeExternalUrl('   ', 'https://twitter.com/'));
    }

    public function testNormalizeExternalUrlPrependsBaseToBareValue(): void
    {
        self::assertSame(
            'https://twitter.com/user',
            UrlService::normalizeExternalUrl('user', 'https://twitter.com/')
        );
        self::assertSame(
            'https://twitter.com/user',
            UrlService::normalizeExternalUrl('user', 'https://twitter.com')
        );
    }

    public function testNormalizeExternalUrlPassesThroughSafeFullUrl(): void
    {
        self::assertSame(
            'https://x.com/user',
            UrlService::normalizeExternalUrl('https://x.com/user', 'https://twitter.com/')
        );
    }

    public function testNormalizeExternalUrlRefusesUnsafeFullUrl(): void
    {
        // javascript: has no http(s) scheme, so it is treated as a bare value
        // and gets the base prepended (safe as an href on the base domain).
        self::assertSame(
            'https://twitter.com/javascript:alert(1)',
            UrlService::normalizeExternalUrl('javascript:alert(1)', 'https://twitter.com/')
        );
        // Scheme-relative URLs are treated as bare values too (safe on the base domain).
        self::assertSame(
            'https://twitter.com///evil.com',
            UrlService::normalizeExternalUrl('//evil.com', 'https://twitter.com/')
        );
        // Actual unsafe full URLs with an http(s) scheme that fail isSafeExternalUrl.
        self::assertNull(UrlService::normalizeExternalUrl("https://example.com/\x00evil", 'https://twitter.com/'));
    }

    private function service(string $siteUrl): UrlService
    {
        $app = $this->app([
            'settings' => $this->stubSettings($siteUrl),
        ]);

        return new UrlService($app);
    }

    /**
     * A settings stand-in returning the configured CMS.siteUrl.
     */
    private function stubSettings(string $siteUrl): callable
    {
        return static fn (): object => new class($siteUrl) {
            public function __construct(private string $siteUrl)
            {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'CMS.siteUrl' ? $this->siteUrl : $default;
            }
        };
    }
}