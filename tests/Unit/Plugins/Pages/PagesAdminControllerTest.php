<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Pages\Controllers\PagesAdminController;
use Pubvana\Plugins\Pages\Services\PagesService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PagesAdminController coverage over the real PagesService.
 */
#[CoversClass(PagesAdminController::class)]
final class PagesAdminControllerTest extends TestCase
{
    private PDO $pdo;
    private PagesService $pages;

    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        PagesSchema::create($this->pdo);
        $this->pages = new PagesService($this->pdo, ['route_prefix' => '/page', 'max_revisions' => 15]);
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];

        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
        ]);
        \Flight::setEngine($app);
    }

    public function testIndexPaginates(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->pages->createPage(['title' => "P{$i}", 'status' => 'draft'], 1);
        }

        (new PagesAdminController($this->engine()))->index();
        self::assertSame('pubvana/pages/admin/index', $this->fetches[0]['view']);
        self::assertSame('Pages', $this->fetches[0]['data']['pageTitle']);
        self::assertSame(3, $this->fetches[0]['data']['total']);
        self::assertSame(1, $this->fetches[0]['data']['page']);
        self::assertSame('/admin/page', $this->fetches[0]['data']['adminBase']);
        self::assertSame('/page', $this->fetches[0]['data']['publicBase']);

        // Page floor: ?page=0 becomes 1.
        $this->fetches = [];
        (new PagesAdminController($this->engine(query: ['page' => '0'])))->index();
        self::assertSame(1, $this->fetches[0]['data']['page']);
    }

    public function testCreateRendersForm(): void
    {
        (new PagesAdminController($this->engine()))->create();

        self::assertSame('pubvana/pages/admin/create', $this->fetches[0]['view']);
        self::assertSame('New Page', $this->fetches[0]['data']['pageTitle']);
        self::assertSame('JODIT', $this->fetches[0]['data']['joditHtml']);
        self::assertSame('/admin/page', $this->fetches[0]['data']['adminBase']);
    }

    public function testStoreRejectsBlankTitle(): void
    {
        (new PagesAdminController($this->engine(data: ['title' => '  '])))->store();

        self::assertSame('Title is required.', $this->flashes['error'][0]);
        self::assertSame(['/admin/page/create'], $this->redirects);
        self::assertSame(0, $this->pages->listPages()['total']);
    }

    public function testStoreCreatesPage(): void
    {
        $app = $this->engine(data: [
            'title' => 'Hello World',
            'content' => 'body',
            'status' => 'published',
            '_csrf_token' => 'tok',
        ]);
        (new PagesAdminController($app))->store();

        self::assertSame('Page created.', $this->flashes['success'][0]);
        self::assertSame(['/admin/page'], $this->redirects);
        $page = $this->pages->findPage(1);
        self::assertNotNull($page);
        self::assertSame('Hello World', $page->title);
        self::assertSame('published', $page->status);
    }

    public function testEditMissAndHit(): void
    {
        (new PagesAdminController($this->engine()))->edit('99');
        self::assertSame(['/admin/page'], $this->redirects);

        $this->pages->createPage(['title' => 'E', 'status' => 'draft'], 1);
        (new PagesAdminController($this->engine()))->edit('1');
        self::assertSame('pubvana/pages/admin/edit', $this->fetches[0]['view']);
        self::assertSame('E', $this->fetches[0]['data']['editPage']->title);
        self::assertSame('JODIT', $this->fetches[0]['data']['joditHtml']);
        self::assertSame('/page', $this->fetches[0]['data']['publicBase']);
    }

    public function testUpdateRejectsBlankTitle(): void
    {
        $this->pages->createPage(['title' => 'E', 'status' => 'draft'], 1);
        (new PagesAdminController($this->engine(data: ['title' => ''])))->update('1');

        self::assertSame('Title is required.', $this->flashes['error'][0]);
        self::assertSame(['/admin/page/1/edit'], $this->redirects);
    }

    public function testUpdateMissRedirectsWithoutFlash(): void
    {
        (new PagesAdminController($this->engine(data: ['title' => 'X'])))->update('99');

        self::assertSame(['/admin/page'], $this->redirects);
        self::assertSame([], $this->flashes);
    }

    public function testUpdateHitFlashesAndRedirects(): void
    {
        $this->pages->createPage(['title' => 'E', 'status' => 'draft'], 1);
        (new PagesAdminController($this->engine(data: ['title' => 'E2'])))->update('1');

        self::assertSame('Page updated.', $this->flashes['success'][0]);
        self::assertSame(['/admin/page/1/edit'], $this->redirects);
        self::assertSame('E2', $this->pages->findPage(1)?->title);
    }

    public function testDelete(): void
    {
        $this->pages->createPage(['title' => 'X', 'status' => 'draft'], 1);

        (new PagesAdminController($this->engine()))->delete('1');
        self::assertSame('Page deleted.', $this->flashes['success'][0]);
        self::assertSame(['/admin/page'], $this->redirects);
        self::assertNull($this->pages->findPage(1));
    }

    public function testRevisionsMissAndHit(): void
    {
        (new PagesAdminController($this->engine()))->revisions('99999');
        self::assertSame(['/admin/page'], $this->redirects);

        $page = $this->pages->createPage(['title' => 'Y', 'status' => 'draft'], 1);
        $id = (int) $page->id;

        $this->fetches = [];
        (new PagesAdminController($this->engine()))->revisions((string) $id);
        self::assertSame('pubvana/pages/admin/revisions', $this->fetches[0]['view']);
        self::assertSame('Revisions: Y', $this->fetches[0]['data']['pageTitle']);
        self::assertCount(1, $this->fetches[0]['data']['revisions']);
    }

    public function testRestore(): void
    {
        $page = $this->pages->createPage(['title' => 'V1', 'status' => 'draft'], 1);
        $id = (int) $page->id;
        $this->pages->updatePage($id, ['title' => 'V2'], 1);
        $revs = $this->pages->getRevisions($id);

        (new PagesAdminController($this->engine()))->restore((string) $id, (string) $revs[0]->id);
        self::assertSame('Revision restored.', $this->flashes['success'][0]);
        self::assertSame(["/admin/page/{$id}/edit"], $this->redirects);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $query
     */
    private function engine(array $data = [], array $query = []): Engine
    {
        $test = $this;
        $pages = $this->pages;
        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
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
            'pages' => static fn(): PagesService => $pages,
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
            'media' => static fn(): object => new class {
                public function joditInit(string $sel): string
                {
                    return 'JODIT';
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/page';
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private PagesAdminControllerTest $t)
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
                    public function __construct(private PagesAdminControllerTest $t)
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
        \Flight::setEngine($app);

        return $app;
    }
}
