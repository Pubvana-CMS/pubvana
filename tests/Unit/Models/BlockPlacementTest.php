<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\BlockPlacement;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * BlockPlacement model: region queries, sort order, and options JSON.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(BlockPlacement::class)]
final class BlockPlacementTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testGetForRegionFiltersAndOrdersBySortOrder(): void
    {
        $this->createPlacement('sidebar', 'pubvana.widget-a', 2);
        $this->createPlacement('header', 'pubvana.logo', 0);
        $this->createPlacement('sidebar', 'pubvana.widget-b', 1);

        $rows = (new BlockPlacement($this->pdo))->getForRegion('sidebar');

        self::assertCount(2, $rows);
        self::assertSame('sidebar', $rows[0]->region_id);
        self::assertSame(
            ['pubvana.widget-b', 'pubvana.widget-a'],
            array_column($rows, 'block_key')
        );
    }

    public function testGetAllOrdersByRegionThenSortOrder(): void
    {
        $this->createPlacement('z-region', 'z-block', 0);
        $this->createPlacement('a-region', 'second', 1);
        $this->createPlacement('a-region', 'first', 0);

        $rows = (new BlockPlacement($this->pdo))->getAll();

        self::assertSame(['first', 'second', 'z-block'], array_column($rows, 'block_key'));
        self::assertSame(['a-region', 'a-region', 'z-region'], array_column($rows, 'region_id'));
    }

    public function testFindPlacementHitsOnlyExactMatch(): void
    {
        $this->createPlacement('sidebar', 'pubvana.widget-a', 0);

        $hit = (new BlockPlacement($this->pdo))->findPlacement('sidebar', 'pubvana.widget-a');
        self::assertInstanceOf(BlockPlacement::class, $hit);
        self::assertSame('pubvana.widget-a', $hit->block_key);

        self::assertNull((new BlockPlacement($this->pdo))->findPlacement('sidebar', 'pubvana.missing'));
        self::assertNull((new BlockPlacement($this->pdo))->findPlacement('header', 'pubvana.widget-a'));
    }

    public function testNextSortOrderIsZeroForEmptyRegion(): void
    {
        self::assertSame(0, (new BlockPlacement($this->pdo))->nextSortOrder('sidebar'));
    }

    public function testNextSortOrderIsOnePastHighest(): void
    {
        $this->createPlacement('sidebar', 'pubvana.widget-a', 0);
        $this->createPlacement('sidebar', 'pubvana.widget-b', 5);

        self::assertSame(6, (new BlockPlacement($this->pdo))->nextSortOrder('sidebar'));
    }

    public function testGetOptionsHandlesNullEmptyValidAndInvalidJson(): void
    {
        $model = new BlockPlacement($this->pdo);

        $model->options = null;
        self::assertSame([], $model->getOptions());

        $model->options = '';
        self::assertSame([], $model->getOptions());

        $model->options = '{"a":1,"b":true}';
        self::assertSame(['a' => 1, 'b' => true], $model->getOptions());

        $model->options = 'not-json';
        self::assertSame([], $model->getOptions());
    }

    public function testSetOptionsEncodesValidArray(): void
    {
        $model = new BlockPlacement($this->pdo);
        $model->setOptions(['a' => 1, 'b' => 'two']);

        self::assertSame('{"a":1,"b":"two"}', $model->options);
    }

    public function testSetOptionsWithInvalidUtf8StoresNull(): void
    {
        $model = new BlockPlacement($this->pdo);
        $model->setOptions(["\xB1\x31"]);

        self::assertNull($model->options);
    }

    private function createPlacement(string $regionId, string $blockKey, int $sortOrder): BlockPlacement
    {
        $placement = new BlockPlacement($this->pdo);
        $placement->region_id = $regionId;
        $placement->block_key = $blockKey;
        $placement->sort_order = $sortOrder;
        $placement->insert();

        return $placement;
    }
}