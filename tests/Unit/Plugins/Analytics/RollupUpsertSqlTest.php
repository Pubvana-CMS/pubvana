<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Analytics;

use flight\Engine;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Pubvana\Plugins\Analytics\Services\AnalyticsService;

/**
 * AUDIT M16: on MySQL 8.0.19+ the rollup upsert uses the row-alias form
 * instead of the deprecated VALUES() function. MariaDB and older MySQL
 * keep the VALUES() form (the row-alias syntax is not supported there).
 */
final class RollupUpsertSqlTest extends TestCase
{
    public function testModernMysqlUsesRowAliasForm(): void
    {
        $pdo = $this->recordingPdo('8.0.36');
        $service = new AnalyticsService($pdo, $this->engine());
        $service->rollup();

        self::assertCount(2, $pdo->upserts());
        foreach ($pdo->upserts() as $sql) {
            self::assertStringContainsString(' AS new_row', $sql);
            self::assertStringNotContainsString('VALUES(', $sql);
        }
    }

    public function testMariaDbKeepsValuesForm(): void
    {
        $pdo = $this->recordingPdo('10.6.14-MariaDB-1:10.6.14+maria~ubu2004');
        $service = new AnalyticsService($pdo, $this->engine());
        $service->rollup();

        foreach ($pdo->upserts() as $sql) {
            self::assertStringContainsString('VALUES(view_count)', $sql);
            self::assertStringNotContainsString(' AS new_row', $sql);
        }
    }

    public function testOldMysqlKeepsValuesForm(): void
    {
        $pdo = $this->recordingPdo('5.7.42');
        $service = new AnalyticsService($pdo, $this->engine());
        $service->rollup();

        foreach ($pdo->upserts() as $sql) {
            self::assertStringContainsString('VALUES(view_count)', $sql);
            self::assertStringNotContainsString(' AS new_row', $sql);
        }
    }

    public function testRowAliasSelectionIsCached(): void
    {
        $pdo = $this->recordingPdo('8.0.36');
        $service = new AnalyticsService($pdo, $this->engine());
        $service->rollup();
        $service->rollup();

        // One version probe per service lifetime, not per statement.
        self::assertSame(1, $pdo->versionProbes);
    }

    public function testUpsertsMergeCountsOnConflict(): void
    {
        $pdo = $this->recordingPdo('8.0.36');
        $service = new AnalyticsService($pdo, $this->engine());
        $service->rollup();

        foreach ($pdo->upserts() as $sql) {
            self::assertStringContainsString('ON DUPLICATE KEY UPDATE', $sql);
            self::assertStringContainsString('view_count +', $sql);
        }
    }

    /**
     * A PDO stand-in that records prepared SQL and reports a fixed server
     * version, so the version-aware upsert can be exercised without a
     * MySQL server.
     */
    private function recordingPdo(string $serverVersion): RecordingPdo
    {
        return new RecordingPdo($serverVersion);
    }

    private function engine(): Engine
    {
        return new Engine();
    }
}

final class RecordingPdo extends PDO
{
    public string $serverVersion;

    /** @var list<string> */
    public array $prepared = [];

    /** @return list<string> The two rollup INSERT statements */
    public function upserts(): array
    {
        return array_values(array_filter(
            $this->prepared,
            static fn (string $sql): bool => str_starts_with($sql, 'INSERT INTO')
        ));
    }

    public int $versionProbes = 0;

    public function __construct(string $serverVersion)
    {
        $this->serverVersion = $serverVersion;
    }

    public function getAttribute(int $attribute): mixed
    {
        $this->versionProbes++;
        return $this->serverVersion;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->prepared[] = $query;

        return new class() extends PDOStatement {
            public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR): bool
            {
                return true;
            }

            public function execute(?array $params = null): bool
            {
                return true;
            }

            public function rowCount(): int
            {
                return 0;
            }
        };
    }
}
