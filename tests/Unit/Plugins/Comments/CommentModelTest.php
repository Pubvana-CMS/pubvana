<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Comments;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Comments\Models\Comment;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Comment model CRUD and queries against in-memory SQLite.
 */
#[CoversClass(Comment::class)]
final class CommentModelTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function make(array $extra = []): Comment
    {
        $model = new Comment($this->pdo);

        return $model->createRecord(array_merge([
            'commentable_type' => 'blog',
            'commentable_id' => 1,
            'body' => 'Hello',
            'status' => 'pending',
        ], $extra));
    }

    private function setCreatedAt(int $id, string $when): void
    {
        $stmt = $this->pdo->prepare('UPDATE comments SET created_at = ? WHERE id = ?');
        self::assertNotFalse($stmt);
        $stmt->execute([$when, $id]);
    }

    public function testCreateRecordSetsTimestamps(): void
    {
        $comment = $this->make();

        self::assertGreaterThan(0, (int) $comment->id);
        self::assertNotEmpty($comment->created_at);
        self::assertNotEmpty($comment->updated_at);
    }

    public function testFindById(): void
    {
        self::assertNull((new Comment($this->pdo))->findById(99999));

        $comment = $this->make(['body' => 'find me']);

        $found = (new Comment($this->pdo))->findById((int) $comment->id);
        self::assertNotNull($found);
        self::assertSame('find me', (string) $found->body);
    }

    public function testFindByIdRunsOnFreshInstance(): void
    {
        $a = $this->make(['body' => 'a']);
        $b = $this->make(['body' => 'b']);

        $model = new Comment($this->pdo);
        $first = $model->findById((int) $a->id);
        $second = $model->findById((int) $b->id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame('a', (string) $first->body);
        self::assertNull($model->findById(99999));
    }

    public function testFindByContentApprovedOnlyAndOrdered(): void
    {
        $a = $this->make(['body' => 'a', 'status' => 'approved']);
        $b = $this->make(['body' => 'b', 'status' => 'approved']);
        $this->make(['body' => 'pending one', 'status' => 'pending']);
        $this->make(['commentable_type' => 'page', 'body' => 'other type', 'status' => 'approved']);

        $this->setCreatedAt((int) $a->id, '2026-01-01 00:00:00');
        $this->setCreatedAt((int) $b->id, '2026-01-02 00:00:00');

        $rows = (new Comment($this->pdo))->findByContent('blog', 1);
        self::assertCount(2, $rows);
        self::assertSame('a', (string) $rows[0]->body);
        self::assertSame('b', (string) $rows[1]->body);

        $all = (new Comment($this->pdo))->findByContent('blog', 1, null);
        self::assertCount(3, $all);
    }

    public function testPaginate(): void
    {
        $ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $c = $this->make(['body' => 'c' . $i, 'status' => $i === 3 ? 'approved' : 'pending']);
            $ids[] = (int) $c->id;
            $this->setCreatedAt((int) $c->id, '2026-01-0' . $i . ' 00:00:00');
        }

        $page1 = (new Comment($this->pdo))->paginate(1, 2);
        self::assertCount(2, $page1);
        self::assertSame('c3', (string) $page1[0]->body);

        $page2 = (new Comment($this->pdo))->paginate(2, 2);
        self::assertCount(1, $page2);

        $approved = (new Comment($this->pdo))->paginate(1, 10, 'approved');
        self::assertCount(1, $approved);
        self::assertSame('c3', (string) $approved[0]->body);
    }

    public function testCountByStatus(): void
    {
        $this->make(['status' => 'pending']);
        $this->make(['status' => 'approved']);
        $this->make(['status' => 'approved']);

        $model = new Comment($this->pdo);
        self::assertSame(3, $model->countByStatus());
        self::assertSame(1, $model->countByStatus('pending'));
        self::assertSame(2, $model->countByStatus('approved'));
        self::assertSame(0, $model->countByStatus('rejected'));
    }

    public function testCountByType(): void
    {
        $this->make(['commentable_type' => 'blog', 'status' => 'approved']);
        $this->make(['commentable_type' => 'blog', 'status' => 'pending']);
        $this->make(['commentable_type' => 'page', 'status' => 'approved']);

        $model = new Comment($this->pdo);
        self::assertSame(['blog' => 2, 'page' => 1], $model->countByType());
        self::assertSame(['blog' => 1, 'page' => 1], $model->countByType('approved'));
        self::assertSame(['blog' => 1], $model->countByType('pending'));
    }

    public function testUpdateStatus(): void
    {
        self::assertNull((new Comment($this->pdo))->updateStatus(99999, 'approved'));

        $comment = $this->make(['status' => 'pending']);
        $updated = (new Comment($this->pdo))->updateStatus((int) $comment->id, 'approved');

        self::assertNotNull($updated);
        self::assertSame('approved', (string) $updated->status);
    }

    public function testDeleteById(): void
    {
        self::assertFalse((new Comment($this->pdo))->deleteById(99999));

        $comment = $this->make();
        self::assertTrue((new Comment($this->pdo))->deleteById((int) $comment->id));
        self::assertNull((new Comment($this->pdo))->findById((int) $comment->id));
    }

    public function testGetChildren(): void
    {
        $parent = $this->make(['body' => 'parent']);
        $child = $this->make(['body' => 'child', 'parent_id' => (int) $parent->id]);
        $this->make(['body' => 'other']);

        $children = (new Comment($this->pdo))->getChildren((int) $parent->id);
        self::assertCount(1, $children);
        self::assertSame((int) $child->id, (int) $children[0]->id);
    }

    public function testGetDepth(): void
    {
        self::assertSame(0, (new Comment($this->pdo))->getDepth(99999));

        $root = $this->make(['body' => 'root']);
        self::assertSame(0, (new Comment($this->pdo))->getDepth((int) $root->id));

        $child = $this->make(['body' => 'child', 'parent_id' => (int) $root->id]);
        self::assertSame(1, (new Comment($this->pdo))->getDepth((int) $child->id));

        $grand = $this->make(['body' => 'grand', 'parent_id' => (int) $child->id]);
        self::assertSame(2, (new Comment($this->pdo))->getDepth((int) $grand->id));
    }
}
