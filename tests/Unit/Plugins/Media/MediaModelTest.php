<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Media;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Media\Models\Media;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Media model CRUD and queries against in-memory SQLite.
 */
#[CoversClass(Media::class)]
final class MediaModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->pdo->exec(
            'CREATE TABLE media (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                type           TEXT NOT NULL,
                filename       TEXT NOT NULL,
                path           TEXT,
                mime_type      TEXT,
                size           INTEGER,
                alt_text       TEXT,
                title          TEXT,
                embed_url      TEXT,
                embed_provider TEXT,
                poster_path    TEXT,
                uploaded_by    INTEGER,
                created_at     TEXT,
                updated_at     TEXT
            )'
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function make(array $extra = []): Media
    {
        return (new Media($this->pdo))->createRecord(array_merge([
            'type' => 'image',
            'filename' => 'photo.jpg',
            'path' => 'uploads/2026/01/abc.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1234,
            'uploaded_by' => 1,
        ], $extra));
    }

    public function testCreateRecordSetsTimestamps(): void
    {
        $media = $this->make();

        self::assertGreaterThan(0, (int) $media->id);
        self::assertNotEmpty($media->created_at);
        self::assertNotEmpty($media->updated_at);
    }

    public function testFindById(): void
    {
        self::assertNull((new Media($this->pdo))->findById(99999));

        $media = $this->make(['filename' => 'find-me.png']);
        $found = (new Media($this->pdo))->findById((int) $media->id);
        self::assertNotNull($found);
        self::assertSame('find-me.png', (string) $found->filename);
    }

    public function testFindByIdRunsOnFreshInstance(): void
    {
        $a = $this->make(['filename' => 'a.jpg']);
        $b = $this->make(['filename' => 'b.jpg']);

        $model = new Media($this->pdo);
        $first = $model->findById((int) $a->id);
        $second = $model->findById((int) $b->id);
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertNotSame($first, $second);
        self::assertSame('a.jpg', (string) $first->filename);
        self::assertNull($model->findById(99999));
    }

    public function testPaginateAndCountWithTypeFilter(): void
    {
        $this->make(['filename' => 'a.jpg', 'type' => 'image']);
        $this->make(['filename' => 'b.mp4', 'type' => 'video']);
        $this->make(['filename' => 'c.jpg', 'type' => 'image']);

        $model = new Media($this->pdo);
        self::assertSame(3, $model->countAll());
        self::assertSame(2, $model->countAll('image'));
        self::assertSame(1, $model->countAll('video'));
        self::assertSame(0, $model->countAll('embed'));

        $page = $model->paginate(1, 2);
        self::assertCount(2, $page);
        // Newest first.
        self::assertSame('c.jpg', (string) $page[0]->filename);

        $images = $model->paginate(1, 10, 'image');
        self::assertCount(2, $images);
    }

    public function testUpdateMetaWhitelistsAndTrims(): void
    {
        $media = $this->make(['alt_text' => 'old']);
        $media->updateMeta([
            'alt_text' => '  New alt  ',
            'title' => '  ',
            'poster_path' => '/p.jpg',
            'filename' => 'hacked.jpg',
            'type' => 'video',
        ]);

        $found = (new Media($this->pdo))->findById((int) $media->id);
        self::assertNotNull($found);
        self::assertSame('New alt', (string) $found->alt_text);
        self::assertNull($found->title);
        self::assertSame('/p.jpg', (string) $found->poster_path);
        self::assertSame('photo.jpg', (string) $found->filename);
        self::assertSame('image', (string) $found->type);
    }
}
