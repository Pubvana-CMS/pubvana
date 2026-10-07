<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SocialLinks;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\SocialLinks\Controllers\SocialLinksAdminController;
use Pubvana\Plugins\SocialLinks\Services\SocialLinksService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * SocialLinksAdminController over the real service.
 */
#[CoversClass(SocialLinksAdminController::class)]
final class SocialLinksAdminControllerTest extends TestCase
{
    private PDO $pdo;
    private SocialLinksService $links;

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
        SocialLinksSchema::create($this->pdo);
        $this->links = new SocialLinksService($this->pdo, []);
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
    }

    public function testIndexRendersList(): void
    {
        $this->links->create(['platform' => 'github', 'url' => 'https://a.test']);

        (new SocialLinksAdminController($this->engine()))->index();
        self::assertSame('pubvana/social-links/admin/index', $this->fetches[0]['view']);
        self::assertSame('Social Links', $this->fetches[0]['data']['pageTitle']);
        self::assertCount(1, $this->fetches[0]['data']['links']);
        self::assertArrayHasKey('github', $this->fetches[0]['data']['platforms']);
    }

    public function testStoreSuccessAndFailure(): void
    {
        $app = $this->engine(data: ['platform' => 'github', 'url' => 'https://a.test', '_csrf_token' => 'tok']);
        (new SocialLinksAdminController($app))->store();

        self::assertSame('Social link added.', $this->flashes['success'][0]);
        self::assertSame(['/admin/social-links'], $this->redirects);
        self::assertCount(1, $this->links->all());

        $this->flashes = [];
        $this->redirects = [];
        $bad = $this->engine(data: ['platform' => 'github', 'url' => '']);
        (new SocialLinksAdminController($bad))->store();

        self::assertStringContainsString('full http:// or https://', $this->flashes['error'][0]);
        self::assertSame(['/admin/social-links'], $this->redirects);
    }

    public function testTogglePaths(): void
    {
        (new SocialLinksAdminController($this->engine()))->toggle('99');
        self::assertSame('Social link not found.', $this->flashes['error'][0]);

        $link = $this->links->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);
        $this->flashes = [];
        (new SocialLinksAdminController($this->engine()))->toggle((string) $link->id);
        self::assertSame('Social link updated.', $this->flashes['success'][0]);
        self::assertSame(['/admin/social-links', '/admin/social-links'], $this->redirects);
    }

    public function testDeletePaths(): void
    {
        (new SocialLinksAdminController($this->engine()))->delete('99');
        self::assertSame('Social link not found.', $this->flashes['error'][0]);

        $link = $this->links->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);
        $this->flashes = [];
        (new SocialLinksAdminController($this->engine()))->delete((string) $link->id);
        self::assertSame('Social link deleted.', $this->flashes['success'][0]);
    }

    public function testEditRendersFormAndMissingId(): void
    {
        $link = $this->links->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);

        (new SocialLinksAdminController($this->engine()))->edit((string) $link->id);
        self::assertSame('pubvana/social-links/admin/edit', $this->fetches[0]['view']);
        self::assertSame('Edit Social Link', $this->fetches[0]['data']['pageTitle']);
        self::assertSame((int) $link->id, (int) $this->fetches[0]['data']['link']->id);
        self::assertArrayHasKey('github', $this->fetches[0]['data']['platforms']);

        $this->fetches = [];
        (new SocialLinksAdminController($this->engine()))->edit('99');
        self::assertSame([], $this->fetches);
        self::assertSame('Social link not found.', $this->flashes['error'][0]);
        self::assertSame(['/admin/social-links'], $this->redirects);
    }

    public function testUpdatePaths(): void
    {
        $link = $this->links->create(['platform' => 'github', 'url' => 'https://a.test']);
        self::assertNotNull($link);
        $id = (string) $link->id;

        $ok = $this->engine(data: ['platform' => 'github', 'url' => 'https://github.com/acme', '_csrf_token' => 'tok']);
        (new SocialLinksAdminController($ok))->update($id);
        self::assertSame('Social link updated.', $this->flashes['success'][0]);
        self::assertSame(['/admin/social-links'], $this->redirects);

        $this->flashes = [];
        $this->redirects = [];
        $bad = $this->engine(data: ['platform' => 'github', 'url' => 'example.com']);
        (new SocialLinksAdminController($bad))->update($id);
        self::assertStringContainsString('full http:// or https://', $this->flashes['error'][0]);
        self::assertSame(['/admin/social-links/' . $id . '/edit'], $this->redirects);

        $this->flashes = [];
        $this->redirects = [];
        $miss = $this->engine(data: ['platform' => 'github', 'url' => 'https://x.test']);
        (new SocialLinksAdminController($miss))->update('99');
        self::assertSame('Social link not found.', $this->flashes['error'][0]);
        self::assertSame(['/admin/social-links'], $this->redirects);
    }

    public function testReorderPaths(): void
    {
        $a = $this->links->create(['platform' => 'github', 'url' => 'https://a.test']);
        $b = $this->links->create(['platform' => 'x', 'url' => 'https://b.test']);
        self::assertNotNull($a);
        self::assertNotNull($b);

        // Happy path down.
        $app = $this->engine(data: ['direction' => 'down']);
        (new SocialLinksAdminController($app))->reorder((string) $a->id);
        self::assertSame('Social links reordered.', $this->flashes['success'][0]);

        // Bad direction defaults to down; single-item edge then fails.
        $this->flashes = [];
        $bad = $this->engine(data: ['direction' => 'sideways']);
        (new SocialLinksAdminController($bad))->reorder((string) $b->id);
        self::assertSame('Social links reordered.', $this->flashes['success'][0]);

        // Unknown id fails.
        $this->flashes = [];
        (new SocialLinksAdminController($this->engine(data: [])))->reorder('99');
        self::assertSame('Social link not found.', $this->flashes['error'][0]);
    }

    public function testReorderAtTheEndReportsTheEdge(): void
    {
        $a = $this->links->create(['platform' => 'github', 'url' => 'https://a.test']);
        $b = $this->links->create(['platform' => 'x', 'url' => 'https://b.test']);
        self::assertNotNull($a);
        self::assertNotNull($b);

        (new SocialLinksAdminController($this->engine(data: ['direction' => 'down'])))->reorder((string) $b->id);
        self::assertSame('That link is already at the end of the list.', $this->flashes['error'][0]);
        self::assertArrayNotHasKey('success', $this->flashes);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function engine(array $data = []): Engine
    {
        $test = $this;
        $links = $this->links;
        $app = $this->app([
            // Settings-store stand-in: resolves CMS.* the way production does.
            'settings' => static fn(): object => new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return \Flight::app()->get($key) ?? $default;
                }
            },
            'request' => static fn(): object => new class($data) {
                public Collection $data;
                public Collection $query;
                /** @param array<string, mixed> $d */
                public function __construct(array $d)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection([]);
                }
            },
            'socialLinks' => static fn(): SocialLinksService => $links,
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
            'session' => static fn(): object => new class($test) {
                public function __construct(private SocialLinksAdminControllerTest $t)
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
                    public function __construct(private SocialLinksAdminControllerTest $t)
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
