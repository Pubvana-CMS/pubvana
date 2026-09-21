<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Models\Post;
use Pubvana\Plugins\Blog\Services\BlogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * LIKE wildcard safety in the blog search provider.
 *
 * A user-supplied % or _ must be treated as literal text, not as a SQL
 * wildcard. Post::searchByPattern() runs the LIKE pre-filter with an
 * explicit ESCAPE clause, and BlogService::escapeLikePattern() neutralizes
 * the wildcard characters before the pattern is built.
 *
 * The escape character is '!', not a backslash. MySQL reads a backslash
 * inside a string literal as an escaped quote, so ESCAPE '\' is a syntax
 * error there while SQLite accepts it. These tests run on SQLite, so they
 * cannot prove the clause is portable: the escape character must stay a
 * plain literal, and it must be escaped in the term by escapeLikePattern(),
 * which testBangInTermMatchesOnlyLiteralBang pins.
 */
#[CoversClass(Post::class)]
#[CoversClass(BlogService::class)]
final class BlogSearchWildcardTest extends TestCase
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
    }

    private function insertPost(int $id, string $title, string $status = 'published'): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO posts (id, title, slug, content, excerpt, status, author_id, published_at, created_at, updated_at)
             VALUES (:id, :title, :slug, :content, :excerpt, :status, :author, :published, :now, :now)'
        );
        $stmt->execute([
            ':id'        => $id,
            ':title'     => $title,
            ':slug'      => 'post-' . $id,
            ':content'   => 'Body of ' . $title,
            ':excerpt'   => 'Excerpt of ' . $title,
            ':status'    => $status,
            ':author'    => 1,
            ':published' => $now,
            ':now'       => $now,
        ]);
    }

    private function softDelete(int $id): void
    {
        $this->pdo->exec("UPDATE posts SET deleted_at = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $id);
    }

    private function titles(array $results): array
    {
        return array_map(fn(array $r): string => (string) $r['title'], $results);
    }

    public function testPercentInTermMatchesOnlyLiteralPercent(): void
    {
        $this->insertPost(1, 'foo%bar');
        $this->insertPost(2, 'fooxbar');
        $this->insertPost(3, 'foo_bar');
        $this->insertPost(4, '100%');

        $titles = $this->titles($this->service->searchProvider('foo%', ''));

        self::assertSame(['foo%bar'], $titles);

        $matches = $this->titles($this->service->searchProvider('100%', ''));
        self::assertSame(['100%'], $matches);
    }

    public function testUnderscoreInTermMatchesOnlyLiteralUnderscore(): void
    {
        $this->insertPost(1, 'foo%bar');
        $this->insertPost(2, 'fooxbar');
        $this->insertPost(3, 'foo_bar');

        $titles = $this->titles($this->service->searchProvider('foo_', ''));

        self::assertSame(['foo_bar'], $titles);
    }

    public function testBackslashInTermMatchesOnlyLiteralBackslash(): void
    {
        $this->insertPost(1, 'foo\\bar');
        $this->insertPost(2, 'fooxbar');
        $this->insertPost(3, 'foo_bar');

        $titles = $this->titles($this->service->searchProvider('foo\\', ''));

        self::assertSame(['foo\\bar'], $titles);
    }

    /**
     * '!' is the escape character, so a search for it must still be literal.
     * Without the self-escaping in escapeLikePattern() the pattern would go
     * malformed and either match nothing or match the wrong rows.
     */
    public function testBangInTermMatchesOnlyLiteralBang(): void
    {
        $this->insertPost(1, 'foo!bar');
        $this->insertPost(2, 'fooxbar');
        $this->insertPost(3, 'foo_bar');

        $titles = $this->titles($this->service->searchProvider('foo!', ''));

        self::assertSame(['foo!bar'], $titles);
    }

    public function testPlainTermStillFindsAllVariants(): void
    {
        $this->insertPost(1, 'foo%bar');
        $this->insertPost(2, 'fooxbar');
        $this->insertPost(3, 'foo_bar');

        $titles = $this->titles($this->service->searchProvider('foo', ''));

        self::assertSame(['foo%bar', 'fooxbar', 'foo_bar'], $titles);
    }

    public function testSearchExcludesDraftsAndTombstones(): void
    {
        $this->insertPost(1, 'Live post');
        $this->insertPost(2, 'Draft post', 'draft');
        $this->insertPost(3, 'Deleted post');
        $this->softDelete(3);

        $titles = $this->titles($this->service->searchProvider('post', ''));

        self::assertSame(['Live post'], $titles);
    }

    public function testResultsKeepSearchPayloadShape(): void
    {
        $this->insertPost(1, 'Target title');

        $results = $this->service->searchProvider('target', '');

        self::assertCount(1, $results);
        $item = $results[0];
        self::assertSame('Target title', $item['title']);
        self::assertSame('/post-1', $item['url']);
        self::assertSame('Post', $item['content_type']);
        self::assertNotEmpty($item['published_at']);
        self::assertSame('Body of Target title', $item['content']);
        self::assertStringContainsString('target', strtolower((string) $item['excerpt']));
        // Ranking is SearchService's job; the provider returns content only.
        self::assertArrayNotHasKey('relevance', $item);
    }

    public function testNonAsciiTermIsEscapedSinceExactMatch(): void
    {
        $this->insertPost(1, 'café au lait');
        $this->insertPost(2, 'cafe au lait');

        $titles = $this->titles($this->service->searchProvider('café', ''));

        self::assertSame(['café au lait'], $titles);
    }

    public function testSearchByPatternFiltersPublishedAndPublishedOnly(): void
    {
        $this->insertPost(1, 'Alpha');
        $this->insertPost(2, 'Alpha', 'draft');

        $posts = (new Post($this->pdo))->searchByPattern('%Alpha%');

        self::assertCount(1, $posts);
        self::assertSame('Alpha', $posts[0]->title);
    }
}