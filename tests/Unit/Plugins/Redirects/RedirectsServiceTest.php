<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Redirects;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Redirects\Services\RedirectLinksService;
use Pubvana\Plugins\Redirects\Services\RedirectsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * RedirectsService CRUD, normalization, counts, suggestions.
 *
 * handleCurrentRequest() early-returns under CLI (php_sapi_name), so the
 * live redirect path is exercised through the private helpers via invoke().
 */
#[CoversClass(RedirectsService::class)]
final class RedirectsServiceTest extends TestCase
{
    private PDO $pdo;
    private RedirectsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        RedirectsSchema::create($this->pdo);
        $this->service = new RedirectsService($this->pdo, $this->app([]), ['skip_prefixes' => ['/admin', '/api']]);
    }

    public function testCreateNormalizesPayload(): void
    {
        $redirect = $this->service->create([
            'source_path' => 'old//path/',
            'target_url' => 'new-location',
            'status_code' => '302',
            'enabled' => '1',
            'notes' => '  hello  ',
        ]);

        self::assertSame('/old/path', $redirect->source_path);
        self::assertSame('/new-location', $redirect->target_url);
        self::assertSame(302, (int) $redirect->status_code);
        self::assertSame(1, (int) $redirect->enabled);
        self::assertSame('hello', $redirect->notes);
        self::assertSame(0, (int) $redirect->hit_count);
        self::assertNull($redirect->last_hit_at);
    }

    public function testCreateDefaults(): void
    {
        $redirect = $this->service->create(['source_path' => '/a', 'target_url' => '']);

        self::assertSame('/', $redirect->target_url);
        self::assertSame(301, (int) $redirect->status_code);
        self::assertSame(0, (int) $redirect->enabled);
        self::assertNull($redirect->notes);
    }

    public function testCreateFallsBackOnBadStatus(): void
    {
        $redirect = $this->service->create(['source_path' => '/a', 'target_url' => '/b', 'status_code' => '307']);
        self::assertSame(301, (int) $redirect->status_code);
    }

    public function testCreateEmptySourceBecomesRoot(): void
    {
        $redirect = $this->service->create(['source_path' => '', 'target_url' => '/b']);
        self::assertSame('/', $redirect->source_path);
    }

    public function testUpdateAndDelete(): void
    {
        $redirect = $this->service->create(['source_path' => '/a', 'target_url' => '/b']);
        $id = (int) $redirect->id;

        $updated = $this->service->update($id, [
            'source_path' => '/a2',
            'target_url' => 'https://example.com/x',
            'status_code' => '302',
            'enabled' => '1',
            'notes' => 'n',
        ]);
        self::assertNotNull($updated);
        self::assertSame('/a2', $updated->source_path);
        self::assertSame('https://example.com/x', $updated->target_url);

        self::assertNull($this->service->update(99999, ['source_path' => '/z', 'target_url' => '/y']));

        self::assertTrue($this->service->delete($id));
        self::assertNull($this->service->find($id));
        self::assertFalse($this->service->delete($id));
        self::assertFalse($this->service->delete(99999));
    }

    public function testAllFindCounts(): void
    {
        self::assertSame([], $this->service->all());
        self::assertSame(0, $this->service->countAll());
        self::assertSame(0, $this->service->countEnabled());
        self::assertNull($this->service->find(99999));

        $this->service->create(['source_path' => '/b', 'target_url' => '/x', 'enabled' => '1']);
        $this->service->create(['source_path' => '/a', 'target_url' => '/y']);
        $this->service->create(['source_path' => '/c', 'target_url' => '/z', 'enabled' => '1']);

        $all = $this->service->all();
        self::assertSame(['/a', '/b', '/c'], array_map(static fn($r): string => $r->source_path, $all));
        self::assertSame(3, $this->service->countAll());
        self::assertSame(2, $this->service->countEnabled());
    }

    public function testGetTargetSuggestionsEmptyWhenNoProviders(): void
    {
        $app = $this->app([
            'pages' => static function (): object {
                throw new \RuntimeException('no pages');
            },
            'blog' => static function (): object {
                throw new \RuntimeException('no blog');
            },
        ]);
        $service = new RedirectsService($this->pdo, $app);

        self::assertSame([], $service->getTargetSuggestions());
    }

    public function testGetTargetSuggestionsGroups(): void
    {
        $page = new \stdClass();
        $page->title = 'About';
        $page->slug = 'about';
        $post = new \stdClass();
        $post->title = 'Hello';
        $post->slug = 'hello';

        $app = $this->app([
            'pages' => static fn(): object => new class($page) {
                public function __construct(private object $p)
                {
                }

                /** @return list<object> */
                public function listPublished(int $limit = 100): array
                {
                    return [$this->p];
                }
            },
            'blog' => static fn(): object => new class($post) {
                public function __construct(private object $p)
                {
                }

                /** @return array<string, mixed> */
                public function listPosts(int $page, int $perPage, string $status): array
                {
                    return ['items' => [$this->p]];
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/blog';
                }
            },
        ]);
        $service = new RedirectsService($this->pdo, $app);

        $groups = $service->getTargetSuggestions();
        self::assertSame('About', $groups['Pages'][0]['label']);
        self::assertSame('/page/about', $groups['Pages'][0]['url']);
        self::assertSame('Hello', $groups['Blog Posts'][0]['label']);
        self::assertSame('/blog/hello', $groups['Blog Posts'][0]['url']);
    }

    public function testGetTargetSuggestionsSkipsEmptyProviders(): void
    {
        $app = $this->app([
            'pages' => static fn(): object => new class {
                /** @return list<object> */
                public function listPublished(int $limit = 100): array
                {
                    return [];
                }
            },
            'blog' => static fn(): object => new class {
                /** @return array<string, mixed> */
                public function listPosts(int $page, int $perPage, string $status): array
                {
                    return ['items' => []];
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/blog';
                }
            },
        ]);
        $service = new RedirectsService($this->pdo, $app);

        self::assertSame([], $service->getTargetSuggestions());
    }

    public function testNormalizeSourcePathVariants(): void
    {
        self::assertSame('/', $this->invoke($this->service, 'normalizeSourcePath', ['']));
        self::assertSame('/', $this->invoke($this->service, 'normalizeSourcePath', ['   ']));
        self::assertSame('/a/b', $this->invoke($this->service, 'normalizeSourcePath', ['a//b/']));
        self::assertSame('/a', $this->invoke($this->service, 'normalizeSourcePath', ['http://x.test/a?x=1']));
        // No path component: the raw input passes through with a leading slash.
        self::assertSame('/?x=1', $this->invoke($this->service, 'normalizeSourcePath', ['?x=1']));
    }

    public function testNormalizeTargetUrlVariants(): void
    {
        self::assertSame('/', $this->invoke($this->service, 'normalizeTargetUrl', ['']));
        self::assertSame('/x', $this->invoke($this->service, 'normalizeTargetUrl', ['x']));
        self::assertSame('https://x.test/a', $this->invoke($this->service, 'normalizeTargetUrl', ['https://x.test/a']));
    }

    public function testBuildRedirectLocationAppendsQuery(): void
    {
        $_SERVER['QUERY_STRING'] = '';
        self::assertSame('/b', $this->invoke($this->service, 'buildRedirectLocation', ['/b']));

        $_SERVER['QUERY_STRING'] = 'a=1';
        self::assertSame('/b?a=1', $this->invoke($this->service, 'buildRedirectLocation', ['/b']));
        self::assertSame('/b?x=1&a=1', $this->invoke($this->service, 'buildRedirectLocation', ['/b?x=1']));
        unset($_SERVER['QUERY_STRING']);
    }

    public function testShouldSkipPath(): void
    {
        self::assertTrue($this->invoke($this->service, 'shouldSkipPath', ['/admin']));
        self::assertTrue($this->invoke($this->service, 'shouldSkipPath', ['/admin/pages']));
        self::assertTrue($this->invoke($this->service, 'shouldSkipPath', ['/api/x']));
        self::assertFalse($this->invoke($this->service, 'shouldSkipPath', ['/about']));
        self::assertFalse($this->invoke($this->service, 'shouldSkipPath', ['/administrator']));
    }

    public function testIsSelfRedirect(): void
    {
        // Different host is never a self redirect.
        self::assertFalse($this->invoke($this->service, 'isSelfRedirect', ['https://other.test/a', '/a', 'example.test']));
        // Same host + same path is a self redirect.
        self::assertTrue($this->invoke($this->service, 'isSelfRedirect', ['https://example.test/a', '/a', 'example.test']));
        // Relative target matching current path.
        self::assertTrue($this->invoke($this->service, 'isSelfRedirect', ['/a', '/a', 'example.test']));
        // Different path is fine.
        self::assertFalse($this->invoke($this->service, 'isSelfRedirect', ['/b', '/a', 'example.test']));
        // Target without a path is not a self redirect.
        self::assertFalse($this->invoke($this->service, 'isSelfRedirect', ['https://example.test', '/a', 'example.test']));
    }

    public function testHandleCurrentRequestReturnsUnderCli(): void
    {
        // php_sapi_name() is cli under PHPUnit: must return without touching request.
        $this->service->handleCurrentRequest();
        self::assertSame(0, $this->service->countAll());

        (new RedirectLinksService($this->pdo, $this->app([])))->logCurrentRequest();
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) c FROM redirects_links')->fetch()['c']);
    }
}
