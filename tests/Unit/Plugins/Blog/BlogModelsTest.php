<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Models\Category;
use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Blog\Models\PostCategory;
use Pubvana\Plugins\Blog\Models\PostRevision;
use Pubvana\Plugins\Blog\Models\PostTag;
use Pubvana\Plugins\Blog\Models\Tag;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Blog models over the blog schema.
 *
 * The blog tables live outside the shared Sqlite schema (see
 * BlogTaxonomyPaginationTest), so this suite creates them per test.
 */
#[CoversClass(Post::class)]
#[CoversClass(Category::class)]
#[CoversClass(Tag::class)]
#[CoversClass(PostRevision::class)]
#[CoversClass(PostCategory::class)]
#[CoversClass(PostTag::class)]
final class BlogModelsTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        BlogSchema::create($this->pdo);
    }

    public function testPostFinders(): void
    {
        $id = $this->insertPost('Hello', 'hello', 'published');

        $post = (new Post($this->pdo))->findById($id);
        self::assertNotNull($post);
        self::assertSame('Hello', $post->title);

        self::assertNull((new Post($this->pdo))->findById(999));

        $bySlug = (new Post($this->pdo))->findBySlug('hello');
        self::assertNotNull($bySlug);
        self::assertSame($id, (int) $bySlug->id);

        // Drafts are invisible to the slug finder.
        $this->insertPost('Draft', 'draft-post', 'draft');
        self::assertNull((new Post($this->pdo))->findBySlug('draft-post'));

        // Soft-deleted rows are invisible everywhere.
        $this->pdo->exec("UPDATE posts SET deleted_at = '2026-01-01 00:00:00' WHERE id = {$id}");
        self::assertNull((new Post($this->pdo))->findById($id));
        self::assertNull((new Post($this->pdo))->findBySlug('hello'));
    }

    public function testPostPreviewToken(): void
    {
        $id = $this->insertPost('T', 't', 'draft');
        $post = (new Post($this->pdo))->findById($id);
        self::assertNotNull($post);

        $token = $post->generatePreviewToken();
        self::assertSame(64, strlen($token));

        $found = (new Post($this->pdo))->findByPreviewToken($token);
        self::assertNotNull($found);
        self::assertSame($id, (int) $found->id);
        self::assertNull((new Post($this->pdo))->findByPreviewToken('nope'));
    }

    public function testPostSlugExists(): void
    {
        $id = $this->insertPost('Hello', 'hello', 'published');
        $model = new Post($this->pdo);

        self::assertTrue($model->slugExists('hello'));
        self::assertFalse($model->slugExists('missing'));
        self::assertFalse($model->slugExists('hello', $id));
        self::assertTrue($model->slugExists('hello', $id + 99));
    }

    public function testPostAuthorIdForSlug(): void
    {
        $this->insertPost('Hello', 'hello', 'published');

        $model = new Post($this->pdo);
        self::assertSame(1, $model->authorIdForSlug('hello'));
        self::assertNull($model->authorIdForSlug('missing'));

        // Drafts are invisible to the lean lookup.
        $this->insertPost('Draft', 'draft-post', 'draft');
        self::assertNull($model->authorIdForSlug('draft-post'));

        // Soft-deleted rows are invisible too.
        $this->pdo->exec("UPDATE posts SET deleted_at = '2026-01-01 00:00:00' WHERE slug = 'hello'");
        self::assertNull($model->authorIdForSlug('hello'));
    }

    public function testPostPaginateAndCount(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->insertPost("P{$i}", "p{$i}", 'published');
        }
        $this->insertPost('D1', 'd1', 'draft');

        $model = new Post($this->pdo);
        self::assertSame(6, $model->countAll());
        self::assertSame(5, $model->countAll('published'));
        self::assertSame(1, $model->countAll('draft'));

        $page1 = $model->paginate(1, 2, 'published');
        self::assertCount(2, $page1);
        self::assertGreaterThan((int) $page1[1]->id, (int) $page1[0]->id);

        $page3 = $model->paginate(3, 2, 'published');
        self::assertCount(1, $page3);

        $recent = $model->publishedRecent(3);
        self::assertCount(3, $recent);
    }

    public function testPostCreateUpdateSoftDeleteViews(): void
    {
        $model = new Post($this->pdo);
        $post = $model->createRecord([
            'title' => 'New', 'slug' => 'new-post', 'status' => 'draft', 'author_id' => 1,
        ]);
        self::assertGreaterThan(0, (int) $post->id);
        self::assertNotEmpty($post->created_at);

        $post->updateRecord(['title' => 'Renamed', 'status' => 'published', 'bogus' => 'ignored']);
        $fresh = (new Post($this->pdo))->findById((int) $post->id);
        self::assertNotNull($fresh);
        self::assertSame('Renamed', $fresh->title);
        self::assertSame('published', $fresh->status);

        $post->incrementViews();
        $fresh2 = (new Post($this->pdo))->findById((int) $post->id);
        self::assertNotNull($fresh2);
        self::assertSame(1, (int) $fresh2->views);

        $post->incrementViewsDirect((int) $post->id);
        $fresh3 = (new Post($this->pdo))->findById((int) $post->id);
        self::assertNotNull($fresh3);
        self::assertSame(2, (int) $fresh3->views);

        // Direct increment on a missing row is a no-op.
        $model->incrementViewsDirect(99999);

        $post->softDelete();
        self::assertNull((new Post($this->pdo))->findById((int) $post->id));
    }

    public function testCategoryCrud(): void
    {
        $model = new Category($this->pdo);
        $cat = $model->createRecord(['name' => 'News', 'slug' => 'news']);
        self::assertGreaterThan(0, (int) $cat->id);

        self::assertNotNull((new Category($this->pdo))->findById((int) $cat->id));
        self::assertNotNull((new Category($this->pdo))->findBySlug('news'));
        self::assertNull((new Category($this->pdo))->findBySlug('nope'));
        self::assertTrue($model->slugExists('news'));
        self::assertFalse($model->slugExists('news', (int) $cat->id));

        $cat->updateRecord(['name' => 'Updated', 'bogus' => 'x']);
        $fresh = (new Category($this->pdo))->findById((int) $cat->id);
        self::assertNotNull($fresh);
        self::assertSame('Updated', $fresh->name);

        $all = $model->getAll();
        self::assertCount(1, $all);
    }

    public function testTagFindOrCreate(): void
    {
        $model = new Tag($this->pdo);
        $first = $model->findOrCreate('PHP', 'php');
        $second = $model->findOrCreate('PHP', 'php');
        self::assertSame((int) $first->id, (int) $second->id);

        self::assertNotNull((new Tag($this->pdo))->findById((int) $first->id));
        self::assertNotNull((new Tag($this->pdo))->findBySlug('php'));
        self::assertNull((new Tag($this->pdo))->findBySlug('nope'));

        $byIds = $model->findByIds([(int) $first->id, 999, 0, -1]);
        self::assertCount(1, $byIds);
        self::assertArrayHasKey((int) $first->id, $byIds);
        self::assertSame([], $model->findByIds([]));
        self::assertSame([], $model->findByIds([0, -5]));

        self::assertCount(1, $model->getAll());
    }

    public function testPostCategoryPivot(): void
    {
        $postId = $this->insertPost('P', 'p', 'published');
        $pivot = new PostCategory($this->pdo);

        self::assertSame([], $pivot->getCategoryIds($postId));

        $pivot->syncForPost($postId, [3, 7]);
        self::assertSame([3, 7], $pivot->getCategoryIds($postId));

        $pivot->syncForPost($postId, [7]);
        self::assertSame([7], $pivot->getCategoryIds($postId));

        $pivot->deleteForCategory(7);
        self::assertSame([], $pivot->getCategoryIds($postId));
    }

    public function testPostTagPivot(): void
    {
        $postId = $this->insertPost('P', 'p', 'published');
        $pivot = new PostTag($this->pdo);

        self::assertSame([], $pivot->getTagIds($postId));

        $pivot->syncForPost($postId, [2, 5]);
        self::assertSame([2, 5], $pivot->getTagIds($postId));

        $pivot->syncForPost($postId, []);
        self::assertSame([], $pivot->getTagIds($postId));

        $pivot->syncForPost($postId, [9]);
        $pivot->deleteForTag(9);
        self::assertSame([], $pivot->getTagIds($postId));
    }

    public function testRevisions(): void
    {
        $postId = $this->insertPost('V1', 'v1', 'draft');
        $post = (new Post($this->pdo))->findById($postId);
        self::assertNotNull($post);

        $revisions = new PostRevision($this->pdo);
        $revisions->createFromPost($post, 4);
        $post->updateRecord(['title' => 'V2']);
        $revisions->createFromPost($post, 4);

        $all = $revisions->getForPost($postId);
        self::assertCount(2, $all);
        self::assertSame('V2', $all[0]->title);
        self::assertSame(4, (int) $all[0]->author_id);
        self::assertNotNull($revisions->findById((int) $all[0]->id));
        self::assertNull($revisions->findById(99999));

        // Prune keeps the newest N.
        $revisions->pruneForPost($postId, 1);
        self::assertCount(1, $revisions->getForPost($postId));

        // Prune within budget is a no-op.
        $revisions->pruneForPost($postId, 15);
        self::assertCount(1, $revisions->getForPost($postId));
    }

    public function testFindersRunOnFreshInstances(): void
    {
        $id = $this->insertPost('Hello', 'hello', 'published');

        $model = new Post($this->pdo);
        $first = $model->findById($id);
        $second = $model->findById($id);

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($model, $first);
        self::assertNotSame($first, $second);

        $bySlug = $model->findBySlug('hello');
        self::assertNotNull($bySlug);
        self::assertNotSame($first, $bySlug);

        // Misses stay null on a reused model.
        self::assertNull($model->findById(999));
        self::assertNull($model->findBySlug('does-not-exist'));
    }

    private function insertPost(string $title, string $slug, string $status): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO posts (title, slug, content, excerpt, status, author_id, published_at, created_at, updated_at)
             VALUES (:t, :s, :c, :e, :st, 1, :now, :now, :now)'
        );
        $stmt->execute([':t' => $title, ':s' => $slug, ':c' => 'body', ':e' => 'ex', ':st' => $status, ':now' => $now]);

        return (int) $this->pdo->lastInsertId();
    }
}
