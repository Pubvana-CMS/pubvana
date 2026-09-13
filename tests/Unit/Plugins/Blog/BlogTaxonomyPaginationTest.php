<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Blog\Models\Category;
use Pubvana\Plugins\Blog\Models\Tag;
use Pubvana\Plugins\Blog\Services\BlogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Per-taxonomy pagination for the blog.
 *
 * Category and tag archive pages now paginate by taxonomy, not globally.
 * These tests seed enough posts to span two pages and verify that the
 * query methods and service wrappers return the correct scoped slices.
 */
#[CoversClass(Post::class)]
#[CoversClass(BlogService::class)]
final class BlogTaxonomyPaginationTest extends TestCase
{
    private PDO $pdo;
    private BlogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->createBlogSchema($this->pdo);
        $this->service = new BlogService($this->pdo, []);
    }

    /**
     * Blog tables, mirroring the migration column shapes. Not added to the
     * shared Sqlite schema because AiAssistant defines its own narrower
     * posts table; this suite owns the blog tables it exercises.
     */
    private function createBlogSchema(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL UNIQUE,
                content        TEXT,
                excerpt        TEXT,
                status         TEXT NOT NULL DEFAULT 'draft',
                featured_image TEXT,
                media_id       INTEGER,
                author_id      INTEGER NOT NULL,
                published_at   TEXT,
                views          INTEGER NOT NULL DEFAULT 0,
                is_featured    INTEGER NOT NULL DEFAULT 0,
                allow_comments INTEGER NOT NULL DEFAULT 1,
                ai_generated   INTEGER NOT NULL DEFAULT 0,
                preview_token  TEXT UNIQUE,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE categories (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                name        TEXT NOT NULL,
                slug        TEXT NOT NULL UNIQUE,
                description TEXT,
                parent_id   INTEGER,
                created_at  TEXT,
                updated_at  TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE tags (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT NOT NULL,
                slug       TEXT NOT NULL UNIQUE,
                created_at TEXT,
                updated_at TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE posts_to_categories (
                post_id     INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                UNIQUE (post_id, category_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE tags_to_posts (
                tag_id  INTEGER NOT NULL,
                post_id INTEGER NOT NULL,
                UNIQUE (tag_id, post_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE post_revisions (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id    INTEGER NOT NULL,
                author_id  INTEGER NOT NULL,
                title      TEXT,
                content    TEXT,
                excerpt    TEXT,
                status     TEXT,
                created_at TEXT
            )"
        );
    }

    private function seed(int $publishedCount, int $draftCount, array $categoryIds): void
    {
        $authorId = 1;
        $now = date('Y-m-d H:i:s');

        for ($i = 1; $i <= $publishedCount; $i++) {
            $this->pdo->exec(
                "INSERT INTO posts (title, slug, status, author_id, published_at, created_at, updated_at)
                 VALUES ('Post $i', 'post-$i', 'published', $authorId, '$now', '$now', '$now')"
            );
        }

        for ($i = $publishedCount + 1; $i <= $publishedCount + $draftCount; $i++) {
            $this->pdo->exec(
                "INSERT INTO posts (title, slug, status, author_id, created_at, updated_at)
                 VALUES ('Draft $i', 'draft-$i', 'draft', $authorId, '$now', '$now')"
            );
        }

        foreach ($categoryIds as $catId) {
            $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES ($catId, 'Cat $catId', 'cat-$catId')");
        }
    }

    private function assignCategory(int $postId, int $categoryId): void
    {
        $this->pdo->exec(
            "INSERT INTO posts_to_categories (post_id, category_id) VALUES ($postId, $categoryId)"
        );
    }

    private function createTag(int $tagId): void
    {
        $this->pdo->exec("INSERT INTO tags (id, name, slug) VALUES ($tagId, 'Tag $tagId', 'tag-$tagId')");
    }

    private function assignTag(int $postId, int $tagId): void
    {
        $this->pdo->exec(
            "INSERT INTO tags_to_posts (tag_id, post_id) VALUES ($tagId, $postId)"
        );
    }

    private function softDeletePost(int $postId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("UPDATE posts SET deleted_at = '$now' WHERE id = $postId");
    }

    // ─── Category pagination ────────────────────────────────────────

    public function testCountByCategoryIncludesOnlyPublished(): void
    {
        $this->seed(15, 3, [1]);
        foreach (range(1, 15) as $id) {
            $this->assignCategory($id, 1);
        }

        $count = (new Post($this->pdo))->countByCategory(1, 'published');

        self::assertSame(15, $count);
    }

    public function testCountByCategoryExcludesDrafts(): void
    {
        $this->seed(10, 5, [1]);
        foreach (range(1, 10) as $id) {
            $this->assignCategory($id, 1);
        }
        foreach (range(11, 15) as $id) {
            $this->assignCategory($id, 1);
        }

        $count = (new Post($this->pdo))->countByCategory(1, 'published');

        self::assertSame(10, $count);
    }

    public function testCountByCategoryExcludesSoftDeletedPosts(): void
    {
        $this->seed(10, 0, [1]);
        foreach (range(1, 10) as $id) {
            $this->assignCategory($id, 1);
        }
        $this->softDeletePost(10);

        $count = (new Post($this->pdo))->countByCategory(1, 'published');

        self::assertSame(9, $count);
    }

    public function testPaginateByCategoryReturnsScopedPage(): void
    {
        $this->seed(12, 0, [1]);
        foreach (range(1, 12) as $id) {
            $this->assignCategory($id, 1);
        }

        $postModel = new Post($this->pdo);
        $page1 = $postModel->paginateByCategory(1, 1, 5, 'published');
        $page3 = $postModel->paginateByCategory(1, 3, 5, 'published');

        self::assertCount(5, $page1);
        self::assertCount(2, $page3);

        $slugsP1 = array_column($page1, 'slug');
        self::assertContains('post-12', $slugsP1);
        self::assertContains('post-8', $slugsP1);
        self::assertNotContains('post-7', $slugsP1);

        $slugsP3 = array_column($page3, 'slug');
        self::assertContains('post-2', $slugsP3);
        self::assertContains('post-1', $slugsP3);
    }

    public function testPaginateByCategoryExcludesOtherCategoriesPosts(): void
    {
        $this->seed(6, 0, [1, 2]);
        foreach (range(1, 6) as $id) {
            $this->assignCategory($id, 1);
        }
        foreach (range(4, 6) as $id) {
            $this->assignCategory($id, 2);
        }

        $page1 = (new Post($this->pdo))->paginateByCategory(1, 1, 10, 'published');
        $slugs = array_column($page1, 'slug');

        self::assertCount(6, $slugs);
        self::assertContains('post-3', $slugs);
        self::assertContains('post-4', $slugs);
    }

    public function testServiceListPostsByCategoryPagination(): void
    {
        $this->seed(12, 0, [1]);
        foreach (range(1, 12) as $id) {
            $this->assignCategory($id, 1);
        }

        $result = $this->service->listPostsByCategory(1, 1, 5);

        self::assertSame(12, $result['total']);
        self::assertSame(1, $result['page']);
        self::assertSame(5, $result['per_page']);
        self::assertCount(5, $result['items']);

        $slugs = array_column($result['items'], 'slug');
        self::assertContains('post-12', $slugs);
        self::assertNotContains('post-1', $slugs);
    }

    public function testCategoryWithZeroPublishedPostsReturnsEmptyResult(): void
    {
        $this->seed(0, 3, [1]);
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (2, 'Empty', 'empty')");

        $result = $this->service->listPostsByCategory(2, 1, 10);

        self::assertSame(0, $result['total']);
        self::assertCount(0, $result['items']);
    }

    // ─── Tag pagination ─────────────────────────────────────────────

    public function testCountByTagIncludesOnlyPublished(): void
    {
        $this->seed(15, 3, []);
        $this->createTag(1);
        foreach (range(1, 15) as $id) {
            $this->assignTag($id, 1);
        }

        $count = (new Post($this->pdo))->countByTag(1, 'published');

        self::assertSame(15, $count);
    }

    public function testPaginateByTagReturnsScopedPage(): void
    {
        $this->seed(12, 0, []);
        $this->createTag(1);
        foreach (range(1, 12) as $id) {
            $this->assignTag($id, 1);
        }

        $postModel = new Post($this->pdo);
        $page1 = $postModel->paginateByTag(1, 1, 5, 'published');
        $page3 = $postModel->paginateByTag(1, 3, 5, 'published');

        self::assertCount(5, $page1);
        self::assertCount(2, $page3);
    }

    public function testServiceListPostsByTagPagination(): void
    {
        $this->seed(12, 0, []);
        $this->createTag(1);
        foreach (range(1, 12) as $id) {
            $this->assignTag($id, 1);
        }

        $result = $this->service->listPostsByTag(1, 2, 5);

        self::assertSame(12, $result['total']);
        self::assertSame(2, $result['page']);
        self::assertCount(5, $result['items']);
    }

    public function testTagWithZeroPublishedPostsReturnsEmptyResult(): void
    {
        $this->seed(0, 3, []);
        $this->createTag(99);

        $result = $this->service->listPostsByTag(99, 1, 10);

        self::assertSame(0, $result['total']);
        self::assertCount(0, $result['items']);
    }

    // ─── Slug lookup ────────────────────────────────────────────────

    public function testFindCategoryBySlugReturnsCategory(): void
    {
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (7, 'Tech', 'tech')");

        $cat = $this->service->findCategoryBySlug('tech');

        self::assertNotNull($cat);
        self::assertSame('Tech', $cat->name);
    }

    public function testFindCategoryBySlugReturnsNullOnMiss(): void
    {
        self::assertNull($this->service->findCategoryBySlug('nonexistent'));
    }

    public function testFindTagBySlugReturnsTag(): void
    {
        $this->pdo->exec("INSERT INTO tags (id, name, slug) VALUES (7, 'PHP', 'php')");

        $tag = $this->service->findTagBySlug('php');

        self::assertNotNull($tag);
        self::assertSame('PHP', $tag->name);
    }

    public function testFindTagBySlugReturnsNullOnMiss(): void
    {
        self::assertNull($this->service->findTagBySlug('nonexistent'));
    }

    // ─── No pagination on small result sets ──────────────────────────

    public function testSmallCategoryListReturnsNoPaginationData(): void
    {
        $this->seed(3, 0, [1]);
        foreach (range(1, 3) as $id) {
            $this->assignCategory($id, 1);
        }

        $result = $this->service->listPostsByCategory(1, 1, 10);

        self::assertSame(3, $result['total']);
        self::assertCount(3, $result['items']);
    }
}
