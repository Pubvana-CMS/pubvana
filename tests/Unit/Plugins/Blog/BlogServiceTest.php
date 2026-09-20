<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Services\BlogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * BlogService CRUD, revisions, taxonomy sync, blocks, search, dashboard.
 */
#[CoversClass(BlogService::class)]
final class BlogServiceTest extends TestCase
{
    private PDO $pdo;
    private BlogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        BlogSchema::create($this->pdo);
        $this->service = new BlogService($this->pdo, ['route_prefix' => '/blog', 'max_revisions' => 3]);

        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
        ]);
        \Flight::setEngine($app);
    }

    public function testCreatePostPurifiesAndSnapshotsRevision(): void
    {
        $post = $this->service->createPost([
            'title' => 'Hello',
            'slug' => 'hello',
            'content' => '<p>Hi</p><script>evil()</script>',
            'status' => 'draft',
        ], 7);

        self::assertGreaterThan(0, (int) $post->id);
        self::assertSame(7, (int) $post->author_id);
        self::assertStringNotContainsString('<script>', (string) $post->content);
        self::assertNotEmpty($post->preview_token);
        self::assertCount(1, $this->service->getRevisions((int) $post->id));
    }

    public function testCreatePostSkipsPurifyWhenFlagged(): void
    {
        $post = $this->service->createPost([
            'title' => 'Raw',
            'slug' => 'raw',
            'content' => '<script>keep</script>',
            'status' => 'draft',
            'purify_content' => false,
        ], 1);

        self::assertStringContainsString('<script>', (string) $post->content);
    }

    public function testUpdatePostSnapshotsBeforeAndPrunesAfter(): void
    {
        $post = $this->service->createPost(['title' => 'V1', 'slug' => 'v1', 'status' => 'draft'], 1);
        $id = (int) $post->id;

        // max_revisions=3: create (1) + 4 updates (4 more) = 5, pruned to 3.
        for ($i = 2; $i <= 5; $i++) {
            $updated = $this->service->updatePost($id, ['title' => "V{$i}", 'purify_content' => false], 1);
            self::assertNotNull($updated);
        }
        self::assertCount(3, $this->service->getRevisions($id));

        self::assertNull($this->service->updatePost(99999, ['title' => 'x'], 1));
    }

    public function testDeletePost(): void
    {
        $post = $this->service->createPost(['title' => 'Gone', 'slug' => 'gone', 'status' => 'draft'], 1);
        $id = (int) $post->id;

        self::assertTrue($this->service->deletePost($id));
        self::assertNull($this->service->findPost($id));
        self::assertFalse($this->service->deletePost($id));
        self::assertFalse($this->service->deletePost(99999));
    }

    public function testRestoreRevision(): void
    {
        $post = $this->service->createPost(['title' => 'V1', 'slug' => 'v1', 'content' => 'one', 'status' => 'draft'], 1);
        $id = (int) $post->id;
        $this->service->updatePost($id, ['title' => 'V2', 'content' => 'two', 'purify_content' => false], 1);

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

        // Cross-post revision id is refused.
        $other = $this->service->createPost(['title' => 'Other', 'slug' => 'other', 'status' => 'draft'], 1);
        $otherRevs = $this->service->getRevisions((int) $other->id);
        self::assertNull($this->service->restoreRevision($id, (int) $otherRevs[0]->id, 1));
    }

    public function testRecordViewIncrements(): void
    {
        $post = $this->service->createPost(['title' => 'V', 'slug' => 'v', 'status' => 'published'], 1);
        $id = (int) $post->id;

        $this->service->recordView($id);
        $this->service->recordView($id);

        $fresh = $this->service->findPost($id);
        self::assertNotNull($fresh);
        self::assertSame(2, (int) $fresh->views);
    }

    public function testListWrappers(): void
    {
        $this->service->createPost(['title' => 'A', 'slug' => 'a', 'status' => 'published'], 1);
        $this->service->createPost(['title' => 'B', 'slug' => 'b', 'status' => 'draft'], 1);

        $all = $this->service->listPosts(1, 25);
        self::assertSame(2, $all['total']);

        $published = $this->service->listPublished(1, 25);
        self::assertSame(1, $published['total']);

        self::assertNotNull($this->service->findPostBySlug('a'));
        self::assertTrue($this->service->postSlugExists('a'));
        self::assertFalse($this->service->postSlugExists('missing'));
    }

    public function testCategoryCrud(): void
    {
        $cat = $this->service->createCategory(['name' => 'News', 'slug' => 'news']);
        self::assertGreaterThan(0, (int) $cat->id);
        self::assertNotNull($this->service->findCategory((int) $cat->id));
        self::assertNotNull($this->service->findCategoryBySlug('news'));
        self::assertTrue($this->service->categorySlugExists('news'));
        self::assertFalse($this->service->categorySlugExists('news', (int) $cat->id));

        $updated = $this->service->updateCategory((int) $cat->id, ['name' => 'Renamed']);
        self::assertNotNull($updated);
        self::assertSame('Renamed', $updated->name);
        self::assertNull($this->service->updateCategory(99999, ['name' => 'x']));

        self::assertCount(1, $this->service->listCategories());
        self::assertTrue($this->service->deleteCategory((int) $cat->id));
        self::assertFalse($this->service->deleteCategory((int) $cat->id));
    }

    public function testTagDelete(): void
    {
        $post = $this->service->createPost(['title' => 'T', 'slug' => 't', 'status' => 'draft'], 1);
        $this->service->syncPostTags((int) $post->id, 'php, testing');

        $tag = $this->service->findTagBySlug('php');
        self::assertNotNull($tag);
        self::assertNotNull($this->service->findTag((int) $tag->id));
        self::assertCount(2, $this->service->listTags());

        self::assertTrue($this->service->deleteTag((int) $tag->id));
        self::assertFalse($this->service->deleteTag((int) $tag->id));
        self::assertFalse($this->service->deleteTag(99999));
    }

    public function testSyncPostTags(): void
    {
        $post = $this->service->createPost(['title' => 'T', 'slug' => 't', 'status' => 'draft'], 1);
        $id = (int) $post->id;

        $this->service->syncPostTags($id, 'php, testing, php, , !!!');
        self::assertSame(['php', 'testing'], $this->service->getPostTagNames($id));

        $this->service->syncPostTags($id, '');
        self::assertSame([], $this->service->getPostTagNames($id));
    }

    public function testSyncPostCategories(): void
    {
        $post = $this->service->createPost(['title' => 'T', 'slug' => 't', 'status' => 'draft'], 1);
        $id = (int) $post->id;
        $cat = $this->service->createCategory(['name' => 'News', 'slug' => 'news']);

        $this->service->syncPostCategories($id, [(int) $cat->id]);
        self::assertSame([(int) $cat->id], $this->service->getPostCategoryIds($id));
    }

    public function testCategoryItemsForPostIds(): void
    {
        $cat = $this->service->createCategory(['name' => 'News', 'slug' => 'news']);
        $post = $this->service->createPost(['title' => 'T', 'slug' => 't', 'status' => 'draft'], 1);
        $id = (int) $post->id;
        $this->service->syncPostCategories($id, [(int) $cat->id]);

        self::assertSame([], $this->service->categoryItemsForPostIds([], '/blog'));

        $map = $this->service->categoryItemsForPostIds([$id, 99999], '/blog');
        self::assertCount(1, $map[$id]);
        self::assertSame('News', $map[$id][0]['name']);
        self::assertSame('/blog/category/news', $map[$id][0]['url']);
        self::assertSame([], $map[99999]);
    }

    public function testTagItemsForPostIds(): void
    {
        $post = $this->service->createPost(['title' => 'T', 'slug' => 't', 'status' => 'draft'], 1);
        $id = (int) $post->id;
        $this->service->syncPostTags($id, 'php');

        self::assertSame([], $this->service->tagItemsForPostIds([], '/blog'));

        $map = $this->service->tagItemsForPostIds([$id], '/blog');
        self::assertCount(1, $map[$id]);
        self::assertSame('php', $map[$id][0]['name']);
        self::assertSame('/blog/tag/php', $map[$id][0]['url']);
    }

    public function testAuthorItemsForIds(): void
    {
        self::assertSame([], $this->service->authorItemsForIds([]));
        self::assertSame([], $this->service->authorItemsForIds([0, -1]));

        $this->pdo->exec("INSERT INTO users (username, active) VALUES ('alice', 1)");
        $uid = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO profiles (user_id, display_name) VALUES ({$uid}, 'Alice A')");

        $map = $this->service->authorItemsForIds([$uid, 99999]);
        self::assertSame('alice', $map[$uid]['username']);
        self::assertSame('Alice A', $map[$uid]['name']);
        self::assertSame('/profile/alice', $map[$uid]['url']);
        self::assertNull($map[99999]);
    }

    public function testBlocks(): void
    {
        $this->service->createPost(['title' => 'P1', 'slug' => 'p1', 'status' => 'published', 'published_at' => '2026-01-05 10:00:00'], 1);
        $this->service->createPost(['title' => 'P2', 'slug' => 'p2', 'status' => 'published', 'published_at' => '2026-02-05 10:00:00'], 1);
        $this->service->createCategory(['name' => 'News', 'slug' => 'news']);
        $post = $this->service->createPost(['title' => 'P3', 'slug' => 'p3', 'status' => 'draft'], 1);
        $this->service->syncPostTags((int) $post->id, 'php');

        $recent = $this->service->recentPostsBlock(['count' => 5], '/blog');
        self::assertSame('Recent Posts', $recent['title']);
        self::assertCount(2, $recent['posts']);
        self::assertSame('/blog/p2', $recent['posts'][0]['url']);

        $cats = $this->service->categoriesBlock([], '/blog');
        self::assertSame('Categories', $cats['title']);
        self::assertCount(1, $cats['categories']);

        $tags = $this->service->tagsBlock(['title' => 'T'], '/blog');
        self::assertSame('T', $tags['title']);
        self::assertCount(1, $tags['tags']);

        $archive = $this->service->archiveBlock([], '/blog');
        self::assertCount(2, $archive['months']);
        self::assertSame('2026', (string) $archive['months'][0]['year']);
        self::assertSame('February 2026', $archive['months'][0]['label']);
        self::assertSame('/blog/archive/2026/02', $archive['months'][0]['url']);

        $related = $this->service->relatedPostsBlock([], ['post_id' => 0], '/blog');
        self::assertSame([], $related['posts']);
    }

    public function testRelatedPostsScoresSharedTaxonomy(): void
    {
        $cat = $this->service->createCategory(['name' => 'News', 'slug' => 'news']);
        $a = $this->service->createPost(['title' => 'A', 'slug' => 'a', 'status' => 'published'], 1);
        $b = $this->service->createPost(['title' => 'B', 'slug' => 'b', 'status' => 'published'], 1);
        $c = $this->service->createPost(['title' => 'C', 'slug' => 'c', 'status' => 'published'], 1);
        $this->service->syncPostTags((int) $a->id, 'php');
        $this->service->syncPostTags((int) $b->id, 'php, testing');
        $this->service->syncPostCategories((int) $a->id, [(int) $cat->id]);
        $this->service->syncPostCategories((int) $b->id, [(int) $cat->id]);

        $related = $this->service->relatedPostsBlock(['count' => 5], ['post_id' => (int) $a->id], '/blog');
        self::assertCount(1, $related['posts']);
        self::assertSame('B', $related['posts'][0]['title']);
        self::assertSame(2, $related['posts'][0]['score']);
    }

    public function testSearchProviderScoringAndExcerpt(): void
    {
        $this->service->createPost(['title' => 'PHP Guide', 'slug' => 'php-guide', 'content' => 'Learn PHP here', 'excerpt' => 'intro', 'status' => 'published'], 1);
        $this->service->createPost(['title' => 'Other', 'slug' => 'other', 'content' => 'nothing relevant at all in this body text', 'status' => 'published'], 1);

        $results = $this->service->searchProvider('PHP Guide', '/blog');
        self::assertCount(1, $results);
        self::assertSame('PHP Guide', $results[0]['title']);
        self::assertSame('/blog/php-guide', $results[0]['url']);
        self::assertSame('Post', $results[0]['content_type']);
        self::assertGreaterThan(10, $results[0]['relevance']);
    }

    public function testCommentHostItems(): void
    {
        $this->service->createPost(['title' => 'Live', 'slug' => 'live', 'status' => 'published', 'allow_comments' => 1], 1);
        $this->service->createPost(['title' => 'Draft', 'slug' => 'draft-x', 'status' => 'draft'], 1);

        $items = $this->service->commentHostItems('/blog');
        self::assertCount(1, $items);
        self::assertSame('blog', $items[0]['type']);
        self::assertSame('/blog/live', $items[0]['url']);
        self::assertTrue($items[0]['allow_comments']);
    }

    public function testNavLinkableAndBrokenLinksItems(): void
    {
        $this->service->createPost(['title' => 'Live', 'slug' => 'live', 'content' => '<p>Body text</p>', 'status' => 'published'], 1);
        $this->service->createPost(['title' => 'Draft', 'slug' => 'draft-x', 'status' => 'draft'], 1);

        $nav = $this->service->navLinkableItems('/blog');
        self::assertCount(1, $nav);
        self::assertSame('Live', $nav[0]['label']);
        self::assertSame('/blog/live', $nav[0]['url']);

        $links = $this->service->brokenLinksItems();
        self::assertCount(1, $links);
        self::assertSame('post', $links[0]['type']);
        self::assertSame('Live', $links[0]['title']);
        self::assertStringContainsString('Body text', $links[0]['content']);
    }

    public function testDashboard(): void
    {
        $this->service->createPost(['title' => 'P', 'slug' => 'p', 'status' => 'published'], 1);
        $this->service->createPost(['title' => 'D', 'slug' => 'd', 'status' => 'draft'], 1);
        $this->service->createPost(['title' => 'S', 'slug' => 's', 'status' => 'scheduled'], 1);

        $cards = $this->service->dashboardCards();
        self::assertCount(3, $cards);
        self::assertSame(1, $cards[0]['value']);
        self::assertSame('/blog?status=published', $cards[0]['href']);

        $sections = $this->service->dashboardSections();
        self::assertSame('recent-posts', $sections[0]['id']);
        self::assertCount(3, $sections[0]['items']);
        // Newest first: scheduled, draft, published.
        self::assertSame('info', $sections[0]['items'][0]['emphasis']);
        self::assertSame('secondary', $sections[0]['items'][1]['emphasis']);
        self::assertSame('success', $sections[0]['items'][2]['emphasis']);
    }

    public function testFindPostByPreviewToken(): void
    {
        $post = $this->service->createPost(['title' => 'P', 'slug' => 'p', 'status' => 'draft'], 1);
        $found = $this->service->findPostByPreviewToken((string) $post->preview_token);
        self::assertNotNull($found);
        self::assertNull($this->service->findPostByPreviewToken('nope'));
    }
}
