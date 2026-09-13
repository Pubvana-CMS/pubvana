<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Models;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\NavigationItem;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * NavigationItem model over the navigation table.
 *
 * @package Pubvana\Tests\Unit\Models
 */
#[CoversClass(NavigationItem::class)]
final class NavigationItemTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testGetByGroupFiltersAndOrdersBySortOrder(): void
    {
        $this->createItem('Home', '/', 'primary', 2);
        $this->createItem('Blog', '/blog', 'primary', 0);
        $this->createItem('Footer', '/about', 'footer', 0);

        $rows = (new NavigationItem($this->pdo))->getByGroup('primary');

        self::assertCount(2, $rows);
        self::assertSame(['Blog', 'Home'], array_column($rows, 'label'));
    }

    public function testFindByIdHitsAndMisses(): void
    {
        $created = $this->createItem('Home', '/', 'primary', 0);

        $hit = (new NavigationItem($this->pdo))->findById($created->id);
        self::assertInstanceOf(NavigationItem::class, $hit);
        self::assertSame('Home', $hit->label);

        self::assertNull((new NavigationItem($this->pdo))->findById(9999));
    }

    public function testNextSortOrderIsZeroForEmptyGroup(): void
    {
        self::assertSame(0, (new NavigationItem($this->pdo))->nextSortOrder('primary'));
    }

    public function testNextSortOrderIsOnePastHighest(): void
    {
        $this->createItem('Home', '/', 'primary', 3);
        $this->createItem('Footer', '/about', 'footer', 10);

        self::assertSame(4, (new NavigationItem($this->pdo))->nextSortOrder('primary'));
    }

    public function testGetChildrenReturnsDirectChildrenWithinGroup(): void
    {
        $parent = $this->createItem('Menu', '/menu', 'primary', 0);
        $otherParent = $this->createItem('Other', '/other', 'primary', 1);

        $childA = $this->createItem('Child A', '/menu/a', 'primary', 0);
        $childA->parent_id = $parent->id;
        $childA->save();
        $childB = $this->createItem('Child B', '/menu/b', 'primary', 2);
        $childB->parent_id = $parent->id;
        $childB->save();
        $footerChild = $this->createItem('Footer Child', '/menu/c', 'footer', 0);
        $footerChild->parent_id = $parent->id;
        $footerChild->save();

        $children = (new NavigationItem($this->pdo))->getChildren($parent->id, 'primary');

        self::assertSame(['Child A', 'Child B'], array_column($children, 'label'));
        self::assertSame([], (new NavigationItem($this->pdo))->getChildren($otherParent->id, 'primary'));
    }

    private function createItem(string $label, string $url, string $group, int $sortOrder): NavigationItem
    {
        $item = new NavigationItem($this->pdo);
        $item->label = $label;
        $item->url = $url;
        $item->parent_id = null;
        $item->sort_order = $sortOrder;
        $item->target = '_self';
        $item->nav_group = $group;
        $item->insert();

        return $item;
    }
}