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
        $first = $this->created(['label' => 'One', 'url' => '/one']);
        $second = $this->created(['label' => 'Two', 'url' => '/two']);

        self::assertSame('One', $first->label);
        self::assertSame('/one', $first->url);
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
        $item = $this->created(['label' => 'L', 'url' => '/l', 'parent_id' => '']);

        self::assertNull($item->parent_id);
    }

    public function testCreateStoresProvidedValues(): void
    {
        $parent = $this->created(['label' => 'Parent', 'url' => '/parent']);
        $item = $this->created([
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

    public function testCreateTrimsLabel(): void
    {
        $item = $this->created(['label' => '  Spaced  ', 'url' => '/spaced']);

        self::assertSame('Spaced', $item->label);
    }

    public function testCreateRejectsBlankLabel(): void
    {
        $result = $this->service->create(['label' => '   ', 'url' => '/x']);

        self::assertFalse($result->isOK());
        self::assertSame('A label is required.', $result->reason());
    }

    public function testCreateRejectsOverlongLabel(): void
    {
        $result = $this->service->create(['label' => str_repeat('a', 101), 'url' => '/x']);

        self::assertFalse($result->isOK());
        self::assertSame('The label is limited to 100 characters.', $result->reason());
    }

    public function testCreateRejectsBlankUrl(): void
    {
        $result = $this->service->create(['label' => 'L', 'url' => '   ']);

        self::assertFalse($result->isOK());
        self::assertSame('A URL is required.', $result->reason());
    }

    public function testCreateRejectsOverlongUrl(): void
    {
        $result = $this->service->create(['label' => 'L', 'url' => '/' . str_repeat('a', 500)]);

        self::assertFalse($result->isOK());
        self::assertSame('The URL is limited to 500 characters.', $result->reason());
    }

    public function testCreateRejectsUnknownTarget(): void
    {
        $result = $this->service->create(['label' => 'L', 'url' => '/x', 'target' => '_top']);

        self::assertFalse($result->isOK());
        self::assertSame('The link target must open in the same window or a new one.', $result->reason());
    }

    public function testCreateWritesNothingWhenRejected(): void
    {
        $this->service->create(['label' => '', 'url' => '/x']);

        self::assertSame([], $this->service->getByGroup('primary'));
    }

    // -----------------------------------------------------------------
    // Find
    // -----------------------------------------------------------------

    public function testFindReturnsItemOrNull(): void
    {
        $item = $this->created(['label' => 'L', 'url' => '/l']);

        $found = $this->service->find((int) $item->id);
        self::assertNotNull($found);
        self::assertSame((int) $item->id, (int) $found->id);
        self::assertNull($this->service->find(4242));
    }

    // -----------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------

    public function testUpdateChangesLabelUrlAndTarget(): void
    {
        $item = $this->created(['label' => 'Old', 'url' => '/old', 'target' => '_self']);

        $result = $this->service->update((int) $item->id, [
            'label'  => 'New',
            'url'    => '/new',
            'target' => '_blank',
        ]);

        self::assertTrue($result->isOK(), (string) $result->reason());

        $reloaded = (new NavigationItem($this->pdo))->findById((int) $item->id);
        self::assertNotNull($reloaded);
        self::assertSame('New', $reloaded->label);
        self::assertSame('/new', $reloaded->url);
        self::assertSame('_blank', $reloaded->target);
    }

    public function testUpdateLeavesParentGroupAndSortOrderAlone(): void
    {
        $parent = $this->created(['label' => 'Parent', 'url' => '/p']);
        $child = $this->created([
            'label'     => 'Child',
            'url'       => '/c',
            'nav_group' => 'footer',
            'parent_id' => (string) $parent->id,
            'sort_order' => '3',
        ]);

        $this->service->update((int) $child->id, ['label' => 'Child 2', 'url' => '/c2', 'target' => '_self']);

        $reloaded = (new NavigationItem($this->pdo))->findById((int) $child->id);
        self::assertNotNull($reloaded);
        self::assertSame((int) $parent->id, (int) $reloaded->parent_id);
        self::assertSame('footer', $reloaded->nav_group);
        self::assertSame(3, (int) $reloaded->sort_order);
    }

    public function testUpdateStampsUpdatedAt(): void
    {
        $item = $this->insertItem('L', '/x', 'primary', null, 0);
        self::assertNull($item->updated_at);

        $this->service->update((int) $item->id, ['label' => 'L2', 'url' => '/x2', 'target' => '_self']);

        $reloaded = (new NavigationItem($this->pdo))->findById((int) $item->id);
        self::assertNotNull($reloaded);
        self::assertNotNull($reloaded->updated_at);
    }

    public function testUpdateSanitizesDangerousUrl(): void
    {
        $item = $this->created(['label' => 'L', 'url' => '/ok']);

        $this->service->update((int) $item->id, ['label' => 'L', 'url' => 'javascript:alert(1)', 'target' => '_self']);

        $reloaded = (new NavigationItem($this->pdo))->findById((int) $item->id);
        self::assertNotNull($reloaded);
        self::assertSame('#', (string) $reloaded->url);
    }

    public function testUpdateRejectsBlankLabelAndKeepsStoredValue(): void
    {
        $item = $this->created(['label' => 'L', 'url' => '/x']);

        $result = $this->service->update((int) $item->id, ['label' => '', 'url' => '/x', 'target' => '_self']);

        self::assertFalse($result->isOK());
        self::assertSame('A label is required.', $result->reason());

        $reloaded = (new NavigationItem($this->pdo))->findById((int) $item->id);
        self::assertNotNull($reloaded);
        self::assertSame('L', $reloaded->label);
    }

    public function testUpdateMissingItemIsDenied(): void
    {
        $result = $this->service->update(4242, ['label' => 'L', 'url' => '/x', 'target' => '_self']);

        self::assertFalse($result->isOK());
        self::assertSame('That navigation item no longer exists.', $result->reason());
    }

    // -----------------------------------------------------------------
    // Delete
    // -----------------------------------------------------------------

    public function testDeleteReParentsChildrenToTopLevel(): void
    {
        $parent = $this->created(['label' => 'Parent', 'url' => '/parent']);
        $child = $this->created(['label' => 'Child', 'url' => '/child', 'parent_id' => $parent->id]);

        self::assertTrue($this->service->delete((int) $parent->id));

        $promoted = (new NavigationItem($this->pdo))->findById((int) $child->id);
        self::assertNotNull($promoted);
        self::assertNull($promoted->parent_id, 'child survives, promoted to top level');
        self::assertNull((new NavigationItem($this->pdo))->findById((int) $parent->id));
    }

    public function testDeleteOnlyReParentsChildrenInSameGroup(): void
    {
        $parent = $this->created(['label' => 'Parent', 'url' => '/parent', 'nav_group' => 'primary']);
        $child = $this->created(['label' => 'Child', 'url' => '/child', 'parent_id' => $parent->id]);
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
        $a = $this->created(['label' => 'A', 'url' => '/a']);
        $b = $this->created(['label' => 'B', 'url' => '/b']);
        $c = $this->created(['label' => 'C', 'url' => '/c']);

        $this->service->reorder([(int) $c->id, (int) $b->id, (int) $a->id]);

        $model = new NavigationItem($this->pdo);
        self::assertSame(0, (int) $model->findById((int) $c->id)->sort_order);
        self::assertSame(1, (int) $model->findById((int) $b->id)->sort_order);
        self::assertSame(2, (int) $model->findById((int) $a->id)->sort_order);
    }

    public function testReorderSkipsUnknownIds(): void
    {
        $this->created(['label' => 'A', 'url' => '/a']);
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
    // URL sanitizing
    // -----------------------------------------------------------------

    public function testCreateSanitizesDangerousUrlScheme(): void
    {
        $item = $this->created(['label' => 'Bad', 'url' => 'javascript:alert(1)']);

        self::assertSame('#', (string) $item->url);
    }

    public function testGetByGroupSanitizesALegacyDangerousUrl(): void
    {
        $this->insertItem('Legacy', 'javascript:alert(1)', 'primary', null, 0);

        $items = $this->service->getByGroup('primary');

        self::assertSame('#', (string) $items[0]->url);
    }

    public function testSafeUrlAllowsRelativeAndHttpAndRejectsTheRest(): void
    {
        self::assertSame('/blog', $this->service->safeUrl('/blog'));
        self::assertSame('#anchor', $this->service->safeUrl('#anchor'));
        self::assertSame('blog/post', $this->service->safeUrl('blog/post'));
        self::assertSame('https://example.com/x', $this->service->safeUrl('https://example.com/x'));
        self::assertSame('mailto:a@b.test', $this->service->safeUrl('mailto:a@b.test'));

        self::assertSame('#', $this->service->safeUrl('javascript:alert(1)'));
        self::assertSame('#', $this->service->safeUrl('data:text/html,<script>'));
        self::assertSame('#', $this->service->safeUrl(''));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Create through the service and hand back the saved item.
     *
     * @param array<string, mixed> $data Item data
     */
    private function created(array $data): NavigationItem
    {
        $result = $this->service->create($data);
        self::assertTrue($result->isOK(), (string) $result->reason());

        $item = $result->extraInfo();
        self::assertInstanceOf(NavigationItem::class, $item);

        return $item;
    }

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
