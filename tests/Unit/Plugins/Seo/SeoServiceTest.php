<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Services\SchemaService;
use Pubvana\Plugins\Seo\Services\SeoService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * SeoService head builders, meta CRUD, detection; SchemaService graphs.
 */
#[CoversClass(SeoService::class)]
#[CoversClass(SchemaService::class)]
final class SeoServiceTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    private array $settingsData = [];

    /** @var array<string, mixed> */
    private array $stubs = [];

    private string $requestUrl = '/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->pdo->exec(
            'CREATE TABLE seo_meta (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                content_type     TEXT NOT NULL,
                content_id       INTEGER NOT NULL,
                meta_title       TEXT,
                meta_description TEXT,
                canonical_url    TEXT,
                robots_directive TEXT,
                focus_keywords   TEXT,
                og_title         TEXT,
                og_description   TEXT,
                og_image         TEXT,
                og_type          TEXT,
                twitter_card     TEXT,
                schema_type      TEXT,
                seo_score        INTEGER,
                hreflang         TEXT,
                created_at       TEXT,
                updated_at       TEXT
            )'
        );
        $this->settingsData = [
            'CMS.siteName' => 'Test Site',
            'Seo.title_separator' => '|',
            'Seo.title_template' => '{title} {sep} {site_name}',
            'Seo.default_language' => 'en',
        ];
        $this->stubs = [];
        $this->requestUrl = '/';
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $stubs
     */
    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $stubs
     * @return \flight\Engine<object>
     */
    private function makeEngine(array $settings = [], array $stubs = []): \flight\Engine
    {
        $test = $this;
        $mergedSettings = array_merge($this->settingsData, $settings);
        $mergedStubs = array_merge($this->stubs, $stubs);
        $url = $this->requestUrl;

        $app = $this->app([
            'settings' => static fn (): object => new class ($mergedSettings) {
                /** @param array<string, mixed> $data */
                public function __construct(private array $data)
                {
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->data[$key] ?? $default;
                }

                public function set(string $key, mixed $value): void
                {
                }
            },
            'url' => static fn (): object => new class {
                public function siteOrigin(): string
                {
                    return 'https://example.com';
                }
            },
            'request' => static fn (): object => new class ($url) {
                public string $url;

                public function __construct(string $url)
                {
                    $this->url = $url;
                }
            },
            'blog' => static fn (): object => new class ($mergedStubs) {
                /** @param array<string, mixed> $stubs */
                public function __construct(private array $stubs)
                {
                }

                public function findPostBySlug(string $slug): ?object
                {
                    return $this->stubs['post'] ?? null;
                }
            },
            'pages' => static fn (): object => new class ($mergedStubs) {
                /** @param array<string, mixed> $stubs */
                public function __construct(private array $stubs)
                {
                }

                public function findPageBySlug(string $slug): ?object
                {
                    return $this->stubs['page'] ?? null;
                }
            },
            'seoSchema' => static fn (): SchemaService => new SchemaService($test->schemaApp()),
        ]);

        return $app;
    }

    /** @return \flight\Engine<object> */
    private function schemaApp(): \flight\Engine
    {
        return $this->app([
            'settings' => static fn (): object => new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return match ($key) {
                        'CMS.siteName' => 'Test Site',
                        'Seo.organization_name' => '',
                        default => $default,
                    };
                }
            },
            'url' => static fn (): object => new class {
                public function siteOrigin(): string
                {
                    return 'https://example.com';
                }
            },
            'request' => static fn (): object => new class {
                public string $url = '/';
            },
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $stubs
     */
    private function service(array $settings = [], array $stubs = []): SeoService
    {
        return new SeoService($this->pdo, $this->makeEngine($settings, $stubs));
    }

    public function testContextAccessors(): void
    {
        $service = $this->service();
        $service->setContext(['content_type' => 'home']);

        self::assertSame(['content_type' => 'home'], $service->getContext());
    }

    public function testSaveAndGetMeta(): void
    {
        $service = $this->service();

        self::assertNull($service->getMeta('post', 1));

        $meta = $service->saveMeta('post', 1, [
            'meta_title' => 'T',
            'meta_description' => 'D',
            'focus_keywords' => ['seo', 'tips'],
            'bogus_field' => 'ignored',
        ]);
        self::assertSame('T', (string) $meta->meta_title);
        self::assertSame(['seo', 'tips'], $meta->getFocusKeywordsArray());

        $found = $service->getMeta('post', 1);
        self::assertInstanceOf(\Pubvana\Plugins\Seo\Models\SeoMeta::class, $found);
        self::assertSame('T', (string) $found->meta_title);

        // CSV keywords on update.
        $updated = $service->saveMeta('post', 1, ['focus_keywords' => 'a, b, c', 'meta_title' => '']);
        self::assertSame(['a', 'b', 'c'], $updated->getFocusKeywordsArray());
        self::assertNull($updated->meta_title);
    }

    public function testBuildTitle(): void
    {
        $service = $this->service();
        $service->setContext(['content_type' => 'home', 'title' => 'Hello']);

        self::assertSame('Hello | Test Site', $service->buildTitle());

        $service->saveMeta('post', 10, ['meta_title' => 'Override']);
        $service->setContext(['content_type' => 'post', 'content_id' => 10, 'title' => 'Hello']);
        self::assertSame('Override', $service->buildTitle());
    }

    public function testBuildDescriptionTruncates(): void
    {
        $service = $this->service();
        $service->setContext(['description' => str_repeat('d', 200)]);

        $desc = $service->buildDescription();
        self::assertSame(160, mb_strlen($desc));
        self::assertStringEndsWith('...', $desc);

        $service->saveMeta('post', 11, ['meta_description' => 'Meta wins']);
        $service->setContext(['content_type' => 'post', 'content_id' => 11, 'description' => 'ctx']);
        self::assertSame('Meta wins', $service->buildDescription());
    }

    public function testBuildCanonicalRobotsHreflang(): void
    {
        $service = $this->service();
        $service->setContext(['url' => 'https://example.com/blog/hi']);

        self::assertSame('https://example.com/blog/hi', $service->buildCanonical());
        self::assertNull($service->buildRobots());
        self::assertSame('en', $service->buildHreflang());

        $service->saveMeta('post', 12, [
            'canonical_url' => 'https://example.com/canonical',
            'robots_directive' => 'noindex, follow',
            'hreflang' => 'fr',
        ]);
        $service->setContext(['content_type' => 'post', 'content_id' => 12, 'url' => 'https://example.com/x']);
        self::assertSame('https://example.com/canonical', $service->buildCanonical());
        self::assertSame('noindex, follow', $service->buildRobots());
        self::assertSame('fr', $service->buildHreflang());
    }

    public function testBuildOpenGraphAndTwitter(): void
    {
        $service = $this->service();
        $service->setContext([
            'content_type' => 'post',
            'title' => 'Hello',
            'og_type' => 'article',
        ]);

        $og = $service->buildOpenGraph();
        self::assertSame('article', $og['og:type']);
        self::assertSame('Hello | Test Site', $og['og:title']);
        self::assertArrayNotHasKey('og:image', $og);

        $service->setContext([
            'content_type' => 'post',
            'title' => 'Hello',
            'image' => 'uploads/x.png',
            'og_type' => 'article',
        ]);
        $og = $service->buildOpenGraph();
        self::assertSame('https://example.com/uploads/x.png', $og['og:image']);

        $tw = $service->buildTwitterCard();
        self::assertSame('summary_large_image', $tw['twitter:card']);
        self::assertSame('https://example.com/uploads/x.png', $tw['twitter:image']);
    }

    public function testRenderHeadEscapesAndOmitsTitle(): void
    {
        $service = $this->service();
        $service->setContext([
            'content_type' => 'home',
            'title' => 'Hi <b>',
            'description' => 'Desc "quoted"',
            'url' => 'https://example.com/',
        ]);
        $service->addMeta('author', 'Ada & Co');
        $service->addTag('<link rel="x" href="/y">');

        $head = $service->renderHead();

        self::assertStringNotContainsString('<title>', $head);
        self::assertStringContainsString('Desc &quot;quoted&quot;', $head);
        self::assertStringContainsString('Ada &amp; Co', $head);
        self::assertStringContainsString('<link rel="x" href="/y">', $head);
        self::assertStringContainsString('og:title', $head);
        self::assertStringContainsString('twitter:card', $head);
    }

    public function testResolveImageUrl(): void
    {
        $service = $this->service();

        self::assertSame('', $service->resolveImageUrl(null));
        self::assertSame('', $service->resolveImageUrl(''));
        self::assertSame('https://cdn.test/x.png', $service->resolveImageUrl('https://cdn.test/x.png'));
        self::assertSame('https://example.com/uploads/x.png', $service->resolveImageUrl('uploads/x.png'));
        self::assertSame('https://example.com/uploads/x.png', $service->resolveImageUrl('/uploads/x.png'));
    }

    public function testDetectContentBranches(): void
    {
        $post = (object) [
            'id' => 5, 'title' => 'Hello', 'status' => 'published', 'author_id' => 0,
            'excerpt' => 'Ex', 'ai_generated' => 0, 'published_at' => '2026-01-01',
            'updated_at' => '2026-01-02', 'featured_image' => '',
        ];

        $this->requestUrl = '/';
        $service = $this->service([], ['post' => $post]);
        $service->detectContent();
        self::assertSame('home', $service->getContext()['content_type']);

        $this->requestUrl = '/blog/hello';
        $service = $this->service([], ['post' => $post]);
        $service->detectContent();
        self::assertSame('post', $service->getContext()['content_type']);
        self::assertSame(5, $service->getContext()['content_id']);

        $this->requestUrl = '/blog/category/news';
        $service = $this->service();
        $service->detectContent();
        self::assertSame('archive', $service->getContext()['content_type']);

        $this->requestUrl = '/blog/tag/php';
        $service = $this->service();
        $service->detectContent();
        self::assertSame('archive', $service->getContext()['content_type']);

        $this->requestUrl = '/blog';
        $service = $this->service();
        $service->detectContent();
        self::assertSame('archive', $service->getContext()['content_type']);

        $page = (object) ['id' => 3, 'title' => 'About', 'status' => 'published', 'ai_generated' => 0, 'updated_at' => '2026-01-02'];
        $this->requestUrl = '/page/about';
        $service = $this->service([], ['page' => $page]);
        $service->detectContent();
        self::assertSame('page', $service->getContext()['content_type']);

        $this->requestUrl = '/unknown/thing';
        $service = $this->service();
        $service->detectContent();
        self::assertSame('unknown', $service->getContext()['content_type']);
    }

    public function testSchemaHomePostPageAndBreadcrumbs(): void
    {
        $schema = new SchemaService($this->schemaApp());

        $home = $schema->render(['content_type' => 'home', 'url' => 'https://example.com/']);
        self::assertStringContainsString('WebSite', $home);
        self::assertStringContainsString('Organization', $home);

        $post = $schema->render([
            'content_type' => 'post',
            'title' => 'Hello',
            'url' => 'https://example.com/blog/hello',
            'author' => ['name' => 'Ada', 'url' => 'https://example.com/profile/ada'],
            'breadcrumbs' => [['label' => 'Home', 'url' => 'https://example.com/']],
        ]);
        self::assertStringContainsString('BlogPosting', $post);
        self::assertStringContainsString('Person', $post);
        self::assertStringContainsString('BreadcrumbList', $post);

        $page = $schema->render([
            'content_type' => 'page',
            'title' => 'About',
            'url' => 'https://example.com/page/about',
        ]);
        self::assertStringContainsString('WebPage', $page);

        self::assertSame('', $schema->render(['content_type' => 'unknown', 'url' => 'https://example.com/x']));
    }

    public function testSchemaAiDisclosureToggle(): void
    {
        $app = $this->schemaApp();
        $schema = new SchemaService($app);

        $ctx = [
            'content_type' => 'post',
            'title' => 'AI post',
            'url' => 'https://example.com/blog/ai',
            'ai_generated' => true,
        ];
        self::assertStringContainsString('digitalSourceType', $schema->render($ctx));

        $off = $this->app([
            'settings' => static fn (): object => new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return match ($key) {
                        'CMS.siteName' => 'Test Site',
                        'Seo.ai_disclosure_enabled' => false,
                        default => $default,
                    };
                }
            },
            'url' => static fn (): object => new class {
                public function siteOrigin(): string
                {
                    return 'https://example.com';
                }
            },
            'request' => static fn (): object => new class {
                public string $url = '/';
            },
        ]);
        self::assertStringNotContainsString('digitalSourceType', (new SchemaService($off))->render($ctx));
    }
}
