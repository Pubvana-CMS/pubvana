<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use flight\database\DatabaseInterface;
use flight\database\DatabaseStatementInterface;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\TrustCache;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * TrustCache model over the trust_cache table.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(TrustCache::class)]
final class TrustCacheTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testCompositeKeyJoinsIdentityWithPipes(): void
    {
        self::assertSame(
            'plugin|blog|1.0.0|Pubvana Team',
            TrustCache::compositeKey('plugin', 'blog', '1.0.0', 'Pubvana Team')
        );
    }

    public function testFindByAddonHitsAndMisses(): void
    {
        $this->createCacheRow('plugin', 'blog', '1.0.0', 'author', 'known', null);

        $hit = (new TrustCache($this->pdo))->findByAddon('plugin', 'blog', '1.0.0', 'author');
        self::assertInstanceOf(TrustCache::class, $hit);
        self::assertSame('known', $hit->status);

        self::assertNull((new TrustCache($this->pdo))->findByAddon('plugin', 'blog', '2.0.0', 'author'));
        self::assertNull((new TrustCache($this->pdo))->findByAddon('theme', 'blog', '1.0.0', 'author'));
    }

    public function testUpsertInsertsNewIdentity(): void
    {
        $row = (new TrustCache($this->pdo))->upsert('plugin', 'blog', '1.0.0', 'author', 'known', null);

        self::assertNotNull($row->id);
        self::assertSame('plugin', $row->type);
        self::assertSame('blog', $row->slug);
        self::assertSame('1.0.0', $row->version);
        self::assertSame('author', $row->author);
        self::assertSame('known', $row->status);
        self::assertNull($row->warning);
        self::assertNotNull($row->checked_at);

        self::assertTrue($this->cacheRowExists('plugin|blog|1.0.0|author'));
    }

    public function testUpsertUpdatesExistingRowForSameIdentity(): void
    {
        $first = (new TrustCache($this->pdo))->upsert('plugin', 'blog', '1.0.0', 'author', 'known', null);

        $second = (new TrustCache($this->pdo))->upsert('plugin', 'blog', '1.0.0', 'author', 'trusted', 'exists');

        self::assertSame($first->id, $second->id);
        self::assertSame('trusted', $second->status);
        self::assertSame('exists', $second->warning);

        self::assertCount(1, (new TrustCache($this->pdo))->findAll());
    }

    public function testStatusesForAllKeysByCompositeIdentity(): void
    {
        (new TrustCache($this->pdo))->upsert('plugin', 'blog', '1.0.0', 'author', 'known', null);
        (new TrustCache($this->pdo))->upsert('theme', 'dark', '2.0.0', 'other', 'trusted', null);

        $map = (new TrustCache($this->pdo))->statusesForAll();

        self::assertCount(2, $map);
        self::assertArrayHasKey('plugin|blog|1.0.0|author', $map);
        self::assertSame('known', $map['plugin|blog|1.0.0|author']['status']);
        self::assertNull($map['plugin|blog|1.0.0|author']['warning']);
        self::assertArrayHasKey('theme|dark|2.0.0|other', $map);
        self::assertSame('trusted', $map['theme|dark|2.0.0|other']['status']);
        self::assertNotEmpty($map['theme|dark|2.0.0|other']['checked_at']);
    }

    public function testPurgeStaleDeletesOnlyOldNonTrustedRows(): void
    {
        $this->createCacheRow('plugin', 'blog', '1.0.0', 'author', 'unknown', null);
        $this->createCacheRow('plugin', 'media', '1.0.0', 'author', 'malicious', null);
        $this->createCacheRow('plugin', 'seo', '1.0.0', 'author', 'trusted', null);

        $old = date('Y-m-d H:i:s', time() - 2 * 86400);
        $new = date('Y-m-d H:i:s', time());
        $this->ageRow('plugin|blog|1.0.0|author', $old);
        $this->ageRow('plugin|media|1.0.0|author', $old);
        $this->ageRow('plugin|seo|1.0.0|author', $new);

        $deleted = (new TrustCache($this->pdo))->purgeStale(24);

        self::assertSame(2, $deleted);
        self::assertFalse($this->cacheRowExists('plugin|blog|1.0.0|author'));
        self::assertFalse($this->cacheRowExists('plugin|media|1.0.0|author'));
        self::assertTrue($this->cacheRowExists('plugin|seo|1.0.0|author'));
    }

    public function testPurgeStaleReturnsZeroWhenNothingIsStale(): void
    {
        $this->createCacheRow('plugin', 'blog', '1.0.0', 'author', 'unknown', null);
        $this->ageRow('plugin|blog|1.0.0|author', date('Y-m-d H:i:s', time()));

        self::assertSame(0, (new TrustCache($this->pdo))->purgeStale(24));
    }

    public function testPurgeStaleReturnsZeroOnThrowingConnection(): void
    {
        $throwing = new class implements DatabaseInterface {
            public function prepare(string $sql): DatabaseStatementInterface
            {
                throw new \PDOException('connection gone');
            }

            public function lastInsertId()
            {
                return 0;
            }

            public function beginTransaction(): bool
            {
                return true;
            }

            public function commit(): bool
            {
                return true;
            }

            public function rollback(): bool
            {
                return true;
            }
        };

        self::assertSame(0, (new TrustCache($throwing))->purgeStale(24));
    }

    private function createCacheRow(
        string $type,
        string $slug,
        string $version,
        string $author,
        string $status,
        ?string $warning
    ): void {
        (new TrustCache($this->pdo))->upsert($type, $slug, $version, $author, $status, $warning);
    }

    private function ageRow(string $compositeKey, string $checkedAt): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE trust_cache SET checked_at = :at '
            . 'WHERE (type || \'|\' || slug || \'|\' || version || \'|\' || author) = :key'
        );
        $stmt->execute([':at' => $checkedAt, ':key' => $compositeKey]);
    }

    private function cacheRowExists(string $compositeKey): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM trust_cache '
            . 'WHERE (type || \'|\' || slug || \'|\' || version || \'|\' || author) = :key'
        );
        $stmt->execute([':key' => $compositeKey]);

        return (int) $stmt->fetchColumn() > 0;
    }
}