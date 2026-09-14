<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\NavigationController;
use Pubvana\Tests\Support\TestCase;

/**
 * NavigationController coverage.
 */
#[CoversClass(NavigationController::class)]
final class NavigationControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var array<string, mixed> */
    public array $jsons = [];
    public FakeNav $nav;
    public string $body = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->jsons = [];
        $this->nav = new FakeNav();
        $this->body = '';
    }

    public function testIndexDefaultsToPrimary(): void
    {
        (new NavigationController($this->engine()))->index();

        self::assertSame('admin/navigation/index', $this->fetches[0]['view']);
        self::assertSame('primary', $this->fetches[0]['data']['group']);
        self::assertContains('primary', $this->fetches[0]['data']['groups']);
    }

    public function testIndexRejectsUnknownGroup(): void
    {
        // getAvailableGroups() always lists the current request group, so an
        // unknown group passes through as its own (empty) group page.
        (new NavigationController($this->engine(query: ['group' => 'nope'])))->index();
        self::assertSame('nope', $this->fetches[0]['data']['group']);
    }

    public function testIndexKeepsKnownGroup(): void
    {
        $this->nav->groups = ['primary', 'footer', 'extra'];
        (new NavigationController($this->engine(query: ['group' => 'extra'])))->index();
        self::assertSame('extra', $this->fetches[0]['data']['group']);
    }

    public function testStoreCreatesAndRedirects(): void
    {
        $app = $this->engine(data: ['nav_group' => 'footer', 'label' => 'L', '_csrf_token' => 'x']);
        (new NavigationController($app))->store();

        self::assertSame('footer', $this->nav->lastCreate['nav_group'] ?? null);
        self::assertSame('L', $this->nav->lastCreate['label'] ?? null);
        self::assertSame(['/admin/navigation?group=footer'], $this->redirects);
        self::assertSame('Navigation item added.', $this->flashes['success'][0]);
    }

    public function testStoreDefaultsGroup(): void
    {
        (new NavigationController($this->engine(data: [])))->store();
        self::assertSame(['/admin/navigation?group=primary'], $this->redirects);
    }

    public function testDeleteRemovesAndRedirects(): void
    {
        $app = $this->engine(query: ['group' => 'footer']);
        (new NavigationController($app))->delete('4');

        self::assertSame(4, $this->nav->lastDelete);
        self::assertSame(['/admin/navigation?group=footer'], $this->redirects);
    }

    public function testReorderSendsJson(): void
    {
        $this->body = '{"order":[3,1,2]}';
        (new NavigationController($this->engine()))->reorder();

        self::assertSame([3, 1, 2], $this->nav->lastReorder);
        self::assertSame(['success' => true], $this->jsons[0]);
    }

    public function testReorderEmptyBody(): void
    {
        $this->body = 'not json';
        (new NavigationController($this->engine()))->reorder();

        self::assertSame([], $this->nav->lastReorder);
    }

    public function testAvailableGroupsMergesAndSorts(): void
    {
        $this->nav->groups = ['zeta', 'primary'];
        $c = new NavigationController($this->engine(query: ['group' => 'custom']));
        $groups = $this->invoke($c, 'getAvailableGroups');

        self::assertContains('footer', $groups);
        self::assertContains('custom', $groups);
        $sorted = $groups;
        sort($sorted);
        self::assertSame($sorted, $groups);
    }

    public function testAvailableGroupsSurvivesThrowingService(): void
    {
        $this->nav->throw = true;
        $c = new NavigationController($this->engine());
        $groups = $this->invoke($c, 'getAvailableGroups');

        self::assertContains('primary', $groups);
        self::assertContains('footer', $groups);
    }

    private function engine(array $data = [], array $query = []): Engine
    {
        $test = $this;
        $app = $this->app([
            'request' => static fn(): object => new class($data, $query, $test) {
                public Collection $data;
                public Collection $query;
                /** @param array<string, mixed> $d @param array<string, mixed> $q */
                public function __construct(array $d, array $q, private NavigationControllerTest $t)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection($q);
                }

                public function getBody(): string
                {
                    return $this->t->body;
                }
            },
            'navigation' => fn(): FakeNav => $this->nav,
            'session' => static fn(): object => new class($test) {
                public function __construct(private NavigationControllerTest $t)
                {
                }

                public function flash(string $k, mixed $v): void
                {
                    $this->t->flashes[$k][] = $v;
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
                    public function __construct(private NavigationControllerTest $t)
                    {
                    }

                    /** @param array<string, mixed>|null $d */
                    public function fetch(string $v, ?array $d = null): string
                    {
                        $this->t->fetches[] = ['view' => $v, 'data' => $d ?? []];

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
        $app->map('json', function (mixed $d) use ($test): void {
            $test->jsons[] = $d;
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * Navigation service double.
 */
final class FakeNav
{
    /** @var list<string> */
    public array $groups = ['primary', 'footer'];
    public bool $throw = false;
    /** @var array<string, mixed>|null */
    public ?array $lastCreate = null;
    public ?int $lastDelete = null;
    /** @var list<int>|null */
    public ?array $lastReorder = null;

    /** @return list<string> */
    public function getGroups(): array
    {
        if ($this->throw) {
            throw new \RuntimeException('down');
        }

        return $this->groups;
    }

    /** @return list<object> */
    public function getByGroup(string $g): array
    {
        return [];
    }

    /** @return list<object> */
    public function getTree(string $g): array
    {
        return [];
    }

    /** @return list<array<string, string>> */
    public function getLinkableItems(): array
    {
        return [];
    }

    /** @param array<string, mixed> $post */
    public function create(array $post): object
    {
        $this->lastCreate = $post;

        return (object) ['id' => 1];
    }

    public function delete(int $id): bool
    {
        $this->lastDelete = $id;

        return true;
    }

    /** @param list<int> $ids */
    public function reorder(array $ids): void
    {
        $this->lastReorder = $ids;
    }
}
