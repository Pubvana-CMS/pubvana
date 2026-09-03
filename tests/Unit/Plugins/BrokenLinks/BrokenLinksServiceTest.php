<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\BrokenLinks;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\BrokenLinks\Models\BrokenLink;
use Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

#[CoversClass(BrokenLinksService::class)]
final class BrokenLinksServiceTest extends TestCase
{
    private \PDO $pdo;
    private ExtensionRegistry $registry;
    private BrokenLinksService $service;

    protected function setUp(): void
    {
        $this->pdo = Sqlite::recreate();
        $this->registry = new ExtensionRegistry();

        $app = $this->app([
            'adext'    => fn (): ExtensionRegistry => $this->registry,
            'settings' => fn () => new class {
                public function get(string $key, mixed $default = ''): mixed
                {
                    return '/var/www/html';
                }
            },
        ]);

        $this->service = new BrokenLinksService($this->pdo, $app);
    }

    public function testIsOk(): void
    {
        self::assertTrue($this->service->isOk(200));
        self::assertTrue($this->service->isOk(204));
        self::assertTrue($this->service->isOk(299));
        self::assertFalse($this->service->isOk(301));
        self::assertFalse($this->service->isOk(404));
        self::assertFalse($this->service->isOk(500));
        self::assertFalse($this->service->isOk(null));
    }

    public function testExtractLinksFiltersOutSkippableUrls(): void
    {
        $content = '<p>Hello <a href="https://example.com">Example</a> and ' .
            '<a href="/internal/path">Internal</a> and <a href="mailto:a@b.c">Mail</a></p>';

        $links = $this->service->extractLinks($content);

        self::assertContains('https://example.com', $links);
        self::assertNotContains('/internal/path', $links);
        self::assertNotContains('mailto:a@b.c', $links);
    }

    public function testExtractLinksHandlesMarkdown(): void
    {
        $content = 'See [the docs](https://example.org/docs) for details.';

        $links = $this->service->extractLinks($content);

        self::assertContains('https://example.org/docs', $links);
    }

    public function testExtractLinksHandlesBareUrls(): void
    {
        $content = 'Visit https://example.com/page now.';

        $links = $this->service->extractLinks($content);

        self::assertContains('https://example.com/page', $links);
    }

    public function testExtractLinksIgnoresEmptyContent(): void
    {
        self::assertSame([], $this->service->extractLinks(''));
        self::assertSame([], $this->service->extractLinks('   '));
    }

    public function testUpsertCreatesNewRow(): void
    {
        $this->service->upsert([
            'source_type'   => 'post',
            'source_id'     => 1,
            'source_title'  => 'Test Post',
            'url'           => 'https://example.org/x',
            'http_status'   => 404,
            'error_message' => 'Not found',
        ]);

        $row = $this->pdo->query(
            "SELECT * FROM broken_links WHERE source_type = 'post' AND source_id = 1"
        )->fetch();

        self::assertNotFalse($row);
        self::assertSame(404, (int) $row['http_status']);
        self::assertSame(0, (int) $row['dismissed']);
    }

    public function testCountBroken(): void
    {
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.com', 'http_status' => 404, 'error_message' => null]);
        $this->service->upsert(['source_type' => 'post', 'source_id' => 2, 'source_title' => 'B', 'url' => 'https://b.com', 'http_status' => 500, 'error_message' => null]);
        $this->service->upsert(['source_type' => 'post', 'source_id' => 3, 'source_title' => 'C', 'url' => 'https://c.com', 'http_status' => null, 'error_message' => 'timeout']);

        self::assertSame(3, $this->service->countBroken());
    }

    public function testAllGroupsBySource(): void
    {
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.com', 'http_status' => 404, 'error_message' => null]);
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://b.com', 'http_status' => 404, 'error_message' => null]);
        $this->service->upsert(['source_type' => 'page', 'source_id' => 2, 'source_title' => 'B', 'url' => 'https://c.com', 'http_status' => 404, 'error_message' => null]);

        $grouped = $this->service->all();

        self::assertCount(2, $grouped);

        $byType = [];
        foreach ($grouped as $group) {
            $byType[$group['source_type']] = $group;
        }

        self::assertArrayHasKey('post', $byType);
        self::assertArrayHasKey('page', $byType);
        self::assertCount(2, $byType['post']['links']);
        self::assertCount(1, $byType['page']['links']);
    }

    public function testDismissSkipsOnRescan(): void
    {
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.com', 'http_status' => 404, 'error_message' => null]);

        $result = $this->service->dismiss(1);
        self::assertNotNull($result);

        // Re-upsert the same URL with a broken status; dismissed row must be untouched.
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.com', 'http_status' => 404, 'error_message' => null]);

        $row = $this->pdo->query(
            "SELECT * FROM broken_links WHERE source_type = 'post' AND source_id = 1"
        )->fetch();

        self::assertSame(1, (int) $row['dismissed']);
    }

    public function testAllExcludesDismissedByDefault(): void
    {
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.com', 'http_status' => 404, 'error_message' => null]);
        $this->service->upsert(['source_type' => 'post', 'source_id' => 2, 'source_title' => 'B', 'url' => 'https://b.com', 'http_status' => 404, 'error_message' => null]);

        $this->service->dismiss(1);

        $default = $this->service->all(false);
        $withDismissed = $this->service->all(true);

        self::assertCount(1, $default);
        self::assertCount(2, $withDismissed);
    }

    public function testDeleteOkRemovesReachableUrls(): void
    {
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://a.com', 'http_status' => 404, 'error_message' => null]);
        $this->service->upsert(['source_type' => 'post', 'source_id' => 1, 'source_title' => 'A', 'url' => 'https://b.com', 'http_status' => 200, 'error_message' => null]);

        $this->service->deleteOk('post', 1);

        $count = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM broken_links WHERE source_type = 'post' AND source_id = 1"
        )->fetchColumn();

        self::assertSame(1, $count);
    }

    public function testCollectSourcesReturnsRegisteredSources(): void
    {
        $this->registry->register('brokenlinks', 'source', 'pubvana.test', [
            'label'    => 'Test Items',
            'callable' => fn () => [
                ['type' => 'item', 'id' => 1, 'title' => 'Item One', 'content' => '<p>Hello <a href="https://example.com">Example</a></p>'],
                ['type' => 'item', 'id' => 2, 'title' => 'Item Two', 'content' => '<p>No external links here</p>'],
            ],
        ]);

        $sources = $this->service->collectSources();

        self::assertCount(2, $sources);
        self::assertSame('Item One', $sources[0]['title']);
    }

    public function testCheckUrlReturns404ForNonExistentDomain(): void
    {
        $result = $this->service->checkUrl('https://invalid-domain-that-does-not-exist-12345.example');

        if ($result['status'] !== null) {
            self::assertTrue($result['status'] >= 400);
        } else {
            self::assertIsString($result['error']);
        }
    }
}