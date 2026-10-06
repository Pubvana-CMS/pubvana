<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Services\BlogService;
use Pubvana\Plugins\Profiles\Services\ProfileBlockService;
use Pubvana\Tests\Support\CountingPdo;
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

        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
            'db'      => fn(): PDO => $this->pdo,
            'pluginLoader' => static fn(): object => new class {
                public function isEnabled(string $pluginId): bool
                {
                    return false;
                }
            },
        ]);
        \Flight::setEngine($app);

        $this->service = new BlogService($app, ['route_prefix' => '/blog', 'max_revisions' => 3]);
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

    public function testNormalizePublishDate(): void
    {
        // The admin form posts a datetime-local value.
        self::assertSame('2030-01-01 10:00:00', $this->service->normalizePublishDate('2030-01-01T10:00'));
        self::assertSame('2030-01-01 10:00:00', $this->service->normalizePublishDate('2030-01-01 10:00:00'));
        self::assertSame('2030-01-01 10:00:00', $this->service->normalizePublishDate('  2030-01-01T10:00  '));

        self::assertNull($this->service->normalizePublishDate(''));
        self::assertNull($this->service->normalizePublishDate('   '));
        self::assertNull($this->service->normalizePublishDate('not-a-date'));
    }

    public function testIsFuturePublishDate(): void
    {
        $future = (new \DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');
        $past = (new \DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s');

        self::assertTrue($this->service->isFuturePublishDate($future));
        self::assertFalse($this->service->isFuturePublishDate($past));
        self::assertFalse($this->service->isFuturePublishDate(null));

        // A date typed as "now" survives save latency.
        self::assertTrue($this->service->isFuturePublishDate(date('Y-m-d H:i:s')));
    }

    public function testPublishDuePostsFlipsOnlyDuePosts(): void
    {
        $past = (new \DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s');
        $future = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

        $due = $this->service->createPost(['title' => 'Due', 'slug' => 'due', 'status' => 'scheduled', 'published_at' => $past], 1);
        $soon = $this->service->createPost(['title' => 'Soon', 'slug' => 'soon', 'status' => 'scheduled', 'published_at' => $future], 1);
        $undated = $this->service->createPost(['title' => 'Undated', 'slug' => 'undated', 'status' => 'scheduled'], 1);
        $draft = $this->service->createPost(['title' => 'Draft', 'slug' => 'draft', 'status' => 'draft'], 1);
        $tombstoned = $this->service->createPost(['title' => 'Gone', 'slug' => 'gone', 'status' => 'scheduled', 'published_at' => $past], 1);
        $this->service->deletePost((int) $tombstoned->id);

        self::assertSame(1, $this->service->publishDuePosts());

        $flipped = $this->service->findPost((int) $due->id);
        self::assertNotNull($flipped);
        self::assertSame('published', $flipped->status);
        self::assertSame($past, $flipped->published_at);
        // Live on schedule: the first edit after publishing bumps updated_at.
        self::assertSame($past, $flipped->updated_at);

        self::assertSame('scheduled', $this->service->findPost((int) $soon->id)?->status);
        self::assertSame('scheduled', $this->service->findPost((int) $undated->id)?->status);
        self::assertSame('draft', $this->service->findPost((int) $draft->id)?->status);

        // Tombstoned rows are skipped; findPost() hides them, so read the row.
        $tombstone = $this->pdo->query("select status from posts where slug = 'gone'")->fetchColumn();
        self::assertSame('scheduled', $tombstone);

        // Idempotent: nothing left due on the next tick.
        self::assertSame(0, $this->service->publishDuePosts());
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

        // No Profiles service: the Shield username stands in, with no URL.
        $map = $this->service->authorItemsForIds([$uid, 99999]);
        self::assertSame('alice', $map[$uid]['name']);
        self::assertNull($map[$uid]['url']);
        self::assertNull($map[99999]);

        // Soft-deleted users keep their attribution, so the name stays.
        $this->pdo->exec("INSERT INTO users (username, active, deleted_at) VALUES ('gone', 1, '2026-01-01 00:00:00')");
        $goneId = (int) $this->pdo->lastInsertId();
        self::assertSame('gone', $this->service->authorItemsForIds([$goneId])[$goneId]['name']);
    }

    public function testAuthorItemsForIdsUsesProfilesWhenLoaded(): void
    {
        $this->pdo->exec("INSERT INTO users (username, active) VALUES ('alice', 1)");
        $uid = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO profiles (user_id, display_name) VALUES ({$uid}, 'Alice A')");

        $app = $this->app([
            'db'           => fn (): PDO => $this->pdo,
            'pluginLoader' => static fn (): object => new class {
                public function isEnabled(string $pluginId): bool
                {
                    return true;
                }

                public function routePrefix(string $pluginId): string
                {
                    return '/profile';
                }
            },
        ]);
        $app->map('profileBlock', static fn () => new ProfileBlockService($app));

        $service = new BlogService($app, []);
        $map = $service->authorItemsForIds([$uid]);

        self::assertSame('Alice A', $map[$uid]['name']);
        self::assertSame('/profile/' . $uid, $map[$uid]['url']);
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

    public function testTaxonomyBulkLoadersMapEveryRequestedPost(): void
    {
        $cat = $this->service->createCategory(['name' => 'News', 'slug' => 'news']);
        $a = $this->service->createPost(['title' => 'A', 'slug' => 'a', 'status' => 'draft'], 1);
        $b = $this->service->createPost(['title' => 'B', 'slug' => 'b', 'status' => 'draft'], 1);
        $this->service->syncPostTags((int) $a->id, 'php, testing');
        $this->service->syncPostCategories((int) $b->id, [(int) $cat->id]);

        self::assertSame([], $this->service->tagNamesForPostIds([]));
        self::assertSame([], $this->service->categoryIdsForPostIds([]));

        $names = $this->service->tagNamesForPostIds([(int) $a->id, (int) $b->id, 99999]);
        self::assertSame(['php', 'testing'], $names[(int) $a->id]);
        self::assertSame([], $names[(int) $b->id]);
        self::assertSame([], $names[99999]);

        $ids = $this->service->categoryIdsForPostIds([(int) $a->id, (int) $b->id, 99999]);
        self::assertSame([], $ids[(int) $a->id]);
        self::assertSame([(int) $cat->id], $ids[(int) $b->id]);
        self::assertSame([], $ids[99999]);

        // The single-post helpers go through the same batched path.
        self::assertSame(['php', 'testing'], $this->service->getPostTagNames((int) $a->id));
        self::assertSame([], $this->service->getPostCategoryIds((int) $a->id));
        self::assertSame([], $this->service->getPostTagNames(99999));
    }

    public function testUpdatePostRotatesPreviewTokenWhenPublished(): void
    {
        $post = $this->service->createPost(['title' => 'Draft', 'slug' => 'draft', 'status' => 'draft'], 1);
        $id = (int) $post->id;
        $token = (string) $post->preview_token;

        self::assertNotSame('', $token);
        self::assertNotNull($this->service->findPostByPreviewToken($token));

        $this->service->updatePost($id, ['status' => 'published', 'purify_content' => false], 1);

        $fresh = $this->service->findPost($id);
        self::assertNotNull($fresh);
        self::assertNotSame($token, (string) $fresh->preview_token);
        self::assertNull($this->service->findPostByPreviewToken($token));
        self::assertNotNull($this->service->findPostByPreviewToken((string) $fresh->preview_token));
    }

    public function testDeleteCategoryPromotesChildren(): void
    {
        $parent = $this->service->createCategory(['name' => 'Parent', 'slug' => 'parent']);
        $child = $this->service->createCategory([
            'name'      => 'Child',
            'slug'      => 'child',
            'parent_id' => (int) $parent->id,
        ]);

        self::assertTrue($this->service->deleteCategory((int) $parent->id));

        $reloaded = $this->service->findCategory((int) $child->id);
        self::assertNotNull($reloaded);
        self::assertNull($reloaded->parent_id, 'the child is promoted to the top level');
    }

    public function testPublishedPostCountsByCategoryIgnoresDrafts(): void
    {
        $cat = $this->service->createCategory(['name' => 'News', 'slug' => 'news']);

        $published = $this->service->createPost(['title' => 'P', 'slug' => 'p', 'status' => 'published'], 1);
        $draft = $this->service->createPost(['title' => 'D', 'slug' => 'd', 'status' => 'draft'], 1);

        $this->service->syncPostCategories((int) $published->id, [(int) $cat->id]);
        $this->service->syncPostCategories((int) $draft->id, [(int) $cat->id]);

        $counts = $this->service->publishedPostCountsByCategory();
        self::assertSame(1, $counts[(int) $cat->id] ?? 0, 'only published posts count');
    }

    /**
     * Related Posts scores every candidate from one batched pass. Twenty
     * candidates would otherwise cost two lookups each.
     */
    public function testRelatedPostsBatchesTaxonomyLookups(): void
    {
        $counting = CountingPdo::copyOf($this->pdo);
        $this->pdo = $counting;
        $this->service = new BlogService($this->app([
            'db'           => fn(): PDO => $counting,
            'pluginLoader' => static fn(): object => new class {
                public function isEnabled(string $pluginId): bool
                {
                    return false;
                }
            },
        ]), ['route_prefix' => '/blog']);

        $cat = $this->service->createCategory(['name' => 'News', 'slug' => 'news']);

        $target = $this->service->createPost(['title' => 'Target', 'slug' => 'target', 'status' => 'published'], 1);
        $this->service->syncPostTags((int) $target->id, 'php');
        $this->service->syncPostCategories((int) $target->id, [(int) $cat->id]);

        for ($i = 1; $i <= 20; $i++) {
            $post = $this->service->createPost([
                'title' => "P{$i}",
                'slug'  => "p{$i}",
                'status' => 'published',
            ], 1);

            if ($i === 7) {
                $this->service->syncPostTags((int) $post->id, 'php');
                $this->service->syncPostCategories((int) $post->id, [(int) $cat->id]);
            } elseif ($i === 8) {
                $this->service->syncPostTags((int) $post->id, 'php');
            } elseif ($i === 9) {
                $this->service->syncPostCategories((int) $post->id, [(int) $cat->id]);
            } else {
                $this->service->syncPostTags((int) $post->id, "tag{$i}");
            }
        }

        $counting->queries = 0;
        $related = $this->service->relatedPostsBlock(['count' => 5], ['post_id' => (int) $target->id], '/blog');
        self::assertLessThan(12, $counting->queries, 'Related Posts should not look up taxonomy per candidate.');

        self::assertSame('Related Posts', $related['title']);
        self::assertCount(3, $related['posts']);
        self::assertSame('P7', $related['posts'][0]['title']);
        self::assertSame('/blog/p7', $related['posts'][0]['url']);
        self::assertSame(2, $related['posts'][0]['score']);
        self::assertSame([1, 1], array_column(array_slice($related['posts'], 1), 'score'));
    }

    public function testSearchProviderReturnsMatchesWithoutScoring(): void
    {
        $this->service->createPost(['title' => 'PHP Guide', 'slug' => 'php-guide', 'content' => 'Learn PHP here', 'excerpt' => 'intro', 'status' => 'published'], 1);
        $this->service->createPost(['title' => 'Other', 'slug' => 'other', 'content' => 'nothing relevant at all in this body text', 'status' => 'published'], 1);

        $results = $this->service->searchProvider('PHP Guide', '/blog');
        self::assertCount(1, $results);
        self::assertSame('PHP Guide', $results[0]['title']);
        self::assertSame('/blog/php-guide', $results[0]['url']);
        self::assertSame('Post', $results[0]['content_type']);
        self::assertSame('Learn PHP here', $results[0]['content']);
        // Ranking belongs to SearchService; the provider must not ship a score.
        self::assertArrayNotHasKey('relevance', $results[0]);
    }

    public function testSearchProviderShipsStrippedBodyForScoring(): void
    {
        $this->service->createPost([
            'title'   => 'Markup Post',
            'slug'    => 'markup-post',
            'content' => '<p>Body <strong>keyword</strong> here</p>',
            'status'  => 'published',
        ], 1);

        $results = $this->service->searchProvider('keyword', '/blog');

        self::assertCount(1, $results);
        $content = (string) $results[0]['content'];
        self::assertStringContainsString('Body keyword here', $content);
        self::assertStringNotContainsString('<p>', $content);
        self::assertStringNotContainsString('<strong>', $content);
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
