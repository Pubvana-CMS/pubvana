<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\PermissionsController;
use Pubvana\Tests\Support\TestCase;

/**
 * PermissionsController coverage.
 */
#[CoversClass(PermissionsController::class)]
final class PermissionsControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
    }

    public function testIndexRendersPermissions(): void
    {
        $app = $this->engine();
        (new PermissionsController($app))->index();

        self::assertSame('admin/permissions/index', $this->fetches[0]['view']);
        self::assertSame('Permissions', $this->fetches[0]['data']['pageTitle']);
        self::assertCount(1, $this->fetches[0]['data']['permissions']);
    }

    public function testCreateRendersForm(): void
    {
        (new PermissionsController($this->engine()))->create();
        self::assertSame('admin/permissions/create', $this->fetches[0]['view']);
    }

    public function testStoreRejectsEmptyAlias(): void
    {
        (new PermissionsController($this->engine(data: ['alias' => ''])))->store();
        self::assertSame(['/admin/permissions/create'], $this->redirects);
        self::assertSame('Alias is required.', $this->flashes['error'][0]);
    }

    public function testStoreRejectsDuplicate(): void
    {
        (new PermissionsController($this->engine(data: ['alias' => 'admin.access'])))->store();
        self::assertSame('A permission with that alias already exists.', $this->flashes['error'][0]);
    }

    public function testStoreCreates(): void
    {
        $store = new PermStore(['admin.access']);
        (new PermissionsController($this->engine(data: ['alias' => 'blog.edit', 'description' => 'd'], store: $store)))->store();
        self::assertTrue($store->has('blog.edit'));
        self::assertSame(['/admin/permissions'], $this->redirects);
        self::assertSame('Permission created.', $this->flashes['success'][0]);
    }

    public function testDeleteRemoves(): void
    {
        $store = new PermStore(['a', 'b']);
        $app = $this->engine(store: $store);
        // seed ids: findById(1) -> 'a'
        (new PermissionsController($app))->delete('1');
        self::assertFalse($store->has('a'));
        self::assertSame(['/admin/permissions'], $this->redirects);
    }

    public function testDeleteMissingStillRedirects(): void
    {
        (new PermissionsController($this->engine()))->delete('99');
        self::assertSame(['/admin/permissions'], $this->redirects);
        self::assertSame('Permission deleted.', $this->flashes['success'][0]);
    }

    private function engine(array $data = [], ?PermStore $store = null): Engine
    {
        $test = $this;
        $store ??= new PermStore(['admin.access']);
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
            'auth' => static fn(): object => new class($store) {
                public function __construct(private PermStore $store)
                {
                }
                public function permissions(): PermStore
                {
                    return $this->store;
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private PermissionsControllerTest $test)
                {
                }
                public function flash(string $k, mixed $v): void
                {
                    $this->test->flashes[$k][] = $v;
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
                    public function __construct(private PermissionsControllerTest $test)
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
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * In-memory permissions double.
 */
final class PermStore
{
    /** @var array<int, object> */
    private array $rows = [];

    /** @param list<string> $seed */
    public function __construct(array $seed = [])
    {
        $id = 1;
        foreach ($seed as $alias) {
            $this->rows[] = (object) ['id' => $id++, 'alias' => $alias];
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

    public function create(string $alias, string $desc): object
    {
        $o = (object) ['id' => count($this->rows) + 1, 'alias' => $alias];
        $this->rows[] = $o;

        return $o;
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
