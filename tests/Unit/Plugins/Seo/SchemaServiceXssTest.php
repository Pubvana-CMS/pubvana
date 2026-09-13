<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Services\SchemaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\SettingsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * The JSON-LD block is injected into <head> raw ({! header !}), so any
 * <script> break-out sequence in an admin-controlled title must be
 * hex-escaped before it reaches the page. These tests pin that contract.
 */
#[CoversClass(SchemaService::class)]
final class SchemaServiceXssTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Sqlite::recreate();
    }

    public function testHostilePostTitleCannotBreakOutOfTheScriptTag(): void
    {
        $schema = new SchemaService($this->buildApp());

        $title = 'Evil</script><script>alert(1)</script> & \' "';
        $output = $schema->render([
            'url'          => 'https://example.com/blog/evil',
            'content_type' => 'post',
            'title'        => $title,
            'author'       => ['name' => 'Admin', 'url' => 'https://example.com'],
            'published_at' => '2026-01-01',
            'updated_at'   => '2026-01-02',
        ]);

        self::assertStringContainsString('\u003C/script\u003E', $output, 'break-out chars must be hex-escaped');
        self::assertStringNotContainsString('<script>alert(1)</script>', $output, 'no raw script element may appear');
        self::assertStringNotContainsString('"alert(1) & \' "', $output);
        self::assertSame(1, substr_count($output, '</script>'), 'the wrapper close tag must be the only one');
        self::assertSame($title, $this->nodeByType($output, 'BlogPosting')['headline']);
    }

    public function testHostilePageNameCannotBreakOutOfTheScriptTag(): void
    {
        $schema = new SchemaService($this->buildApp());

        $title = 'Page</script><script>alert(2)</script>';
        $output = $schema->render([
            'url'          => 'https://example.com/page/evil',
            'content_type' => 'page',
            'title'        => $title,
            'updated_at'   => '2026-01-02',
        ]);

        self::assertStringContainsString('\u003C/script\u003E', $output);
        self::assertSame(1, substr_count($output, '</script>'));
        self::assertSame($title, $this->nodeByType($output, 'WebPage')['name']);
    }

    /**
     * Fresh engine wired with the real settings store over the shared
     * in-memory database, mirroring the app wiring other DB-backed tests use.
     */
    private function buildApp(): Engine
    {
        $app = $this->app([
            'db'       => fn(): \PDO => Sqlite::connection(),
            'settings' => $this->singleton(fn(): SettingsService => new SettingsService(\Flight::app())),
            'adext'    => $this->singleton(fn(): ExtensionRegistry => new ExtensionRegistry()),
        ]);
        \Flight::setEngine($app);
        $app->map('url', $this->singleton(fn(): \Pubvana\Services\UrlService => new \Pubvana\Services\UrlService($app)));

        $app->settings()->set('CMS.siteName', 'Test Site');
        $app->settings()->set('CMS.siteUrl', 'https://example.com');

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
     * block, proving the output stays parseable despite the hex escapes.
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
            if (is_array($node) && ($node['@type'] ?? null) === $type) {
                return $node;
            }
        }

        self::fail("no '$type' node found in the JSON-LD graph");
    }
}