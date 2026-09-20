<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Plugins\Pages\Models\PageRevision;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Pages models over the pages schema.
 */
#[CoversClass(Page::class)]
#[CoversClass(PageRevision::class)]
final class PagesModelsTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        PagesSchema::create($this->pdo);
    }

    public function testPageFinders(): void
    {
        $page = (new Page($this->pdo))->createPage('About', '<p>Hi</p>', 3);
        $id = (int) $page->id;

        self::assertSame('about', $page->slug);
        self::assertSame('draft', $page->status);

        $found = (new Page($this->pdo))->findById($id);
        self::assertNotNull($found);
        self::assertSame('About', $found->title);
        self::assertNull((new Page($this->pdo))->findById(99999));

        // Drafts are invisible to the slug finder.
        self::assertNull((new Page($this->pdo))->findBySlug('about'));

        $page->updatePage(['status' => 'published']);
        self::assertNotNull((new Page($this->pdo))->findBySlug('about'));
        self::assertNull((new Page($this->pdo))->findBySlug('nope'));

        $page->softDelete();
        self::assertNull((new Page($this->pdo))->findById($id));
        self::assertNull((new Page($this->pdo))->findBySlug('about'));
    }

    public function testSlugUniquenessAndExists(): void
    {
        $a = (new Page($this->pdo))->createPage('Hello', 'x', 1);
        $b = (new Page($this->pdo))->createPage('Hello', 'y', 1);

        self::assertSame('hello', $a->slug);
        self::assertSame('hello-1', $b->slug);

        $model = new Page($this->pdo);
        self::assertTrue($model->slugExists('hello'));
        self::assertFalse($model->slugExists('missing'));
        self::assertFalse($model->slugExists('hello', (int) $a->id));
        self::assertTrue($model->slugExists('hello', 99999));
    }

    public function testPageCreatedByForSlug(): void
    {
        $page = (new Page($this->pdo))->createPage('About', '<p>Hi</p>', 3);
        $page->updatePage(['status' => 'published']);

        $model = new Page($this->pdo);
        self::assertSame(3, $model->createdByForSlug('about'));
        self::assertNull($model->createdByForSlug('missing'));

        // Drafts are invisible to the lean lookup.
        (new Page($this->pdo))->createPage('Draft', 'x', 5);
        self::assertNull($model->createdByForSlug('draft'));

        // Soft-deleted rows are invisible too.
        $page->softDelete();
        self::assertNull($model->createdByForSlug('about'));
    }

    public function testPaginationCountPublishedOptions(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $p = (new Page($this->pdo))->createPage("P{$i}", 'x', 1);
            if ($i <= 3) {
                $p->updatePage(['status' => 'published']);
            }
        }

        $model = new Page($this->pdo);
        self::assertSame(5, $model->countAll());

        $page1 = $model->findAllPaginated(1, 2);
        self::assertCount(2, $page1);
        $page3 = $model->findAllPaginated(3, 2);
        self::assertCount(1, $page3);

        $published = $model->findAllPublished(100);
        self::assertCount(3, $published);
        // Title-ordered.
        self::assertSame('P1', $published[0]->title);

        $options = $model->getPublishedOptions();
        self::assertCount(3, $options);
        self::assertContains('P1', $options);
    }

    public function testCreatePageReturnsIndependentInstance(): void
    {
        $model = new Page($this->pdo);
        $first = $model->createPage('First', 'one', 1);
        $second = $model->createPage('Second', 'two', 1);

        // The creating model must not become the returned page, or the
        // second call would overwrite the first result.
        self::assertNotSame($first, $second);
        self::assertSame('First', $first->title);
        self::assertSame('one', $first->content);
        self::assertSame('Second', $second->title);
        self::assertNotSame((int) $first->id, (int) $second->id);
    }

    public function testFindAllPublishedLimitIsOptional(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $p = (new Page($this->pdo))->createPage("P{$i}", 'x', 1);
            $p->updatePage(['status' => 'published']);
        }
        (new Page($this->pdo))->createPage('Draft', 'x', 1);

        $model = new Page($this->pdo);

        // Null limit (the default) returns every published page, so host
        // integrations are not silently capped at 100.
        self::assertCount(3, $model->findAllPublished());
        self::assertCount(2, $model->findAllPublished(2));
    }

    public function testUpdatePageFields(): void
    {
        $page = (new Page($this->pdo))->createPage('T', 'c', 1);
        $page->updatePage([
            'title' => 'New',
            'content' => 'body',
            'status' => 'published',
            'allow_comments' => true,
            'ai_generated' => 1,
        ]);

        $fresh = (new Page($this->pdo))->findById((int) $page->id);
        self::assertNotNull($fresh);
        self::assertSame('New', $fresh->title);
        self::assertSame('published', $fresh->status);
        self::assertSame(1, (int) $fresh->allow_comments);
        self::assertSame(1, (int) $fresh->ai_generated);
    }

    public function testSearchContent(): void
    {
        $a = (new Page($this->pdo))->createPage('Contact Us', 'Email us at hello@example.org for help with your account today', 1);
        $a->updatePage(['status' => 'published']);
        $b = (new Page($this->pdo))->createPage('About', 'Our story', 1);
        $b->updatePage(['status' => 'published']);
        $c = (new Page($this->pdo))->createPage('Hidden', 'Contact hidden draft', 1);

        $results = (new Page($this->pdo))->searchContent('contact', '/pages');
        self::assertCount(1, $results);
        self::assertSame('Contact Us', $results[0]['title']);
        self::assertSame('/pages/contact-us', $results[0]['url']);
        self::assertSame('Page', $results[0]['content_type']);
        self::assertStringContainsString('hello@example.org', $results[0]['excerpt']);
    }

    public function testRevisions(): void
    {
        $page = (new Page($this->pdo))->createPage('V1', 'one', 4);
        $id = (int) $page->id;
        $revisions = new PageRevision($this->pdo);

        $revisions->createFromPage($page, 4);
        $page->updatePage(['title' => 'V2']);
        $revisions->createFromPage($page, 4);

        $all = $revisions->getForPage($id);
        self::assertCount(2, $all);
        self::assertSame('V2', $all[0]->title);
        self::assertSame(4, (int) $all[0]->author_id);
        self::assertNotNull($revisions->findById((int) $all[0]->id));
        self::assertNull($revisions->findById(99999));

        $revisions->pruneForPage($id, 1);
        self::assertCount(1, $revisions->getForPage($id));

        $revisions->pruneForPage($id, 15);
        self::assertCount(1, $revisions->getForPage($id));
    }
}
