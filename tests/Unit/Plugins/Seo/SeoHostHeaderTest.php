<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Services\SchemaService;
use Pubvana\Plugins\Seo\Services\SeoService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\SettingsService;
use Pubvana\Services\UrlService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * AUDIT M4: canonical, og:url and JSON-LD URLs must be built from the
 * configured CMS.siteUrl setting, never from the request Host header.
 *
 * Every test runs with a hostile Host header in $_SERVER; any regression
 * to Host-based URL building fails loudly.
 *
 * @package Pubvana\Tests\Unit\Plugins\Seo
 */
#[CoversClass(SeoService::class)]
#[CoversClass(SchemaService::class)]
final class SeoHostHeaderTest extends TestCase
{
    private const HOSTILE_HOST = 'evil.example';

    private const CONFIGURED_URL = 'https://example.com';

    protected function setUp(): void
    {
        parent::setUp();
        Sqlite::recreate();
        $_SERVER['HTTP_HOST'] = self::HOSTILE_HOST;
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST']);
        parent::tearDown();
    }

    public function testCanonicalUsesTheConfiguredSiteUrlNotTheHostHeader(): void
    {
        $app = $this->buildApp(self::CONFIGURED_URL);
        $service = new SeoService(Sqlite::connection(), $app);
        $service->setContext(['content_type' => 'home']);

        self::assertSame(self::CONFIGURED_URL . '/', $service->buildCanonical());
    }

    public function testOgUrlUsesTheConfiguredSiteUrlNotTheHostHeader(): void
    {
        $app = $this->buildApp(self::CONFIGURED_URL);
        $service = new SeoService(Sqlite::connection(), $app);
        $service->setContext(['content_type' => 'home']);

        $tags = $service->buildOpenGraph();

        self::assertSame(self::CONFIGURED_URL . '/', $tags['og:url'] ?? null);
        self::assertStringNotContainsString(self::HOSTILE_HOST, implode('', $tags));
    }

    public function testJsonLdHomeUrlsUseTheConfiguredSiteUrlNotTheHostHeader(): void
    {
        $app = $this->buildApp(self::CONFIGURED_URL);

        $output = (new SchemaService($app))->render(['content_type' => 'home']);

        self::assertStringNotContainsString(self::HOSTILE_HOST, $output);

        // WebSite only renders on the homepage, so this also proves the
        // homepage detection (getCurrentUrl against the site URL) works
        // with the setting-derived origin.
        $node = $this->nodeByType($output, 'WebSite');
        self::assertSame(self::CONFIGURED_URL, $node['url'] ?? null);
    }

    public function testUnconfiguredSiteUrlFallsBackToSeededDefaultNotHostHeader(): void
    {
        $app = $this->buildApp('');

        $service = new SeoService(Sqlite::connection(), $app);
        $service->setContext(['content_type' => 'home']);
        self::assertSame('http://localhost/', $service->buildCanonical());

        $output = (new SchemaService($app))->render(['content_type' => 'home']);
        self::assertStringNotContainsString(self::HOSTILE_HOST, $output);
        $node = $this->nodeByType($output, 'WebSite');
        self::assertSame('http://localhost', $node['url'] ?? null);
    }

    /**
     * Fresh engine wired with the real settings store, the real UrlService,
     * and the shared in-memory database, mirroring the app wiring other
     * DB-backed tests use.
     */
    private function buildApp(string $siteUrl): Engine
    {
        $app = $this->app([
            'db'       => fn (): PDO => Sqlite::connection(),
            'settings' => $this->singleton(fn (): SettingsService => new SettingsService(\Flight::app())),
            'adext'    => $this->singleton(fn (): ExtensionRegistry => new ExtensionRegistry()),
        ]);
        \Flight::setEngine($app);
        $app->map('url', $this->singleton(fn (): UrlService => new UrlService($app)));

        $app->settings()->set('CMS.siteName', 'Test Site');
        $app->settings()->set('CMS.siteUrl', $siteUrl);

        return $app;
    }

    /**
     * Wrap a lazy provider in a per-engine singleton guard: Flight resolves
     * mapped services through repeated calls, so a bare closure would hand
     * back a fresh instance every time.
     *
     * @param callable $provider Zero-argument factory
     * @return callable Singleton-guarded factory
     */
    private function singleton(callable $provider): callable
    {
        return function () use ($provider) {
            static $instance = null;
            if ($instance === null) {
                $instance = $provider();
            }
            return $instance;
        };
    }

    /**
     * Extract the decoded JSON-LD node of the given @type from a rendered
     * block, proving the output stays parseable.
     *
     * @return array<string, mixed>
     */
    private function nodeByType(string $output, string $type): array
    {
        preg_match('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $output, $matches);
        self::assertCount(2, $matches, 'expected exactly one JSON-LD block');

        $decoded = json_decode($matches[1], true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('@graph', $decoded);

        foreach ($decoded['@graph'] as $node) {
            self::assertIsArray($node);
            if (($node['@type'] ?? '') === $type) {
                /** @var array<string, mixed> $node */
                return $node;
            }
        }

        self::fail("no @type {$type} node found in the rendered graph");
        return [];
    }
}
