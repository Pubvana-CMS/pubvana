<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Redirects;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Redirects\Models\Redirect;
use Pubvana\Plugins\Redirects\Models\RedirectLink;
use Pubvana\Plugins\Redirects\Services\RedirectLinksService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * RedirectLinksService + both Redirects models.
 */
#[CoversClass(RedirectLinksService::class)]
#[CoversClass(Redirect::class)]
#[CoversClass(RedirectLink::class)]
final class RedirectLinksServiceTest extends TestCase
{
    private PDO $pdo;
    private RedirectLinksService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        RedirectsSchema::create($this->pdo);
        $this->service = new RedirectLinksService($this->pdo, $this->app([]), [
            'skip_prefixes' => ['/admin', '/api'],
            'incoming_404s' => ['skip_prefixes' => ['/admin', '/api']],
        ]);
    }

    public function testRedirectModelOrderingAndFinders(): void
    {
        $this->insertRedirect('/b', '/x');
        $this->insertRedirect('/a', '/y', 0);

        $all = (new Redirect($this->pdo))->allOrdered();
        self::assertSame(['/a', '/b'], array_map(static fn($r): string => $r->source_path, $all));

        self::assertNotNull((new Redirect($this->pdo))->findById(1));
        self::assertNull((new Redirect($this->pdo))->findById(99999));

        // Only enabled rows match.
        self::assertNotNull((new Redirect($this->pdo))->findActiveBySourcePath('/b'));
        self::assertNull((new Redirect($this->pdo))->findActiveBySourcePath('/a'));
        self::assertNull((new Redirect($this->pdo))->findActiveBySourcePath('/missing'));
    }

    public function testAllCountRecent(): void
    {
        self::assertSame([], $this->service->all());
        self::assertSame(0, $this->service->count());
        self::assertSame([], $this->service->recent());
        self::assertNull($this->service->find(99999));

        $this->insertLink('/a', ignored: 0, resolved: null, seen: '2026-01-03 00:00:00');
        $this->insertLink('/b', ignored: 1, resolved: null, seen: '2026-01-02 00:00:00');
        $this->insertLink('/c', ignored: 0, resolved: 7, seen: '2026-01-01 00:00:00');

        self::assertSame(['/a'], $this->paths($this->service->all('active')));
        self::assertSame(['/b'], $this->paths($this->service->all('ignored')));
        self::assertSame(['/c'], $this->paths($this->service->all('resolved')));
        self::assertCount(3, $this->service->all('all'));

        self::assertSame(1, $this->service->count('active'));
        self::assertSame(1, $this->service->count('ignored'));
        self::assertSame(1, $this->service->count('resolved'));
        self::assertSame(3, $this->service->count('all'));

        // recent() slices the newest-first list.
        $this->insertLink('/d', ignored: 0, resolved: null, seen: '2026-01-04 00:00:00');
        $recent = $this->service->recent('active', 1);
        self::assertSame(['/d'], $this->paths($recent));
    }

    public function testDelete(): void
    {
        $this->insertLink('/a');
        $id = (int) $this->pdo->lastInsertId();

        self::assertTrue($this->service->delete($id));
        self::assertNull($this->service->find($id));
        self::assertFalse($this->service->delete($id));
        self::assertFalse($this->service->delete(99999));
    }

    public function testSetIgnored(): void
    {
        $this->insertLink('/a');
        $id = (int) $this->pdo->lastInsertId();

        $entry = $this->service->setIgnored($id, true);
        self::assertNotNull($entry);
        self::assertSame(1, (int) $entry->ignored);

        $entry = $this->service->setIgnored($id, false);
        self::assertNotNull($entry);
        self::assertSame(0, (int) $entry->ignored);

        self::assertNull($this->service->setIgnored(99999, true));
    }

    public function testMarkResolved(): void
    {
        $this->insertLink('/a');
        $id = (int) $this->pdo->lastInsertId();

        $entry = $this->service->markResolved($id, 42);
        self::assertNotNull($entry);
        self::assertSame(42, (int) $entry->resolved_redirect_id);
        self::assertNotEmpty($entry->resolved_at);
        self::assertSame(0, (int) $entry->ignored);

        self::assertNull($this->service->markResolved(99999, 42));
    }

    public function testMarkResolvedByPath(): void
    {
        $this->insertLink('/old-page');
        $entry = $this->service->markResolvedByPath('/old-page', 9);
        self::assertNotNull($entry);
        self::assertSame(9, (int) $entry->resolved_redirect_id);

        self::assertNull($this->service->markResolvedByPath('/missing', 9));
    }

    public function testRedirectLinkFinders(): void
    {
        $this->insertLink('/a');
        $id = (int) $this->pdo->lastInsertId();

        self::assertNotNull((new RedirectLink($this->pdo))->findById($id));
        self::assertNull((new RedirectLink($this->pdo))->findById(99999));
        self::assertNotNull((new RedirectLink($this->pdo))->findBySourcePath('/a'));
        self::assertNull((new RedirectLink($this->pdo))->findBySourcePath('/missing'));
    }

    public function testNormalizeIncomingPathVariants(): void
    {
        self::assertSame('/a/b', $this->invoke($this->service, 'normalizeIncomingPath', ['/base/a//b/', '/base']));
        self::assertSame('/', $this->invoke($this->service, 'normalizeIncomingPath', ['/base', '/base']));
        self::assertSame('/', $this->invoke($this->service, 'normalizeIncomingPath', ['', '']));
        self::assertSame('/a', $this->invoke($this->service, 'normalizeIncomingPath', ['a', '']));
    }

    public function testShouldSkipPathFallbacks(): void
    {
        // incoming_404s.skip_prefixes wins when present.
        self::assertTrue($this->invoke($this->service, 'shouldSkipPath', ['/admin/x']));
        self::assertFalse($this->invoke($this->service, 'shouldSkipPath', ['/about']));

        // Falls back to top-level skip_prefixes.
        $fallback = new RedirectLinksService($this->pdo, $this->app([]), ['skip_prefixes' => ['/api']]);
        self::assertTrue($this->invoke($fallback, 'shouldSkipPath', ['/api/x']));
        self::assertFalse($this->invoke($fallback, 'shouldSkipPath', ['/admin']));

        // No config at all: nothing skipped.
        $bare = new RedirectLinksService($this->pdo, $this->app([]), []);
        self::assertFalse($this->invoke($bare, 'shouldSkipPath', ['/admin']));
    }

    private function insertRedirect(string $source, string $target, int $enabled = 1): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO redirects (source_path, target_url, status_code, enabled, hit_count)
             VALUES (:s, :t, 301, :e, 0)'
        );
        $stmt->execute(['s' => $source, 't' => $target, 'e' => $enabled]);
    }

    private function insertLink(string $source, int $ignored = 0, ?int $resolved = null, string $seen = '2026-01-01 00:00:00'): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO redirects_links (source_path, hit_count, ignored, resolved_redirect_id, first_seen_at, last_seen_at)
             VALUES (:s, 1, :i, :r, :seen, :seen)'
        );
        $stmt->execute(['s' => $source, 'i' => $ignored, 'r' => $resolved, 'seen' => $seen]);
    }

    /** @param list<object> $entries */
    private function paths(array $entries): array
    {
        return array_map(static fn($e): string => $e->source_path, $entries);
    }
}
