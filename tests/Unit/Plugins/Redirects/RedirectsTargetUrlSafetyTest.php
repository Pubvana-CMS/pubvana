<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Redirects;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Redirects\Services\RedirectsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * AUDIT L5: redirect target_url scheme filter.
 *
 * Redirect targets end up in a Location header, so a stored
 * "javascript://..." (or any non-http(s) scheme, scheme-relative
 * "//host", backslash, or control character) must be refused at the
 * write, not stored. Relative paths and full http(s) URLs stay allowed.
 */
#[CoversClass(RedirectsService::class)]
final class RedirectsTargetUrlSafetyTest extends TestCase
{
    private PDO $pdo;

    private RedirectsService $service;

    protected function setUp(): void
    {
        $this->pdo = Sqlite::recreate();
        $this->createRedirectsTable($this->pdo);
        $this->service = new RedirectsService($this->pdo, $this->app([]));
    }

    public function testCreateStoresRelativeTarget(): void
    {
        $redirect = $this->service->create($this->payload('/new-location'));

        self::assertSame('/old-path', $redirect->source_path);
        self::assertSame('/new-location', $redirect->target_url);
    }

    public function testCreateStoresFullHttpTarget(): void
    {
        $redirect = $this->service->create($this->payload('https://example.com/page'));

        self::assertSame('https://example.com/page', $redirect->target_url);
    }

    public function testCreateRefusesJavascriptScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('http:// or https://');

        $this->service->create($this->payload('javascript://alert(1)'));
    }

    public function testCreateRefusesNonHttpSchemes(): void
    {
        foreach (['ftp://example.com/x', 'file:///etc/passwd', 'vbscript://msgbox'] as $target) {
            try {
                $this->service->create($this->payload($target));
                self::fail("Scheme-bearing target '$target' must be refused.");
            } catch (\InvalidArgumentException $e) {
            }
        }

        self::assertSame(0, $this->redirectCount());
    }

    public function testCreateRefusesSchemeRelativeTarget(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->create($this->payload('//evil.com/path'));
    }

    public function testCreateRefusesBackslashesAndControlCharacters(): void
    {
        foreach (['/new\\evil', "/new\r\nHeader: x"] as $target) {
            try {
                $this->service->create($this->payload($target));
                self::fail("Hostile target must be refused.");
            } catch (\InvalidArgumentException $e) {
            }
        }

        self::assertSame(0, $this->redirectCount());
    }

    public function testUpdateRefusesHostileTargetAndLeavesRowUntouched(): void
    {
        $redirect = $this->service->create($this->payload('/new-location'));

        try {
            $this->service->update((int) $redirect->id, $this->payload('javascript://alert(1)'));
            self::fail('A hostile update target must be refused.');
        } catch (\InvalidArgumentException $e) {
        }

        $fresh = $this->service->find((int) $redirect->id);
        self::assertNotNull($fresh);
        self::assertSame('/new-location', $fresh->target_url);
    }

    public function testUpdateAcceptsSafeTarget(): void
    {
        $redirect = $this->service->create($this->payload('/new-location'));

        $updated = $this->service->update((int) $redirect->id, $this->payload('/other-location'));

        self::assertNotNull($updated);
        self::assertSame('/other-location', $updated->target_url);
    }

    public function testUpdateUnknownIdStillReturnsNull(): void
    {
        self::assertNull($this->service->update(9999, $this->payload('/whatever')));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function payload(string $target): array
    {
        return [
            'source_path' => '/old-path',
            'target_url'  => $target,
            'status_code' => '301',
            'enabled'     => '1',
            'notes'       => '',
        ];
    }

    private function redirectCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) c FROM redirects')->fetch()['c'];
    }

    private function createRedirectsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE redirects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                source_path TEXT NOT NULL,
                target_url TEXT NOT NULL,
                status_code INTEGER NOT NULL DEFAULT 301,
                enabled INTEGER NOT NULL DEFAULT 1,
                notes TEXT,
                hit_count INTEGER NOT NULL DEFAULT 0,
                last_hit_at TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
        $pdo->exec('CREATE UNIQUE INDEX redirects_source_path ON redirects (source_path)');
    }
}
