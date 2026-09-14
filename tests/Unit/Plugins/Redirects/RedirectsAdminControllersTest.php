<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Redirects;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Redirects\Controllers\RedirectLinksAdminController;
use Pubvana\Plugins\Redirects\Controllers\RedirectsAdminController;
use Pubvana\Plugins\Redirects\Services\RedirectLinksService;
use Pubvana\Plugins\Redirects\Services\RedirectsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Both Redirects admin controllers over the real services.
 */
#[CoversClass(RedirectsAdminController::class)]
#[CoversClass(RedirectLinksAdminController::class)]
final class RedirectsAdminControllersTest extends TestCase
{
    private PDO $pdo;
    private RedirectsService $redirects;
    private RedirectLinksService $links;

    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirectsTo = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        RedirectsSchema::create($this->pdo);
        $this->redirects = new RedirectsService($this->pdo, $this->app([]), ['skip_prefixes' => []]);
        $this->links = new RedirectLinksService($this->pdo, $this->app([]), []);
        $this->fetches = [];
        $this->redirectsTo = [];
        $this->flashes = [];
    }

    public function testIndexRendersList(): void
    {
        $this->redirects->create(['source_path' => '/a', 'target_url' => '/b']);

        (new RedirectsAdminController($this->engine()))->index();
        self::assertSame('pubvana/redirects/admin/index', $this->fetches[0]['view']);
        self::assertSame('Redirects', $this->fetches[0]['data']['pageTitle']);
        self::assertCount(1, $this->fetches[0]['data']['redirects']);
        self::assertSame('/admin/redirects', $this->fetches[0]['data']['adminBase']);
    }

    public function testCreateRendersFormWithPrefill(): void
    {
        (new RedirectsAdminController($this->engine(query: ['source_path' => '/old', 'incoming_404_id' => '7'])))->create();

        self::assertSame('pubvana/redirects/admin/create', $this->fetches[0]['view']);
        self::assertSame('/old', $this->fetches[0]['data']['prefillSourcePath']);
        self::assertSame(7, $this->fetches[0]['data']['incoming404Id']);
        self::assertSame([], $this->fetches[0]['data']['targetSuggestions']);
    }

    public function testStoreCreatesAndResolves(): void
    {
        $this->pdo->exec("INSERT INTO redirects_links (source_path, hit_count) VALUES ('/old', 2)");
        $linkId = (int) $this->pdo->lastInsertId();

        $app = $this->engine(data: [
            'source_path' => '/old',
            'target_url' => '/new',
            'status_code' => '301',
            'enabled' => '1',
            'incoming_404_id' => (string) $linkId,
        ]);
        (new RedirectsAdminController($app))->store();

        self::assertSame('Redirect created.', $this->flashes['success'][0]);
        self::assertSame(['/admin/redirects'], $this->redirectsTo);
        $link = $this->links->find($linkId);
        self::assertNotNull($link);
        self::assertNotNull($link->resolved_redirect_id);
    }

    public function testStoreRefusesHostileTarget(): void
    {
        $app = $this->engine(data: ['source_path' => '/old', 'target_url' => 'javascript://x']);
        (new RedirectsAdminController($app))->store();

        self::assertStringContainsString('http:// or https://', $this->flashes['error'][0]);
        self::assertSame(['/admin/redirects/create'], $this->redirectsTo);
        self::assertSame(0, $this->redirects->countAll());
    }

    public function testEditMissAndHit(): void
    {
        (new RedirectsAdminController($this->engine()))->edit('99');
        self::assertSame('Redirect not found.', $this->flashes['error'][0]);
        self::assertSame(['/admin/redirects'], $this->redirectsTo);

        $this->redirects->create(['source_path' => '/a', 'target_url' => '/b']);
        $this->fetches = [];
        (new RedirectsAdminController($this->engine()))->edit('1');
        self::assertSame('pubvana/redirects/admin/edit', $this->fetches[0]['view']);
        self::assertSame('/a', $this->fetches[0]['data']['redirect']->source_path);
    }

    public function testUpdatePaths(): void
    {
        $this->redirects->create(['source_path' => '/a', 'target_url' => '/b']);

        // Hostile target: error flash + back to edit.
        (new RedirectsAdminController($this->engine(data: ['source_path' => '/a', 'target_url' => 'javascript://x'])))->update('1');
        self::assertStringContainsString('http:// or https://', $this->flashes['error'][0]);
        self::assertSame(['/admin/redirects/1/edit'], $this->redirectsTo);

        // Unknown id: not-found flash + back to list.
        $this->flashes = [];
        $this->redirectsTo = [];
        (new RedirectsAdminController($this->engine(data: ['source_path' => '/z', 'target_url' => '/y'])))->update('99');
        self::assertSame('Redirect not found.', $this->flashes['error'][0]);
        self::assertSame(['/admin/redirects'], $this->redirectsTo);

        // Happy path.
        $this->flashes = [];
        $this->redirectsTo = [];
        (new RedirectsAdminController($this->engine(data: ['source_path' => '/a2', 'target_url' => '/b2'])))->update('1');
        self::assertSame('Redirect updated.', $this->flashes['success'][0]);
        self::assertSame(['/admin/redirects/1/edit'], $this->redirectsTo);
        self::assertSame('/a2', $this->redirects->find(1)?->source_path);
    }

    public function testDeletePaths(): void
    {
        (new RedirectsAdminController($this->engine()))->delete('99');
        self::assertSame('Redirect not found.', $this->flashes['error'][0]);

        $this->redirects->create(['source_path' => '/a', 'target_url' => '/b']);
        $this->flashes = [];
        (new RedirectsAdminController($this->engine()))->delete('1');
        self::assertSame('Redirect deleted.', $this->flashes['success'][0]);
        self::assertSame(['/admin/redirects', '/admin/redirects'], $this->redirectsTo);
    }

    public function testLinksIndexStatusWhitelist(): void
    {
        (new RedirectLinksAdminController($this->engine()))->index();
        self::assertSame('active', $this->fetches[0]['data']['status']);

        $this->fetches = [];
        (new RedirectLinksAdminController($this->engine(query: ['status' => 'bogus'])))->index();
        self::assertSame('active', $this->fetches[0]['data']['status']);

        $this->fetches = [];
        (new RedirectLinksAdminController($this->engine(query: ['status' => 'ignored'])))->index();
        self::assertSame('ignored', $this->fetches[0]['data']['status']);
        self::assertSame('pubvana/redirects/admin/incoming-404s', $this->fetches[0]['data']['pageTitle'] === '404 Manager' ? 'pubvana/redirects/admin/incoming-404s' : '');
    }

    public function testLinksIgnoreUnignoreDelete(): void
    {
        $this->pdo->exec("INSERT INTO redirects_links (source_path, hit_count) VALUES ('/old', 1)");
        $id = (int) $this->pdo->lastInsertId();

        (new RedirectLinksAdminController($this->engine()))->ignore((string) $id);
        self::assertSame('Entry ignored.', $this->flashes['success'][0]);
        self::assertSame(['/admin/redirects/404-manager'], $this->redirectsTo);

        $this->flashes = [];
        $this->redirectsTo = [];
        (new RedirectLinksAdminController($this->engine()))->unignore((string) $id);
        self::assertSame('Entry unignored.', $this->flashes['success'][0]);
        self::assertSame(['/admin/redirects/404-manager?status=ignored'], $this->redirectsTo);

        $this->flashes = [];
        $this->redirectsTo = [];
        (new RedirectLinksAdminController($this->engine()))->delete((string) $id);
        self::assertSame('Entry deleted.', $this->flashes['success'][0]);

        // Misses flash errors.
        $this->flashes = [];
        (new RedirectLinksAdminController($this->engine()))->ignore('99');
        self::assertSame('Entry not found.', $this->flashes['error'][0]);
        $this->flashes = [];
        (new RedirectLinksAdminController($this->engine()))->unignore('99');
        self::assertSame('Entry not found.', $this->flashes['error'][0]);
        $this->flashes = [];
        (new RedirectLinksAdminController($this->engine()))->delete('99');
        self::assertSame('Entry not found.', $this->flashes['error'][0]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $query
     */
    private function engine(array $data = [], array $query = []): Engine
    {
        $test = $this;
        $redirects = $this->redirects;
        $links = $this->links;
        $app = $this->app([
            'request' => static fn(): object => new class($data, $query) {
                public Collection $data;
                public Collection $query;
                /** @param array<string, mixed> $d @param array<string, mixed> $q */
                public function __construct(array $d, array $q)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection($q);
                }
            },
            'redirects' => static fn(): RedirectsService => $redirects,
            'redirectLinks' => static fn(): RedirectLinksService => $links,
            'auth' => static fn(): object => new class {
                public function user(): object
                {
                    return new class {
                        public int $id = 9;

                        /** @return list<string> */
                        public function getGroups(): array
                        {
                            return ['admin'];
                        }
                    };
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/redirects';
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private RedirectsAdminControllersTest $t)
                {
                }

                public function flash(string $k, mixed $v): void
                {
                    $this->t->flashes[$k][] = $v;
                }

                public function pullFlash(string $k): mixed
                {
                    return null;
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
                    public function __construct(private RedirectsAdminControllersTest $t)
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
            $test->redirectsTo[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}
