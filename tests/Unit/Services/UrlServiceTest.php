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