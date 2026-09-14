<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Blog\Controllers\BlogAdminController;
use Pubvana\Plugins\Blog\Services\BlogService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * BlogAdminController coverage over the real BlogService.
 */
#[CoversClass(BlogAdminController::class)]
final class BlogAdminControllerTest extends TestCase
{
    private PDO $pdo;
    private BlogService $blog;

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
        BlogSchema::create($this->pdo);
        $this->blog = new BlogService($this->pdo, ['route_prefix' => '/blog']);
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];

        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
        ]);
        \Flight::setEngine($app);
    }

    public function testIndexFiltersStatus(): void
    {
        $this->blog->createPost(['title' => 'P', 'slug' => 'p', 'status' => 'published'], 1);
        $this->blog->createPost(['title' => 'D', 'slug' => 'd', 'status' => 'draft'], 1);

        (new BlogAdminController($this->engine()))->index();
        self::assertSame(2, $this->fetches[0]['data']['total']);
        self::assertNull($this->fetches[0]['data']['status']);

        $this->fetches = [];
        (new BlogAdminController($this->engine(query: ['status' => 'bogus'])))->index();
        self::assertNull($this->fetches[0]['data']['status']);

        $this->fetches = [];
        (new BlogAdminController($this->engine(query: ['status' => 'draft', 'page' => '0'])))->index();
        self::assertSame('draft', $this->fetches[0]['data']['status']);
        self::assertSame(1, $this->fetches[0]['data']['page']);
        self::assertSame(1, $this->fetches[0]['data']['total']);
        self::assertSame('/admin/blog', $this->fetches[0]['data']['adminBase']);
    }

    public function testCreateRendersForm(): void
    {
        (new BlogAdminController($this->engine()))->create();

        self::assertSame('pubvana/blog/admin/create', $this->fetches[0]['view']);
        self::assertSame('New Post', $this->fetches[0]['data']['pageTitle']);
        self::assertSame('JODIT', $this->fetches[0]['data']['joditHtml']);
    }

    public function testStoreRejectsInvalidStatus(): void
    {
        (new BlogAdminController($this->engine(data: ['status' => 'bogus'])))->store();

        self::assertSame('Invalid status.', $this->flashes['error'][0]);
        self::assertSame(['/admin/blog/create'], $this->redirects);
    }

    public function testStoreCreatesPublishedPost(): void
    {
        $app = $this->engine(data: [
            'title' => 'Hello World',
            'status' => 'published',
            'content' => 'body',
            'is_featured' => '1',
            'categories' => [],
            'tags_raw' => 'php',
        ]);
        (new BlogAdminController($app))->store();

        self::assertSame('Post created.', $this->flashes['success'][0]);
        self::assertSame(['/admin/blog/1/edit'], $this->redirects);
        $post = $this->blog->findPost(1);
        self::assertNotNull($post);
        self::assertSame('hello-world', $post->slug);
        self::assertNotEmpty($post->published_at);
        self::assertSame(['php'], $this->blog->getPostTagNames(1));
    }

    public function testStoreCollidingSlugGetsSuffix(): void
    {
        $this->blog->createPost(['title' => 'Hello', 'slug' => 'hello', 'status' => 'draft'], 1);
        $app = $this->engine(data: ['title' => 'Hello', 'slug' => 'hello', 'status' => 'draft']);
        (new BlogAdminController($app))->store();

        $post = $this->blog->findPost(2);
        self::assertNotNull($post);
        self::assertStringStartsWith('hello-', $post->slug);
    }

    public function testStoreScheduledKeepsPostedDate(): void
    {
        $app = $this->engine(data: [
            'title' => 'Soon', 'status' => 'scheduled', 'published_at' => '2030-01-01 10:00:00',
        ]);
        (new BlogAdminController($app))->store();

        $post = $this->blog->findPost(1);
        self::assertNotNull($post);
        self::assertSame('2030-01-01 10:00:00', $post->published_at);
    }

    public function testEditMissAndHit(): void
    {
        (new BlogAdminController($this->engine()))->edit('99');
        self::assertSame(['/admin/blog'], $this->redirects);

        $this->blog->createPost(['title' => 'E', 'slug' => 'e', 'status' => 'draft'], 1);
        (new BlogAdminController($this->engine()))->edit('1');
        self::assertSame('pubvana/blog/admin/edit', $this->fetches[0]['view']);
        self::assertSame('E', $this->fetches[0]['data']['post']->title);
        self::assertSame('/blog', $this->fetches[0]['data']['previewBase']);
    }

    public function testUpdateRejectsInvalidStatus(): void
    {
        $this->blog->createPost(['title' => 'E', 'slug' => 'e', 'status' => 'draft'], 1);
        (new BlogAdminController($this->engine(data: ['status' => 'bogus'])))->update('1');

        self::assertSame(['/admin/blog/1/edit'], $this->redirects);
        self::assertSame('Invalid status.', $this->flashes['error'][0]);
    }

    public function testUpdateStampsFirstPublishKeepsRepublish(): void
    {
        $this->blog->createPost(['title' => 'E', 'slug' => 'e', 'status' => 'draft'], 1);

        (new BlogAdminController($this->engine(data: ['title' => 'E', 'status' => 'published'])))->update('1');
        $post = $this->blog->findPost(1);
        self::assertNotNull($post);
        $firstStamp = $post->published_at;
        self::assertNotEmpty($firstStamp);

        sleep(1);
        $this->redirects = [];
        $this->flashes = [];
        (new BlogAdminController($this->engine(data: ['title' => 'E2', 'status' => 'published'])))->update('1');
        $post2 = $this->blog->findPost(1);
        self::assertNotNull($post2);
        self::assertSame($firstStamp, $post2->published_at);
        self::assertSame(['/admin/blog/1/edit'], $this->redirects);
        self::assertSame('Post updated.', $this->flashes['success'][0]);
    }

    public function testDeleteRevisionsRestore(): void
    {
        $this->blog->createPost(['title' => 'X', 'slug' => 'x', 'status' => 'draft'], 1);

        (new BlogAdminController($this->engine()))->delete('1');
        self::assertSame('Post deleted.', $this->flashes['success'][0]);
        self::assertNull($this->blog->findPost(1));

        $post = $this->blog->createPost(['title' => 'Y', 'slug' => 'y', 'status' => 'draft'], 1);
        $id = (int) $post->id;

        $this->redirects = [];
        (new BlogAdminController($this->engine()))->revisions('99999');
        self::assertSame(['/admin/blog'], $this->redirects);

        $this->fetches = [];
        (new BlogAdminController($this->engine()))->revisions((string) $id);
        self::assertSame('pubvana/blog/admin/revisions', $this->fetches[0]['view']);

        $revs = $this->blog->getRevisions($id);
        $this->redirects = [];
        $this->flashes = [];
        (new BlogAdminController($this->engine()))->restore((string) $id, (string) $revs[0]->id);
        self::assertSame('Revision restored.', $this->flashes['success'][0]);
        self::assertSame(["/admin/blog/{$id}/edit"], $this->redirects);
    }

    public function testCategoryFlow(): void
    {
        (new BlogAdminController($this->engine()))->categories();
        self::assertSame('pubvana/blog/admin/categories', $this->fetches[0]['view']);

        $this->fetches = [];
        (new BlogAdminController($this->engine()))->createCategory();
        self::assertSame('pubvana/blog/admin/category-form', $this->fetches[0]['view']);
        self::assertNull($this->fetches[0]['data']['category']);

        $this->redirects = [];
        $this->flashes = [];
        (new BlogAdminController($this->engine(data: ['name' => 'News'])))->storeCategory();
        self::assertSame('Category created.', $this->flashes['success'][0]);
        self::assertSame(['/admin/blog/categories'], $this->redirects);

        $this->redirects = [];
        $this->flashes = [];
        (new BlogAdminController($this->engine(data: ['name' => 'News'])))->storeCategory();
        self::assertSame('A category with that slug already exists.', $this->flashes['error'][0]);

        $this->redirects = [];
        (new BlogAdminController($this->engine()))->editCategory('99');
        self::assertSame(['/admin/blog/categories'], $this->redirects);

        $this->fetches = [];
        (new BlogAdminController($this->engine()))->editCategory('1');
        self::assertSame('Edit Category', $this->fetches[0]['data']['pageTitle']);

        $this->redirects = [];
        $this->flashes = [];
        $this->blog->createCategory(['name' => 'Other', 'slug' => 'other']);
        (new BlogAdminController($this->engine(data: ['name' => 'Other'])))->updateCategory('1');
        self::assertSame('A category with that slug already exists.', $this->flashes['error'][0]);

        $this->redirects = [];
        $this->flashes = [];
        (new BlogAdminController($this->engine(data: ['name' => 'Updates'])))->updateCategory('1');
        self::assertSame('Category updated.', $this->flashes['success'][0]);

        $this->redirects = [];
        $this->flashes = [];
        (new BlogAdminController($this->engine()))->deleteCategory('1');
        self::assertSame('Category deleted.', $this->flashes['success'][0]);
        self::assertNull($this->blog->findCategory(1));
    }

    public function testTagFlow(): void
    {
        (new BlogAdminController($this->engine()))->tags();
        self::assertSame('pubvana/blog/admin/tags', $this->fetches[0]['view']);

        $post = $this->blog->createPost(['title' => 'T', 'slug' => 't', 'status' => 'draft'], 1);
        $this->blog->syncPostTags((int) $post->id, 'php');
        $tag = $this->blog->findTagBySlug('php');
        self::assertNotNull($tag);

        (new BlogAdminController($this->engine()))->deleteTag((string) $tag->id);
        self::assertSame('Tag deleted.', $this->flashes['success'][0]);
        self::assertSame(['/admin/blog/tags'], $this->redirects);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $query
     */
    private function engine(array $data = [], array $query = []): Engine
    {
        $test = $this;
        $blog = $this->blog;
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
            'blog' => static fn(): BlogService => $blog,
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
                    return '/blog';
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private BlogAdminControllerTest $t)
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
                    public function __construct(private BlogAdminControllerTest $t)
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
