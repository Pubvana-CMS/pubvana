<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Controllers\BlogPublicController;
use Pubvana\Plugins\Blog\Services\BlogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * BlogPublicController coverage.
 *
 * render() is captured through a public override (widened visibility),
 * so tests assert template choice + view data without the Vision pipeline.
 * getAuthor() reads \Flight::db() statically, so the engine carries a db
 * mapping and is set as the global.
 */
#[CoversClass(BlogPublicController::class)]
final class BlogPublicControllerTest extends TestCase
{
    private PDO $pdo;
    private BlogService $blog;

    /** @var array<string, mixed> */
    public array $renders = [];
    /** @var list<array{code: int, msg: string}> */
    public array $halts = [];
    /** @var array<string, mixed> */
    public array $settingsRows = [];
    public ?object $profileResult = null;

    /** @var list<array<string, mixed>> Contexts passed to regions()->setContext() */
    public array $regionsContexts = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        BlogSchema::create($this->pdo);
        $this->blog = new BlogService($this->pdo, ['route_prefix' => '/blog']);
        $this->renders = [];
        $this->halts = [];
        $this->settingsRows = [];
        $this->profileResult = null;
        $this->regionsContexts = [];
    }

    public function testIndexRendersListingWithPagination(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->blog->createPost(['title' => "P{$i}", 'slug' => "p{$i}", 'status' => 'published'], 1);
        }

        $this->controller($this->engine())->index();

        self::assertSame('pubvana/blog/home', $this->renders[0]['template']);
        self::assertCount(10, $this->renders[0]['data']['posts']);
        self::assertSame('/blog', $this->renders[0]['data']['pagination']['current'] === 1
            ? $this->renders[0]['data']['pagination']['pages'][0]['url']
            : '');
        self::assertSame(2, $this->renders[0]['data']['pagination']['total']);
        self::assertSame('/blog/page/2', $this->renders[0]['data']['pagination']['next_url']);
    }

    public function testIndexPageSegmentAndQuery(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->blog->createPost(['title' => "P{$i}", 'slug' => "p{$i}", 'status' => 'published'], 1);
        }

        $this->controller($this->engine())->index('2');
        self::assertCount(2, $this->renders[0]['data']['posts']);
        self::assertSame(2, $this->renders[0]['data']['pagination']['current']);

        $this->renders = [];
        $this->controller($this->engine(query: ['page' => '2']))->index();
        self::assertCount(2, $this->renders[0]['data']['posts']);
    }

    public function testIndexSinglePageHasNoPagination(): void
    {
        $this->blog->createPost(['title' => 'Only', 'slug' => 'only', 'status' => 'published'], 1);

        $this->controller($this->engine())->index();

        self::assertNull($this->renders[0]['data']['pagination']);
    }

    public function testCategoriesAndTagsLists(): void
    {
        $this->blog->createCategory(['name' => 'News', 'slug' => 'news']);
        $post = $this->blog->createPost(['title' => 'T', 'slug' => 't', 'status' => 'draft'], 1);
        $this->blog->syncPostTags((int) $post->id, 'php');

        $this->controller($this->engine())->categories();
        self::assertSame('pubvana/blog/categories', $this->renders[0]['view'] ?? $this->renders[0]['template']);
        self::assertSame('/blog/category/news', $this->renders[0]['data']['categories'][0]['url']);

        $this->renders = [];
        $this->controller($this->engine())->tags();
        self::assertSame('/blog/tag/php', $this->renders[0]['data']['tags'][0]['url']);
    }

    public function testShowRendersPost(): void
    {
        $this->pdo->exec("INSERT INTO users (username, active) VALUES ('alice', 1)");
        $uid = (int) $this->pdo->lastInsertId();
        $post = $this->blog->createPost([
            'title' => 'Hello', 'slug' => 'hello', 'content' => 'body',
            'excerpt' => 'ex', 'status' => 'published', 'allow_comments' => 1,
        ], $uid);

        $this->controller($this->engine())->show('hello');

        self::assertSame('pubvana/blog/post', $this->renders[0]['template']);
        $data = $this->renders[0]['data'];
        self::assertSame('Hello', $data['title']);
        self::assertSame(['type' => 'blog', 'id' => (int) $post->id], $data['commentable']);
        self::assertTrue($data['allow_comments']);
        self::assertSame('post', $data['seo_context']['content_type']);
        self::assertSame('alice', $data['author']['username']);

        // View counted.
        $fresh = $this->blog->findPost((int) $post->id);
        self::assertNotNull($fresh);
        self::assertSame(1, (int) $fresh->views);

        // Region context carries the post id for blocks like Related Posts.
        self::assertSame([['post_id' => (int) $post->id]], $this->regionsContexts);
    }

    public function testShowHaltsOnMissingOrDraft(): void
    {
        $this->controller($this->engine())->show('nope');
        self::assertSame([['code' => 404, 'msg' => 'Post not found']], $this->halts);

        $this->halts = [];
        $this->blog->createPost(['title' => 'D', 'slug' => 'd', 'status' => 'draft'], 1);
        $this->controller($this->engine())->show('d');
        self::assertSame([['code' => 404, 'msg' => 'Post not found']], $this->halts);
    }

    public function testShowAiDisclosure(): void
    {
        $this->blog->createPost(['title' => 'AI', 'slug' => 'ai', 'status' => 'published', 'ai_generated' => 1], 1);

        $this->settingsRows = ['Seo.ai_disclosure_enabled' => true];
        $this->controller($this->engine())->show('ai');
        self::assertTrue($this->renders[0]['data']['ai_disclosure']);

        $this->renders = [];
        $this->halts = [];
        $this->settingsRows = ['Seo.ai_disclosure_enabled' => false];
        $this->controller($this->engine())->show('ai');
        self::assertFalse($this->renders[0]['data']['ai_disclosure']);
    }

    public function testCategoryArchive(): void
    {
        $cat = $this->blog->createCategory(['name' => 'News', 'slug' => 'news']);
        $post = $this->blog->createPost(['title' => 'InCat', 'slug' => 'incat', 'status' => 'published'], 1);
        $this->blog->syncPostCategories((int) $post->id, [(int) $cat->id]);
        $this->blog->createPost(['title' => 'Elsewhere', 'slug' => 'else', 'status' => 'published'], 1);

        $this->controller($this->engine())->category('news');
        self::assertSame('pubvana/blog/archive', $this->renders[0]['template']);
        self::assertSame('Category: News', $this->renders[0]['data']['archive_title']);
        self::assertCount(1, $this->renders[0]['data']['posts']);

        $this->renders = [];
        $this->halts = [];
        $this->controller($this->engine())->category('nope');
        self::assertSame([['code' => 404, 'msg' => 'Category not found']], $this->halts);
    }

    public function testTagArchive(): void
    {
        $post = $this->blog->createPost(['title' => 'Tagged', 'slug' => 'tagged', 'status' => 'published'], 1);
        $this->blog->syncPostTags((int) $post->id, 'php');

        $this->controller($this->engine())->tag('php');
        self::assertSame('pubvana/blog/archive', $this->renders[0]['template']);
        self::assertSame('Tag: php', $this->renders[0]['data']['archive_title']);
        self::assertCount(1, $this->renders[0]['data']['posts']);

        $this->renders = [];
        $this->halts = [];
        $this->controller($this->engine())->tag('nope');
        self::assertSame([['code' => 404, 'msg' => 'Tag not found']], $this->halts);
    }

    public function testPreview(): void
    {
        $post = $this->blog->createPost(['title' => 'Draft', 'slug' => 'draft-p', 'status' => 'draft'], 1);

        $this->controller($this->engine())->preview((string) $post->preview_token);
        self::assertSame('pubvana/blog/post', $this->renders[0]['template']);
        self::assertSame('Draft (Preview)', $this->renders[0]['data']['title']);
        self::assertFalse($this->renders[0]['data']['allow_comments']);
        self::assertSame([['post_id' => (int) $post->id]], $this->regionsContexts);

        $this->renders = [];
        $this->halts = [];
        $this->controller($this->engine())->preview('bad-token');
        self::assertSame([['code' => 404, 'msg' => 'Preview not found']], $this->halts);
    }

    public function testPublicAssetUrlVariants(): void
    {
        $c = $this->controller($this->engine());

        self::assertNull($this->invoke($c, 'publicAssetUrl', [null]));
        self::assertNull($this->invoke($c, 'publicAssetUrl', ['']));
        self::assertSame('https://x.test/a.png', $this->invoke($c, 'publicAssetUrl', ['https://x.test/a.png']));
        self::assertSame('/img/a.png', $this->invoke($c, 'publicAssetUrl', ['/img/a.png']));
        self::assertSame('/storage/up/a.png', $this->invoke($c, 'publicAssetUrl', ['up/a.png']));
    }

    public function testFeedDateFallback(): void
    {
        $c = $this->controller($this->engine());

        self::assertSame('', $this->invoke($c, 'feedDate', [null, 'r']));
        self::assertSame('', $this->invoke($c, 'feedDate', ['not-a-date', 'r']));
        self::assertNotSame('', $this->invoke($c, 'feedDate', ['2026-01-05 10:00:00', 'r']));
    }

    public function testRssAndAtom(): void
    {
        $this->blog->createPost([
            'title' => 'Feed Post', 'slug' => 'feed-post', 'content' => '<p>Hi</p>',
            'status' => 'published', 'published_at' => '2026-01-05 10:00:00',
        ], 1);

        $halted = [];
        $app = $this->engine();
        $app->map('halt', function (int $code, string $body) use (&$halted): void {
            $halted[] = ['code' => $code, 'body' => $body];
        });
        $headers = new \ArrayObject([]);
        $app->map('response', static fn(): object => new class($headers) {
            public function __construct(private \ArrayObject $h)
            {
            }

            public function header(string $k, string $v): void
            {
                $this->h[$k] = $v;
            }
        });

        $this->controller($app)->rss();
        self::assertCount(1, $halted);
        self::assertStringContainsString('<rss version="2.0"', $halted[0]['body']);
        self::assertStringContainsString('Feed Post', $halted[0]['body']);
        self::assertStringContainsString('application/rss+xml', (string) $headers['Content-Type']);

        $halted = [];
        $headers->exchangeArray([]);
        $this->controller($app)->atom();
        self::assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom">', $halted[0]['body']);
        self::assertStringContainsString('application/atom+xml', (string) $headers['Content-Type']);
    }

    private function controller(Engine $app): BlogPublicController
    {
        $test = $this;

        return new class($app, $test) extends BlogPublicController {
            public function __construct(Engine $app, private BlogPublicControllerTest $t)
            {
                parent::__construct($app);
            }

            public function render(string $template, array $data = []): void
            {
                $this->t->renders[] = ['template' => $template, 'data' => $data];
            }
        };
    }

    /** @param array<string, mixed> $query */
    private function engine(array $query = []): Engine
    {
        $test = $this;
        $blog = $this->blog;
        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
            'request' => static fn(): object => new class($query) {
                public Collection $data;
                public Collection $query;
                /** @param array<string, mixed> $q */
                public function __construct(array $q)
                {
                    $this->data = new Collection([]);
                    $this->query = new Collection($q);
                }
            },
            'db' => fn(): PDO => $this->pdo,
            'blog' => static fn(): BlogService => $blog,
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/blog';
                }
            },
            'settings' => static fn(): object => new class($test) {
                public function __construct(private BlogPublicControllerTest $t)
                {
                }

                public function get(string $k, mixed $d = null): mixed
                {
                    return $this->t->settingsRows[$k] ?? $d;
                }
            },
            'profiles' => static fn(): object => new class($test) {
                public function __construct(private BlogPublicControllerTest $t)
                {
                }

                public function findByUserId(int $id): ?object
                {
                    return $this->t->profileResult;
                }
            },
            'regions' => static fn(): object => new class($test) {
                public function __construct(private BlogPublicControllerTest $t)
                {
                }

                public function setContext(array $context): void
                {
                    $this->t->regionsContexts[] = $context;
                }
            },
            'adext' => static fn(): object => new class {
                /** @return array<string, mixed> */
                public function get(string $t, string $s, array $c = []): array
                {
                    return [];
                }
            },
            'view' => static fn(): object => new class {
                public function fetch(string $v, ?array $d = null): string
                {
                    return 'C:' . $v;
                }
            },
        ]);
        $app->set('CMS.siteName', 'Test Blog');
        $app->set('CMS.siteUrl', 'https://example.org');
        $app->set('flight.base_url', '/');
        $app->map('halt', function (int $code, string $msg) use ($test): void {
            $test->halts[] = ['code' => $code, 'msg' => $msg];
        });
        \Flight::setEngine($app);

        return $app;
    }
}
