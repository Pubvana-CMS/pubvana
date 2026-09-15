<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\BrokenLinks;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\BrokenLinks\Models\BrokenLink;
use Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Offline coverage for BrokenLinksService CRUD and pure logic.
 *
 * No network: HTTP checks are stubbed via TestableBrokenLinksService.
 */
#[CoversClass(BrokenLinksService::class)]
final class BrokenLinksServiceCoverageTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    /**
     * @param array<string, array{status: int|null, error: string|null}> $results
     * @param-out ExtensionRegistry $registry
     */
    private function makeService(
        string $siteUrl = '',
        array $results = [],
        ?ExtensionRegistry &$registry = null
    ): TestableBrokenLinksService {
        $registry = new ExtensionRegistry();
        $reg = $registry;
        $app = $this->app([
            'adext' => static fn (): ExtensionRegistry => $reg,
            'settings' => static fn (): object => new class ($siteUrl) {
                public function __construct(private string $siteUrl)
                {
                }

                public function get(string $key, mixed $default = ''): mixed
                {
                    return $this->siteUrl;
                }
            },
        ]);

        $service = new TestableBrokenLinksService($this->pdo, $app);
        $service->checkResults = $results;

        return $service;
    }

    private function rowCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM broken_links');
        self::assertNotFalse($stmt);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string, mixed>|false
     */
    private function fetchRow(string $url): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM broken_links WHERE url = ?');
        self::assertNotFalse($stmt);
        $stmt->execute([$url]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function firstId(): int
    {
        $stmt = $this->pdo->query('SELECT id FROM broken_links ORDER BY id ASC LIMIT 1');
        self::assertNotFalse($stmt);

        return (int) $stmt->fetchColumn();
    }

    public function testUpsertUpdatesExistingRow(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $service->upsert([
            'source_type' => 'post',
            'source_id' => 1,
            'source_title' => 'A',
            'url' => 'https://example.com/x',
            'http_status' => 404,
            'error_message' => 'first',
        ]);
        $service->upsert([
            'source_type' => 'post',
            'source_id' => 1,
            'source_title' => 'A',
            'url' => 'https://example.com/x',
            'http_status' => 500,
            'error_message' => 'second',
        ]);

        self::assertSame(1, $this->rowCount());
        $row = $this->fetchRow('https://example.com/x');
        self::assertNotFalse($row);
        self::assertSame(500, (int) $row['http_status']);
        self::assertSame('second', $row['error_message']);
    }

    public function testUpsertTruncatesLongFields(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $service->upsert([
            'source_type' => 'post',
            'source_id' => 1,
            'source_title' => str_repeat('t', 300),
            'url' => 'https://example.com/long',
            'http_status' => 404,
            'error_message' => str_repeat('e', 300),
        ]);

        $row = $this->fetchRow('https://example.com/long');
        self::assertNotFalse($row);
        self::assertSame(255, mb_strlen((string) $row['source_title']));
        self::assertSame(255, mb_strlen((string) $row['error_message']));
        self::assertSame(sha1('https://example.com/long'), $row['url_hash']);
    }

    public function testUpsertLeavesDismissedUntouched(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $service->upsert([
            'source_type' => 'post',
            'source_id' => 1,
            'source_title' => 'A',
            'url' => 'https://example.com/d',
            'http_status' => 404,
            'error_message' => 'orig',
        ]);
        $service->dismiss($this->firstId());
        $service->upsert([
            'source_type' => 'post',
            'source_id' => 1,
            'source_title' => 'A changed',
            'url' => 'https://example.com/d',
            'http_status' => 500,
            'error_message' => 'new',
        ]);

        $row = $this->fetchRow('https://example.com/d');
        self::assertNotFalse($row);
        self::assertSame(1, (int) $row['dismissed']);
        self::assertSame(404, (int) $row['http_status']);
        self::assertSame('orig', $row['error_message']);
    }

    public function testCountBrokenExcludesDismissedAndOk(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/1', 'http_status' => 200, 'error_message' => null]);
        $service->upsert(['source_type' => 'post', 'source_id' => 2, 'source_title' => 'B', 'url' => 'https://a.test/2', 'http_status' => 404, 'error_message' => null]);
        $service->upsert(['source_type' => 'post', 'source_id' => 3, 'source_title' => 'C', 'url' => 'https://a.test/3', 'http_status' => null, 'error_message' => 'timeout']);
        $service->upsert(['source_type' => 'post', 'source_id' => 4, 'source_title' => 'D', 'url' => 'https://a.test/4', 'http_status' => 404, 'error_message' => null]);
        $service->dismiss($this->firstIdForUrl('https://a.test/4'));

        // 200 excluded, dismissed 404 excluded, 404 + null counted.
        self::assertSame(2, $service->countBroken());
    }

    public function testRecentOrdersLimitsAndExcludesDismissed(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/1', 'http_status' => 404, 'error_message' => null]);
        $service->upsert(['source_type' => 'post', 'source_id' => 2, 'source_title' => 'B', 'url' => 'https://a.test/2', 'http_status' => 500, 'error_message' => null]);
        $service->upsert(['source_type' => 'post', 'source_id' => 3, 'source_title' => 'C', 'url' => 'https://a.test/3', 'http_status' => 404, 'error_message' => null]);

        $this->pdo->exec("UPDATE broken_links SET last_checked_at = '2026-01-01 00:00:00' WHERE url = 'https://a.test/1'");
        $this->pdo->exec("UPDATE broken_links SET last_checked_at = '2026-01-03 00:00:00' WHERE url = 'https://a.test/2'");
        $this->pdo->exec("UPDATE broken_links SET last_checked_at = '2026-01-02 00:00:00' WHERE url = 'https://a.test/3'");
        $service->dismiss($this->firstIdForUrl('https://a.test/2'));

        $recent = $service->recent(5);
        self::assertCount(2, $recent);
        self::assertInstanceOf(BrokenLink::class, $recent[0]);
        self::assertInstanceOf(BrokenLink::class, $recent[1]);
        self::assertSame('https://a.test/3', $recent[0]->url);
        self::assertSame('https://a.test/1', $recent[1]->url);

        $limited = $service->recent(1);
        self::assertCount(1, $limited);
        self::assertSame('https://a.test/3', $limited[0]->url);
    }

    public function testDelete(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        self::assertFalse($service->delete(99999));

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/x', 'http_status' => 404, 'error_message' => null]);
        $id = $this->firstId();

        self::assertTrue($service->delete($id));
        self::assertSame(0, $this->rowCount());
        self::assertFalse($service->delete($id));
    }

    public function testDeleteBySourceAndHashRemovesOnlyTarget(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/1', 'http_status' => 404, 'error_message' => null]);
        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/2', 'http_status' => 404, 'error_message' => null]);

        $service->deleteBySourceAndHash('post', 1, sha1('https://a.test/1'));

        self::assertSame(1, $this->rowCount());
        self::assertNotFalse($this->fetchRow('https://a.test/2'));
        self::assertFalse($this->fetchRow('https://a.test/1'));
    }

    public function testDeleteOkScopesToSource(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/ok', 'http_status' => 200, 'error_message' => null]);
        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/bad', 'http_status' => 404, 'error_message' => null]);
        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/err', 'http_status' => null, 'error_message' => 'x']);
        $service->upsert(['source_type' => 'post', 'source_id' => 2, 'source_title' => 'B', 'url' => 'https://a.test/ok2', 'http_status' => 200, 'error_message' => null]);

        $service->deleteOk('post', 1);

        self::assertFalse($this->fetchRow('https://a.test/ok'));
        self::assertNotFalse($this->fetchRow('https://a.test/bad'));
        self::assertNotFalse($this->fetchRow('https://a.test/err'));
        self::assertNotFalse($this->fetchRow('https://a.test/ok2'));
    }

    public function testDismiss(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        self::assertNull($service->dismiss(99999));

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/x', 'http_status' => 404, 'error_message' => null]);
        $entry = $service->dismiss($this->firstId());

        self::assertNotNull($entry);
        self::assertSame(1, (int) $entry->dismissed);
    }

    public function testRecheckMissing(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $result = $service->recheck(99999);

        self::assertNull($result['status']);
        self::assertSame('Entry not found.', $result['error']);
    }

    public function testRecheckStillBroken(): void
    {
        $registry = null;
        $service = $this->makeService('', ['https://a.test/x' => ['status' => 500, 'error' => null]], $registry);

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/x', 'http_status' => 404, 'error_message' => null]);

        $result = $service->recheck($this->firstId());

        self::assertSame(500, $result['status']);
        $row = $this->fetchRow('https://a.test/x');
        self::assertNotFalse($row);
        self::assertSame(500, (int) $row['http_status']);
    }

    public function testRecheckNowOkRemovesRow(): void
    {
        $registry = null;
        $service = $this->makeService('', ['https://a.test/x' => ['status' => 200, 'error' => null]], $registry);

        $service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.test/x', 'http_status' => 404, 'error_message' => null]);

        $result = $service->recheck($this->firstId());

        self::assertSame(200, $result['status']);
        self::assertSame(0, $this->rowCount());
    }

    public function testScanCountsAndCleansOk(): void
    {
        $registry = null;
        $service = $this->makeService('', [
            'https://example.com/broken' => ['status' => 404, 'error' => null],
            'https://example.com/fixed' => ['status' => 200, 'error' => null],
        ], $registry);

        $registry->register('brokenlinks', 'source', 'pubvana.test', [
            'label' => 'Test',
            'callable' => static fn (): array => [
                ['type' => 'post', 'id' => 1, 'title' => 'One', 'content' => '<a href="https://example.com/broken">b</a> <a href="https://example.com/fixed">f</a>'],
            ],
        ]);

        $result = $service->scan();

        self::assertSame(2, $result['total']);
        self::assertSame(1, $result['broken']);
        self::assertSame(1, $result['sources']);
        self::assertSame(['https://example.com/broken', 'https://example.com/fixed'], $service->checked);
        self::assertNotFalse($this->fetchRow('https://example.com/broken'));
        self::assertFalse($this->fetchRow('https://example.com/fixed'));
    }

    public function testScanWithNoSources(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        self::assertSame(['total' => 0, 'broken' => 0, 'sources' => 0], $service->scan());
    }

    public function testExtractLinksExcludesSameHost(): void
    {
        $registry = null;
        $service = $this->makeService('https://mysite.test', [], $registry);

        $links = $service->extractLinks(
            '<a href="https://mysite.test/internal">in</a> ' .
            '<a href="https://MYSITE.test/upper">up</a> ' .
            '<a href="https://other.test/page">out</a>'
        );

        self::assertNotContains('https://mysite.test/internal', $links);
        self::assertNotContains('https://MYSITE.test/upper', $links);
        self::assertContains('https://other.test/page', $links);
    }

    public function testExtractLinksExcludesSchemesAndFragments(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $links = $service->extractLinks(
            '<a href="mailto:a@b.test">m</a> <a href="tel:123">t</a> ' .
            '<a href="javascript:void(0)">j</a> <a href="data:text/plain,x">d</a> ' .
            '<a href="#frag">f</a> <a href="/relative">r</a> ' .
            '<a href="https://example.com/ok">ok</a>'
        );

        self::assertSame(['https://example.com/ok'], $links);
    }

    public function testExtractLinksDedupes(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $links = $service->extractLinks(
            '<a href="https://example.com/dup">a</a> ' .
            '<a href="https://example.com/dup">b</a> ' .
            'See [x](https://example.com/dup) and https://example.com/dup'
        );

        $counts = array_count_values($links);
        self::assertSame(1, $counts['https://example.com/dup'] ?? 0);
    }

    public function testCollectSourcesSkipsBadContributions(): void
    {
        $registry = null;
        $service = $this->makeService('', [], $registry);

        $registry->register('brokenlinks', 'source', 'pubvana.good', [
            'label' => 'Good',
            'callable' => static fn (): array => [
                ['type' => 'post', 'id' => 1, 'title' => 'One', 'content' => '<p>x</p>'],
            ],
        ]);
        $registry->register('brokenlinks', 'source', 'pubvana.badcall', [
            'label' => 'Bad',
            'callable' => 'not-a-function-xyz',
        ]);
        $registry->register('brokenlinks', 'source', 'pubvana.badreturn', [
            'label' => 'Bad return',
            'callable' => static fn (): string => 'nope',
        ]);
        $registry->register('brokenlinks', 'source', 'pubvana.baditems', [
            'label' => 'Bad items',
            'callable' => static fn (): array => [
                ['type' => 'post'],
                'just-a-string',
                null,
                ['type' => 'post', 'id' => 2, 'title' => 'Two', 'content' => '<p>y</p>'],
            ],
        ]);

        $sources = $service->collectSources();

        self::assertCount(2, $sources);
        self::assertSame('One', $sources[0]['title']);
        self::assertSame('Two', $sources[1]['title']);
    }

    private function firstIdForUrl(string $url): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM broken_links WHERE url = ? ORDER BY id ASC LIMIT 1');
        self::assertNotFalse($stmt);
        $stmt->execute([$url]);

        return (int) $stmt->fetchColumn();
    }
}

/**
 * BrokenLinksService with HTTP stubbed for offline tests.
 */
final class TestableBrokenLinksService extends BrokenLinksService
{
    /** @var array<string, array{status: int|null, error: string|null}> */
    public array $checkResults = [];

    /** @var list<string> */
    public array $checked = [];

    /**
     * @return array{status: int|null, error: string|null}
     */
    public function checkUrl(string $url): array
    {
        $this->checked[] = $url;

        return $this->checkResults[$url] ?? ['status' => 404, 'error' => null];
    }
}
