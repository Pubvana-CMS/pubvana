<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Services\LlmsTxtService;
use Pubvana\Plugins\Seo\Services\RobotsTxtService;
use Pubvana\Plugins\Seo\Services\SitemapService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Robots, sitemap, and llms.txt generation with stub engines.
 */
#[CoversClass(RobotsTxtService::class)]
#[CoversClass(SitemapService::class)]
#[CoversClass(LlmsTxtService::class)]
final class SeoFetchFileServiceTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    private array $settingsData = [];

    /** @var array<string, mixed> */
    private array $stubs = [];

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
                robots_directive TEXT,
                created_at       TEXT,
                updated_at       TEXT
            )'
        );
        $this->settingsData = [
            'CMS.siteName' => 'Test Site',
            'CMS.siteByline' => 'Just testing',
        ];
        $this->stubs = [
            'pages' => [],
            'posts' => ['items' => []],
            'categories' => [],
            'tags' => [],
        ];
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
            },
            'url' => static fn (): object => new class {
                public function siteOrigin(): string
                {
                    return 'https://example.com';
                }
            },
            'pages' => static fn (): object => new class ($mergedStubs) {
                /** @param array<string, mixed> $stubs */
                public function __construct(private array $stubs)
                {
                }

                /** @return list<object> */
                public function listPublished(): array
                {
                    return $this->stubs['pages'];
                }
            },
            'blog' => static fn (): object => new class ($mergedStubs) {
                /** @param array<string, mixed> $stubs */
                public function __construct(private array $stubs)
                {
                }

                /** @return array{items: list<object>} */
                public function listPosts(int $page, int $perPage, string $status): array
                {
                    return ['items' => $this->stubs['posts']['items'] ?? []];
                }

                /** @return list<object> */
                public function listCategories(): array
                {
                    return $this->stubs['categories'];
                }

                /** @return list<object> */
                public function listTags(): array
                {
                    return $this->stubs['tags'];
                }
            },
        ]);
        \Flight::setEngine($app);

        return $app;
    }

    private function page(int $id, string $slug): object
    {
        return (object) ['id' => $id, 'title' => 'Page ' . $id, 'slug' => $slug, 'updated_at' => '2026-01-02 00:00:00'];
    }

    private function post(int $id, string $slug): object
    {
        return (object) ['id' => $id, 'title' => 'Post ' . $id, 'slug' => $slug, 'updated_at' => '2026-01-03 00:00:00'];
    }

    private function noindex(string $type, int $id): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO seo_meta (content_type, content_id, robots_directive) VALUES (?, ?, 'noindex, follow')"
        );
        self::assertNotFalse($stmt);
        $stmt->execute([$type, $id]);
    }

    public function testRobotsDefaults(): void
    {
        $txt = (new RobotsTxtService($this->makeEngine()))->generate();

        self::assertStringContainsString('User-agent: *', $txt);
        self::assertStringContainsString('Disallow: /admin/', $txt);
        self::assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $txt);
        // Training bots blocked by default.
        self::assertStringContainsString('User-agent: GPTBot', $txt);
        // Retrieval bots allowed by default: no block lines.
        self::assertStringNotContainsString('User-agent: ChatGPT-User', $txt);
    }

    public function testRobotsCustomBodyAndStanceOverride(): void
    {
        $txt = (new RobotsTxtService($this->makeEngine(
            ['Seo.robots_txt_custom' => 'User-agent: *',
                'Seo.ai_crawler_gptbot' => 'allow',
                'Seo.ai_crawler_chatgpt_user' => 'block'],
        )))->generate();

        self::assertStringContainsString('User-agent: *', $txt);
        self::assertStringNotContainsString('User-agent: GPTBot', $txt);
        self::assertStringContainsString('User-agent: ChatGPT-User', $txt);
    }

    public function testCrawlerListAndKeyNormalization(): void
    {
        $service = new RobotsTxtService($this->makeEngine(['Seo.ai_crawler_applebot_extended' => 'allow']));

        $list = $service->getAiCrawlerList();
        self::assertArrayHasKey('GPTBot', $list);
        self::assertSame('block', $list['GPTBot']['default']);
        self::assertSame('allow', $list['Applebot-Extended']['current']);
        self::assertNotSame('', $list['GPTBot']['description']);

        self::assertSame('applebot_extended', $this->invoke($service, 'botToSettingKey', ['Applebot-Extended']));
        self::assertSame('chatgpt_user', $this->invoke($service, 'botToSettingKey', ['ChatGPT-User']));
    }

    public function testSitemapIncludesAndSkipsNoindex(): void
    {
        $app = $this->makeEngine([], [
            'pages' => [$this->page(1, 'about'), $this->page(2, 'hidden')],
            'posts' => ['items' => [$this->post(5, 'hello'), $this->post(6, 'secret')]],
            'categories' => [(object) ['slug' => 'news']],
            'tags' => [(object) ['slug' => 'php']],
        ]);
        $this->noindex('page', 2);
        $this->noindex('post', 6);

        $xml = (new SitemapService($this->pdo, $app))->generate();

        self::assertStringContainsString('<loc>https://example.com/</loc>', $xml);
        self::assertStringContainsString('https://example.com/page/about', $xml);
        self::assertStringNotContainsString('/page/hidden', $xml);
        self::assertStringContainsString('https://example.com/blog/hello', $xml);
        self::assertStringNotContainsString('/blog/secret', $xml);
        self::assertStringContainsString('/blog/category/news', $xml);
        self::assertStringContainsString('/blog/tag/php', $xml);
        self::assertStringContainsString('<lastmod>2026-01-02</lastmod>', $xml);
    }

    public function testSitemapRespectsTogglesAndEscapes(): void
    {
        $app = $this->makeEngine(
            ['Seo.sitemap_include_pages' => false, 'Seo.sitemap_include_posts' => false],
            ['pages' => [$this->page(1, 'a&b')], 'posts' => ['items' => [$this->post(5, 'c<d')]]],
        );

        $xml = (new SitemapService($this->pdo, $app))->generate();

        self::assertStringNotContainsString('/page/', $xml);
        self::assertStringNotContainsString('/blog/hello', $xml);
        self::assertStringContainsString('<loc>https://example.com/</loc>', $xml);
    }

    public function testSitemapEscapesUrls(): void
    {
        $xml = $this->invoke(new SitemapService($this->pdo, $this->app()), 'buildXml', [[
            ['loc' => 'https://example.com/a&b', 'lastmod' => '2026-01-01'],
            ['loc' => 'https://example.com/c', 'lastmod' => ''],
        ]]);

        self::assertStringContainsString('a&amp;b', $xml);
        self::assertSame(1, substr_count($xml, '<lastmod>'));
    }

    public function testSitemapFormatDate(): void
    {
        $service = new SitemapService($this->pdo, $this->app());

        self::assertSame('2026-03-04', $this->invoke($service, 'formatDate', ['2026-03-04 10:00:00']));
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $this->invoke($service, 'formatDate', [null]));
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $this->invoke($service, 'formatDate', ['bogus']));
    }

    public function testLlmsGeneratesSections(): void
    {
        $app = $this->makeEngine([], [
            'pages' => [$this->page(1, 'about')],
            'posts' => ['items' => [$this->post(5, 'hello')]],
            'categories' => [(object) ['name' => 'News', 'slug' => 'news']],
        ]);

        $txt = (new LlmsTxtService($this->pdo, $app))->generate();

        self::assertStringContainsString('# Test Site', $txt);
        self::assertStringContainsString('> Just testing', $txt);
        self::assertStringContainsString('## Pages', $txt);
        self::assertStringContainsString('[Page 1](https://example.com/page/about)', $txt);
        self::assertStringContainsString('## Blog', $txt);
        self::assertStringContainsString('[Post 5](https://example.com/blog/hello)', $txt);
        self::assertStringContainsString('## Topics', $txt);
        self::assertStringContainsString('[News](https://example.com/blog/category/news)', $txt);
    }

    public function testLlmsSkipsNoindexAndRespectsToggles(): void
    {
        $this->noindex('page', 1);
        $app = $this->makeEngine(
            ['Seo.llms_txt_include_pages' => false, 'Seo.llms_txt_include_posts' => true],
            ['pages' => [$this->page(1, 'about')], 'posts' => ['items' => [$this->post(5, 'hello')]]],
        );

        $txt = (new LlmsTxtService($this->pdo, $app))->generate();

        self::assertStringNotContainsString('## Pages', $txt);
        self::assertStringContainsString('## Blog', $txt);
    }

    public function testLlmsCapsAtFifty(): void
    {
        $pages = [];
        for ($i = 1; $i <= 55; $i++) {
            $pages[] = $this->page($i, 'p' . $i);
        }
        $txt = (new LlmsTxtService($this->pdo, $this->makeEngine([], ['pages' => $pages])))->generate();

        self::assertSame(50, substr_count($txt, '/page/p'));
    }
}
