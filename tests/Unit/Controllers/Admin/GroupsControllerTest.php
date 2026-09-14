<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\GroupsController;
use Pubvana\Tests\Support\TestCase;

/**
 * GroupsController coverage.
 */
#[CoversClass(GroupsController::class)]
final class GroupsControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $renders = [];
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->renders = [];
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
    }

    public function testIndexRendersGroupsWithCounts(): void
    {
        $groups = [(object) ['alias' => 'admin', 'title' => 'Admin']];
        $app = $this->engine(groups: $groups, counts: ['admin' => 3]);
        (new GroupsController($app))->index();

        self::assertCount(1, $this->fetches);
        self::assertSame('admin/groups/index', $this->fetches[0]['view']);
        self::assertSame('admin', $this->fetches[0]['data']['groups'][0]->alias);
        self::assertSame(['admin' => 3], $this->fetches[0]['data']['userCounts']);
    }

    public function testCreateRendersForm(): void
    {
        $app = $this->engine();
        (new GroupsController($app))->create();

        self::assertSame('admin/groups/create', $this->fetches[0]['view']);
        self::assertSame('New Group', $this->fetches[0]['data']['pageTitle']);
    }

    public function testStoreRejectsMissingFields(): void
    {
        $app = $this->engine(data: ['alias' => '', 'title' => '']);
        (new GroupsController($app))->store();

        self::assertSame(['/admin/groups/create'], $this->redirects);
        self::assertSame('Alias and title are required.', $this->flashes['error'][0]);
    }

    public function testStoreRejectsDuplicateAlias(): void
    {
        $app = $this->engine(data: ['alias' => 'admin', 'title' => 'Admin']);
        (new GroupsController($app))->store();

        self::assertSame(['/admin/groups/create'], $this->redirects);
        self::assertSame('A group with that alias already exists.', $this->flashes['error'][0]);
    }

    public function testStoreCreatesGroup(): void
    {
        $store = new GroupStore([['alias' => 'admin', 'title' => 'Admin']]);
        $app = $this->engine(data: ['alias' => 'editors', 'title' => 'Editors', 'description' => 'd'], store: $store);
        (new GroupsController($app))->store();

        self::assertSame(['/admin/groups'], $this->redirects);
        self::assertSame('Group created.', $this->flashes['success'][0]);
        self::assertTrue($store->has('editors'));
    }

    public function testEditRedirectsOnMissing(): void
    {
        $app = $this->engine();
        (new GroupsController($app))->edit('99');

        self::assertSame(['/admin/groups'], $this->redirects);
        self::assertCount(0, $this->fetches);
    }

    public function testEditRendersGroupWithPermissions(): void
    {
        $app = $this->engine();
        (new GroupsController($app))->edit('1');

        self::assertSame('admin/groups/edit', $this->fetches[0]['view']);
        self::assertSame('admin', $this->fetches[0]['data']['group']->alias);
        self::assertNotEmpty($this->fetches[0]['data']['allPermissions']);
    }

    public function testUpdateRedirectsOnMissing(): void
    {
        $app = $this->engine(data: ['title' => 'X']);
        (new GroupsController($app))->update('99');

        self::assertSame(['/admin/groups'], $this->redirects);
    }

    public function testUpdateSavesFieldsAndSyncsPermissions(): void
    {
        $store = new GroupStore([['alias' => 'admin', 'title' => 'Admin']]);
        $app = $this->engine(data: ['title' => 'Admins', 'description' => 'all', 'permissions' => ['a', 'b']], store: $store);
        (new GroupsController($app))->update('1');

        self::assertSame(['/admin/groups/1/edit'], $this->redirects);
        self::assertSame('Group updated.', $this->flashes['success'][0]);
        self::assertSame('Admins', $store->savedTitle);
        self::assertSame(['a', 'b'], $store->synced);
    }

    public function testUpdateHandlesNonArrayPermissions(): void
    {
        $store = new GroupStore([['alias' => 'admin', 'title' => 'Admin']]);
        $app = $this->engine(data: ['title' => 'T'], store: $store);
        (new GroupsController($app))->update('1');

        self::assertSame([], $store->synced);
    }

    public function testDeleteRemovesGroup(): void
    {
        $store = new GroupStore([['alias' => 'admin', 'title' => 'Admin']]);
        $app = $this->engine(store: $store);
        (new GroupsController($app))->delete('1');

        self::assertFalse($store->has('admin'));
        self::assertSame(['/admin/groups'], $this->redirects);
        self::assertSame('Group deleted.', $this->flashes['success'][0]);
    }

    public function testDeleteMissingStillRedirects(): void
    {
        $app = $this->engine();
        (new GroupsController($app))->delete('99');

        self::assertSame(['/admin/groups'], $this->redirects);
    }

    private function engine(array $data = [], ?GroupStore $store = null, array $groups = [], array $counts = []): Engine
    {
        $test = $this;
        $store ??= new GroupStore([['alias' => 'admin', 'title' => 'Admin']]);
        if ($groups !== []) {
            $store = new GroupStore(array_map(static fn($g): array => (array) $g, $groups));
        }
        $app = $this->app([
            'request' => static fn(): object => new class($data) {
                public Collection $data;
                public Collection $query;
                public function __construct(array $data)
                {
                    $this->data = new Collection($data);
                    $this->query = new Collection([]);
                }
            },
            'auth' => static fn(): object => new class($store, $counts) {
                public function __construct(private GroupStore $store, private array $counts)
                {
                }
                public function groups(): GroupStore
                {
                    return $this->store;
                }
                public function permissions(): object
                {
                    return new class {
                        /** @return list<object> */
                        public function all(): array
                        {
                            return [(object) ['alias' => 'a']];
                        }
                    };
                }
                public function stats(): object
                {
                    return new class($this->counts) {
                        public function __construct(private array $counts)
                        {
                        }
                        /** @return array<string, int> */
                        public function usersByGroup(): array
                        {
                            return $this->counts;
                        }
                    };
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private GroupsControllerTest $test)
                {
                }
                public function flash(string $key, mixed $value): void
                {
                    $this->test->flashes[$key][] = $value;
                }
            },
            'adext' => static fn(): object => new class {
                /** @return array<string, mixed> */
                public function get(string $t, string $s, array $c = []): array
                {
                    return [];
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private GroupsControllerTest $test)
                    {
                    }
                    /** @param array<string, mixed>|null $d */
                    public function fetch(string $v, ?array $d = null): string
                    {
                        $this->test->fetches[] = ['view' => $v, 'data' => $d ?? []];

                        return 'C:' . $v;
                    }
                };
            },
        ]);
        $app->set('admin.topNav', []);
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->renders[] = ['template' => $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * In-memory groups service double.
 */
final class GroupStore
{
    /** @var array<int, object> */
    private array $rows = [];
    public string $savedTitle = '';
    /** @var list<string> */
    public array $synced = [];

    /** @param list<array<string, mixed>> $seed */
    public function __construct(array $seed = [])
    {
        $id = 1;
        foreach ($seed as $row) {
            $o = (object) $row;
            $o->id = $id++;
            $this->rows[] = $o;
        }
    }

    /** @return list<object> */
    public function all(): array
    {
        return $this->rows;
    }

    public function info(string $alias): ?object
    {
        foreach ($this->rows as $r) {
            if ($r->alias === $alias) {
                return $r;
            }
        }

        return null;
    }

    public function findById(int $id): ?object
    {
        foreach ($this->rows as $r) {
            if ((int) $r->id === $id) {
                return $r;
            }
        }

        return null;
    }

    public function create(string $alias, string $title, string $desc): object
    {
        $o = (object) ['id' => count($this->rows) + 1, 'alias' => $alias, 'title' => $title, 'description' => $desc];
        $this->rows[] = $o;

        return $o;
    }

    public function save(object $group): void
    {
        $this->savedTitle = (string) ($group->title ?? '');
    }

    /** @param list<string> $perms */
    public function syncPermissions(string $alias, array $perms): void
    {
        $this->synced = $perms;
    }

    /** @return list<string> */
    public function permissions(string $alias): array
    {
        return ['a'];
    }

    public function delete(string $alias): void
    {
        $this->rows = array_values(array_filter($this->rows, static fn($r): bool => $r->alias !== $alias));
    }

    public function has(string $alias): bool
    {
        return $this->info($alias) !== null;
    }
}
