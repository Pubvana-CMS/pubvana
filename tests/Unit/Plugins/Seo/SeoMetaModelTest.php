<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Models\SeoMeta;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * SeoMeta model against in-memory SQLite.
 */
#[CoversClass(SeoMeta::class)]
final class SeoMetaModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->pdo->exec(
            'CREATE TABLE seo_meta (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                content_type     TEXT NOT NULL,
                content_id       INTEGER NOT NULL,
                meta_title       TEXT,
                meta_description TEXT,
                canonical_url    TEXT,
                robots_directive TEXT,
                focus_keywords   TEXT,
                og_title         TEXT,
                og_description   TEXT,
                og_image         TEXT,
                og_type          TEXT,
                twitter_card     TEXT,
                schema_type      TEXT,
                seo_score        INTEGER,
                hreflang         TEXT,
                created_at       TEXT,
                updated_at       TEXT
            )'
        );
    }

    /** @param array<string, mixed> $extra */
    private function insert(array $extra = []): SeoMeta
    {
        $meta = new SeoMeta($this->pdo);
        $meta->content_type = 'post';
        $meta->content_id = 1;
        foreach ($extra as $key => $value) {
            $meta->$key = $value;
        }
        $meta->insert();

        return $meta;
    }

    public function testFindByContent(): void
    {
        self::assertNull((new SeoMeta($this->pdo))->findByContent('post', 999));

        $this->insert(['meta_title' => 'Hello']);
        $found = (new SeoMeta($this->pdo))->findByContent('post', 1);
        self::assertNotNull($found);
        self::assertSame('Hello', (string) $found->meta_title);
        self::assertNull((new SeoMeta($this->pdo))->findByContent('page', 1));
    }

    public function testFindByContentType(): void
    {
        $this->insert(['content_type' => 'post', 'content_id' => 1]);
        $this->insert(['content_type' => 'post', 'content_id' => 2]);
        $this->insert(['content_type' => 'page', 'content_id' => 1]);

        self::assertCount(2, (new SeoMeta($this->pdo))->findByContentType('post'));
        self::assertCount(1, (new SeoMeta($this->pdo))->findByContentType('page'));
        self::assertCount(0, (new SeoMeta($this->pdo))->findByContentType('missing'));
    }

    public function testFocusKeywords(): void
    {
        $meta = new SeoMeta($this->pdo);
        self::assertSame([], $meta->getFocusKeywordsArray());

        $meta->setFocusKeywordsArray(['  seo ', '', 'tips', 'a', 'b', 'c', 'd']);
        self::assertSame(['seo', 'tips', 'a', 'b', 'c'], $meta->getFocusKeywordsArray());

        $meta->setFocusKeywordsArray([]);
        self::assertNull($meta->focus_keywords);
        self::assertSame([], $meta->getFocusKeywordsArray());

        $meta->focus_keywords = 'not-json';
        self::assertSame([], $meta->getFocusKeywordsArray());
    }

    public function testNoindexNofollow(): void
    {
        $meta = new SeoMeta($this->pdo);
        self::assertFalse($meta->isNoindex());
        self::assertFalse($meta->isNofollow());

        $meta->robots_directive = 'index, follow';
        self::assertFalse($meta->isNoindex());
        self::assertFalse($meta->isNofollow());

        $meta->robots_directive = 'noindex, nofollow';
        self::assertTrue($meta->isNoindex());
        self::assertTrue($meta->isNofollow());
    }

    public function testCountWithMetaTitle(): void
    {
        $this->insert(['content_id' => 1, 'meta_title' => 'A']);
        $this->insert(['content_id' => 2, 'meta_title' => '']);
        $this->insert(['content_id' => 3, 'meta_title' => null]);

        self::assertSame(1, (new SeoMeta($this->pdo))->countWithMetaTitle());
    }

    public function testAverageScore(): void
    {
        self::assertSame(0, (new SeoMeta($this->pdo))->averageScore());

        $this->insert(['content_id' => 1, 'seo_score' => 80]);
        $this->insert(['content_id' => 2, 'seo_score' => 60]);
        $this->insert(['content_id' => 3, 'seo_score' => null]);

        self::assertSame(70, (new SeoMeta($this->pdo))->averageScore());
    }
}
