<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Pages\Services\PagesService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PagesService CRUD, revisions, listings, dashboard, host items.
 */
#[CoversClass(PagesService::class)]
final class PagesServiceTest extends TestCase
{
    private PDO $pdo;
    private PagesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        PagesSchema::create($this->pdo);
        $this->service = new PagesService($this->pdo, ['route_prefix' => '/page', 'max_revisions' => 3]);

        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
        ]);
        \Flight::setEngine($app);
    }

    public function testListPagesPaginationAndTotal(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->service->createPage(['title' => "P{$i}", 'status' => 'draft'], 1);
        }

        $result = $this->service->listPages(1, 2);
        self::assertCount(2, $result['items']);
        self::assertSame(5, $result['total']);
        self::assertSame(1, $result['page']);
        self::assertSame(2, $result['per_page']);

        $result2 = $this->service->listPages(3, 2);
        self::assertCount(1, $result2['items']);
    }

    public function testListPublishedOnlyReturnsPublished(): void
    {
        $this->service->createPage(['title' => 'Draft One', 'status' => 'draft'], 1);
        $this->service->createPage(['title' => 'Live One', 'status' => 'published'], 1);

        $published = $this->service->listPublished();
        self::assertCount(1, $published);
        self::assertSame('Live One', $published[0]->title);
    }

    public function testFindersAndSlugExists(): void
    {
        $page = $this->service->createPage(['title' => 'About', 'status' => 'published'], 1);
        $id = (int) $page->id;

        self::assertNotNull($this->service->findPage($id));
        self::assertNull($this->service->findPage(99999));
        // findPageBySlug only sees published rows.
        self::assertNotNull($this->service->findPageBySlug('about'));

        $draft = $this->service->createPage(['title' => 'Hidden', 'status' => 'draft'], 1);
        self::assertNull($this->service->findPageBySlug((string) $draft->slug));

        self::assertTrue($this->service->pageSlugExists('about'));
        self::assertFalse($this->service->pageSlugExists('missing'));
        self::assertFalse($this->service->pageSlugExists('about', $id));
        self::assertTrue($this->service->pageSlugExists('about', 99999));
    }

    public function testCreatePageDefaultsAndSnapshot(): void
    {
        $page = $this->service->createPage([
            'title' => 'Hello',
            'content' => '<p>Hi</p>',
            'status' => 'published',
            'allow_comments' => '1',
            'ai_generated' => '1',
        ], 7);

        self::assertGreaterThan(0, (int) $page->id);
        self::assertSame('published', $page->status);
        self::assertSame(1, (int) $page->allow_comments);
        self::assertSame(1, (int) $page->ai_generated);
        self::assertSame(7, (int) $page->created_by);
        self::assertCount(1, $this->service->getRevisions((int) $page->id));

        // Defaults: draft status, no comments, no AI flag.
        $plain = $this->service->createPage(['title' => 'Plain'], 2);
        self::assertSame('draft', $plain->status);
        self::assertSame(0, (int) $plain->allow_comments);
        self::assertSame(0, (int) $plain->ai_generated);
    }

    public function testUpdatePageSnapshotsBeforeAndPrunesAfter(): void
    {
        $page = $this->service->createPage(['title' => 'V1', 'status' => 'draft'], 5);
        $id = (int) $page->id;

        // max_revisions=3: create (1) + 4 updates = 5 snapshots, pruned to 3.
        for ($i = 2; $i <= 5; $i++) {
            $updated = $this->service->updatePage($id, ['title' => "V{$i}"], 9);
            self::assertNotNull($updated);
        }
        self::assertCount(3, $this->service->getRevisions($id));
        self::assertSame('V5', $this->service->findPage($id)?->title);

        // Null path.
        self::assertNull($this->service->updatePage(99999, ['title' => 'x'], 1));

        // Fallback author uses created_by when no user id given.
        $fallback = $this->service->updatePage($id, ['title' => 'V6']);
        self::assertNotNull($fallback);
        $revisions = $this->service->getRevisions($id);
        self::assertSame(5, (int) $revisions[0]->author_id);
    }

    public function testDeletePage(): void
    {
        $page = $this->service->createPage(['title' => 'Gone', 'status' => 'draft'], 1);
        $id = (int) $page->id;

        self::assertTrue($this->service->deletePage($id));
        self::assertNull($this->service->findPage($id));
        // Already soft-deleted: finder hides it, delete returns false.
        self::assertFalse($this->service->deletePage($id));
        self::assertFalse($this->service->deletePage(99999));
    }

    public function testRestoreRevision(): void
    {
        $page = $this->service->createPage(['title' => 'V1', 'content' => 'one', 'status' => 'draft'], 1);
        $id = (int) $page->id;
        $this->service->updatePage($id, ['title' => 'V2', 'content' => 'two'], 1);

        $revisions = $this->service->getRevisions($id);
        self::assertCount(2, $revisions);
        // Newest revision holds the pre-update snapshot (V1).
        $oldest = $revisions[1];

        $restored = $this->service->restoreRevision($id, (int) $oldest->id, 1);
        self::assertNotNull($restored);
        self::assertSame('V1', $restored->title);

        // The pre-restore state (V2) is snapshotted before the restore, so
        // the restore is reversible.
        $after = $this->service->getRevisions($id);
        self::assertSame('V2', $after[0]->title);

        self::assertNull($this->service->restoreRevision(99999, (int) $oldest->id, 1));
        self::assertNull($this->service->restoreRevision($id, 99999, 1));

        // Cross-page revision id is refused.
        $other = $this->service->createPage(['title' => 'Other', 'status' => 'draft'], 1);
        $otherRevs = $this->service->getRevisions((int) $other->id);
        self::assertNull($this->service->restoreRevision($id, (int) $otherRevs[0]->id, 1));
    }

    public function testPublishedOptions(): void
    {
        $this->service->createPage(['title' => 'Zulu', 'status' => 'published'], 1);
        $this->service->createPage(['title' => 'Alpha', 'status' => 'published'], 1);
        $this->service->createPage(['title' => 'Drafty', 'status' => 'draft'], 1);

        $options = $this->service->publishedOptions();
        self::assertSame(['Alpha', 'Zulu'], array_values($options));
    }

    public function testSearchProviderEmptyAndHit(): void
    {
        self::assertSame([], $this->service->searchProvider('nothing-here'));

        $this->service->createPage(['title' => 'About Us', 'content' => 'hello', 'status' => 'published'], 1);
        $results = $this->service->searchProvider('About');
        self::assertCount(1, $results);
        self::assertSame('/page/about-us', $results[0]['url']);
    }

    public function testNavAndCommentHostItems(): void
    {
        self::assertSame([], $this->service->navLinkableItems());
        self::assertSame([], $this->service->commentHostItems());

        $page = $this->service->createPage(['title' => 'About', 'status' => 'published', 'allow_comments' => '1'], 1);
        $id = (int) $page->id;

        $nav = $this->service->navLinkableItems();
        self::assertSame('About', $nav[0]['label']);
        self::assertSame('/page/about', $nav[0]['url']);

        $hosts = $this->service->commentHostItems();
        self::assertSame('page', $hosts[0]['type']);
        self::assertSame($id, $hosts[0]['id']);
        self::assertTrue($hosts[0]['allow_comments']);
    }

    public function testDashboardCardsAndSections(): void
    {
        $cards = $this->service->dashboardCards();
        self::assertSame('total-pages', $cards[0]['id']);
        self::assertSame(0, $cards[0]['value']);
        self::assertSame('/page', $cards[0]['href']);

        $sections = $this->service->dashboardSections();
        self::assertSame('recent-pages', $sections[0]['id']);
        self::assertSame([], $sections[0]['items']);
        self::assertSame('No pages have been created yet.', $sections[0]['empty_state']);

        $this->service->createPage(['title' => 'Live', 'status' => 'published'], 1);
        $this->service->createPage(['title' => 'Draft', 'status' => 'draft'], 1);

        $cards = $this->service->dashboardCards();
        self::assertSame(2, $cards[0]['value']);

        $sections = $this->service->dashboardSections();
        self::assertCount(2, $sections[0]['items']);
        $emphases = array_column($sections[0]['items'], 'emphasis');
        self::assertContains('success', $emphases);
        self::assertContains('secondary', $emphases);
        foreach ($sections[0]['items'] as $item) {
            self::assertStringContainsString('/page/', $item['href']);
            self::assertStringContainsString('·', $item['meta']);
        }
    }

    public function testRoutePrefixTrimsTrailingSlash(): void
    {
        $service = new PagesService($this->pdo, ['route_prefix' => '/page/']);
        $cards = $service->dashboardCards();
        self::assertSame('/page', $cards[0]['href']);

        $missing = new PagesService($this->pdo, []);
        self::assertSame('', $missing->dashboardCards()[0]['href']);
    }

    public function testPruneDefaultsToFifteenWithoutConfig(): void
    {
        $service = new PagesService($this->pdo, ['route_prefix' => '/page']);
        $page = $service->createPage(['title' => 'Many', 'status' => 'draft'], 1);
        $id = (int) $page->id;
        for ($i = 0; $i < 20; $i++) {
            $service->updatePage($id, ['title' => "Many {$i}"], 1);
        }
        // 1 create + 20 updates = 21 snapshots, pruned to default 15.
        self::assertCount(15, $service->getRevisions($id));
    }
}
