<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Models\NavigationItem;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\NavigationService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * NavigationService over the in-memory navigation table.
 *
 * Covers the nested tree build, flat/group reads, item creation
 * (defaults, sort order, parent casting), delete with child
 * re-parenting, reorder, and the adext nav.linkable collection for
 * the Quick Add dropdown.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(NavigationService::class)]
final class NavigationServiceTest extends TestCase
{
    private PDO $pdo;

    private Engine $app;

    private NavigationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            'adext' => function (): ExtensionRegistry {
                static $registry = null;
                if ($registry === null) {
                    $registry = new ExtensionRegistry();
                }
                return $registry;
            },
        ]);
        $this->service = new NavigationService($this->app);
    }

    // -----------------------------------------------------------------
    // Tree building
    // -----------------------------------------------------------------

    public function testGetTreeNestsChildrenUnderParents(): void
    {
        $home = $this->insertItem('Home', '/', 'primary', null, 0);
        $docs = $this->insertItem('Docs', '/docs', 'primary', null, 1);
        $guide = $this->insertItem('Guide', '/docs/guide', 'primary', (int) $docs->id, 0);
        $deep = $this->insertItem('Deep', '/docs/guide/deep', 'primary', (int) $guide->id, 0);

        $tree = $this->service->getTree('primary');

        self::assertCount(2, $tree);
        self::assertSame((int) $home->id, (int) $tree[0]->id);
        self::assertSame((int) $docs->id, (int) $tree[1]->id);

        $children = $tree[1]->children;
        self::assertCount(1, $children);
        self::assertSame((int) $guide->id, (int) $children[0]->id);
        self::assertSame((int) $deep->id, (int) $children[0]->children[0]->id);
        self::assertSame([], $children[0]->children[0]->children);
    }

    public function testGetTreeTreatsZeroParentIdAsTopLevel(): void
    {
        $this->insertItem('Zero', '/', 'primary', 0, 0);

        $tree = $this->service->getTree('primary');

        self::assertCount(1, $tree);
        self::assertSame([], $tree[0]->children);
    }

    public function testGetTreeClonesDoNotMutateTheSourceModels(): void
    {
        $parent = $this->insertItem('Parent', '/', 'primary', null, 0);
        $this->insertItem('Child', '/c', 'primary', (int) $parent->id, 0);

        $flat = $this->service->getByGroup('primary');
        $tree = $this->service->getTree('primary');

        self::assertNotSame($flat[0], $tree[0], 'buildTree clones, the flat read is untouched');
        self::assertCount(1, $tree[0]->children);
    }

    public function testGetTreeFiltersByGroup(): void
    {
        $this->insertItem('Primary item', '/', 'primary', null, 0);
        $this->insertItem('Footer item', '/f', 'footer', null, 0);

        self::assertCount(1, $this->service->getTree('footer'));
    }

    public function testGetByGroupReturnsFlatOrderedList(): void
    {
        $second = $this->insertItem('Second', '/2', 'primary', null, 2);
        $first = $this->insertItem('First', '/1', 'primary', null, 1);

        $items = $this->service->getByGroup('primary');

        self::assertCount(2, $items);
        self::assertSame((int) $first->id, (int) $items[0]->id);
        self::assertSame((int) $second->id, (int) $items[1]->id);
    }

    public function testGetGroupsListsOnlyUsedGroupsInOrder(): void
    {
        $this->insertItem('Footer item', '/f', 'footer', null, 0);
        $this->insertItem('Primary item', '/', 'primary', null, 0);
        $this->insertItem('Another primary', '/p2', 'primary', null, 1);

        self::assertSame(['footer', 'primary'], $this->service->getGroups());
    }

    // -----------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------

    public function testCreateAppliesDefaultsAndAutoSortOrder(): void
    {
        $first = $this->service->create(['label' => 'One']);
        $second = $this->service->create(['label' => 'Two']);

        self::assertSame('One', $first->label);
        self::assertSame('/', $first->url, 'default url');
        self::assertSame('_self', $first->target, 'default target');
        self::assertSame('primary', $first->nav_group, 'default group');
        self::assertNull($first->parent_id);
        self::assertSame(0, (int) $first->sort_order, 'first item sorts at 0');
        self::assertSame(1, (int) $second->sort_order, 'next item sorts after');

        self::assertNotEmpty($first->created_at);
        self::assertNotEmpty($first->updated_at);
    }

    public function testCreateCastsParentIdToNullWhenEmpty(): void
    {
        $item = $this->service->create(['label' => 'L', 'parent_id' => '']);

        self::assertNull($item->parent_id);
    }

    public function testCreateStoresProvidedValues(): void
    {
        $parent = $this->service->create(['label' => 'Parent']);
        $item = $this->service->create([
            'label'      => 'Child',
            'url'        => '/child',
            'nav_group'  => 'footer',
            'parent_id'  => (string) $parent->id,
            'target'     => '_blank',
            'sort_order' => '7',
        ]);

        self::assertSame('/child', $item->url);
        self::assertSame('footer', $item->nav_group);
        self::assertSame((int) $parent->id, (int) $item->parent_id);
        self::assertSame('_blank', $item->target);
        self::assertSame(7, (int) $item->sort_order);
    }

    // -----------------------------------------------------------------
    // Delete
    // -----------------------------------------------------------------

    public function testDeleteReParentsChildrenToTopLevel(): void
    {
        $parent = $this->service->create(['label' => 'Parent']);
        $child = $this->service->create(['label' => 'Child', 'parent_id' => $parent->id]);

        self::assertTrue($this->service->delete((int) $parent->id));

        $promoted = (new NavigationItem($this->pdo))->findById((int) $child->id);
        self::assertNotNull($promoted);
        self::assertNull($promoted->parent_id, 'child survives, promoted to top level');
        self::assertNull((new NavigationItem($this->pdo))->findById((int) $parent->id));
    }

    public function testDeleteOnlyReParentsChildrenInSameGroup(): void
    {
        $parent = $this->service->create(['label' => 'Parent', 'nav_group' => 'primary']);
        $child = $this->service->create(['label' => 'Child', 'parent_id' => $parent->id]);
        // A child in another group that shares the parent id must not be touched.
        $otherGroup = $this->insertItem('Other group child', '/og', 'footer', (int) $parent->id, 0);

        $this->service->delete((int) $parent->id);

        self::assertNull((new NavigationItem($this->pdo))->findById((int) $child->id)->parent_id);
        self::assertSame(
            (int) $parent->id,
            (int) $this->fetchParentId((int) $otherGroup->id),
            'children in other groups are not re-parented'
        );
    }

    public function testDeleteMissingItemReturnsFalse(): void
    {
        self::assertFalse($this->service->delete(9999));
    }

    // -----------------------------------------------------------------
    // Reorder
    // -----------------------------------------------------------------

    public function testReorderAppliesArrayPositionsAsSortOrder(): void
    {
        $a = $this->service->create(['label' => 'A']);
        $b = $this->service->create(['label' => 'B']);
        $c = $this->service->create(['label' => 'C']);

        $this->service->reorder([(int) $c->id, (int) $b->id, (int) $a->id]);

        $model = new NavigationItem($this->pdo);
        self::assertSame(0, (int) $model->findById((int) $c->id)->sort_order);
        self::assertSame(1, (int) $model->findById((int) $b->id)->sort_order);
        self::assertSame(2, (int) $model->findById((int) $a->id)->sort_order);
    }

    public function testReorderSkipsUnknownIds(): void
    {
        $this->service->create(['label' => 'A']);
        $this->service->reorder([9999, 10000]);

        self::assertCount(1, $this->service->getByGroup('primary'));
    }

    // -----------------------------------------------------------------
    // Linkable items (Quick Add)
    // -----------------------------------------------------------------

    public function testLinkableItemsAlwaysIncludeTheCoreGroup(): void
    {
        $items = $this->service->getLinkableItems();

        self::assertSame(
            [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Blog', 'url' => '/blog'],
            ],
            $items['Core']
        );
    }

    public function testLinkableItemsCollectPluginCallableContributions(): void
    {
        $registry = $this->app->adext();
        $registry->register('nav.linkable', 'default', 'pubvana.blog', [
            'label'    => 'Blog',
            'callable' => fn(): array => [
                ['label' => 'Posts', 'url' => '/blog/posts'],
            ],
        ]);
        $registry->register('nav.linkable', 'default', 'pubvana.pages', [
            'label'    => 'Pages',
            'callable' => fn(): array => [
                ['label' => 'Page list', 'url' => '/page-1'],
            ],
        ]);

        $items = $this->service->getLinkableItems();

        self::assertSame([['label' => 'Posts', 'url' => '/blog/posts']], $items['Blog']);
        self::assertSame([['label' => 'Page list', 'url' => '/page-1']], $items['Pages']);
        // The contribution key is not used as the group label when a
        // label is declared.
        self::assertArrayNotHasKey('pubvana.blog', $items);
    }

    public function testLinkableItemsSkipsEmptyAndNonCallableContributions(): void
    {
        $registry = $this->app->adext();
        $registry->register('nav.linkable', 'default', 'pubvana.empty', [
            'label'    => 'Empty',
            'callable' => fn(): array => [],
        ]);
        $registry->register('nav.linkable', 'default', 'pubvana.noCallable', [
            'label' => 'No callable',
        ]);

        $items = $this->service->getLinkableItems();

        self::assertArrayNotHasKey('Empty', $items);
        self::assertArrayNotHasKey('No callable', $items);
        self::assertArrayNotHasKey('pubvana.noCallable', $items);
        self::assertArrayHasKey('Core', $items);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Insert a row directly so read-side tests control exact data.
     */
    private function insertItem(
        string $label,
        string $url,
        string $group,
        ?int $parentId,
        int $sortOrder
    ): NavigationItem {
        $item = new NavigationItem($this->pdo);
        $item->label = $label;
        $item->url = $url;
        $item->nav_group = $group;
        $item->parent_id = $parentId;
        $item->sort_order = $sortOrder;
        $item->target = '_self';
        $item->insert();

        return $item;
    }

    /**
     * Read parent_id straight from the table (guards against model
     * property caching on the pre-delete instance).
     */
    private function fetchParentId(int $id): int|string|null
    {
        $statement = $this->pdo->prepare('SELECT parent_id FROM navigation WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->fetch(PDO::FETCH_ASSOC)['parent_id'] ?? null;
    }
}
