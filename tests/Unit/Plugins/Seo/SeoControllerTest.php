<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Controllers\SeoAdminController;
use Pubvana\Plugins\Seo\Controllers\SeoPublicController;
use Pubvana\Tests\Support\TestCase;

/**
 * SEO admin and public controllers with stub engines.
 */
#[CoversClass(SeoAdminController::class)]
#[CoversClass(SeoPublicController::class)]
final class SeoControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $savedSettings = [];

    /** @var array<string, string> */
    public array $flashes = [];

    public ?string $redirectTarget = null;

    /** @var list<array{data: mixed, code: int}> */
    public array $jsons = [];

    /** @var list<array{code: int, body: string}> */
    public array $halts = [];

    /** @var array<string, string> */
    public array $headers = [];

    /** @var array<string, mixed> */
    public array $renders = [];

    /** @var array<string, mixed> */
    private array $settingsData = [];

    /** @var array<string, mixed> */
    public array $metaStore = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedSettings = [];
        $this->flashes = [];
        $this->redirectTarget = null;
        $this->jsons = [];
        $this->halts = [];
        $this->headers = [];
        $this->renders = [];
        $this->settingsData = [];
        $this->metaStore = [];
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $data
     * @param array<string, mixed> $settings
     * @return \flight\Engine<object>
     */
    private function adminApp(array $query = [], array $data = [], array $settings = []): \flight\Engine
    {
        $test = $this;
        $merged = array_merge($this->settingsData, $settings);
        $app = $this->app([
            'request' => static fn (): object => new class ($query, $data) {
                public Collection $query;
                public Collection $data;

                /**
                 * @param array<string, mixed> $query
                 * @param array<string, mixed> $data
                 */
                public function __construct(array $query, array $data)
                {
                    $this->query = new Collection($query);
                    $this->data = new Collection($data);
                }
            },
            'settings' => static fn (): object => new class ($test, $merged) {
                /** @param array<string, mixed> $data */
                public function __construct(private SeoControllerTest $test, private array $data)
                {
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->data[$key] ?? $default;
                }

                public function set(string $key, mixed $value): void
                {
                    $this->test->savedSettings[$key] = $value;
                }
            },
            'seoRobots' => static fn (): object => new class {
                /** @return array<string, array<string, string>> */
                public function getAiCrawlerList(): array
                {
                    return ['GPTBot' => ['bot' => 'GPTBot', 'current' => 'block']];
                }
            },
            'media' => static fn (): object => new class {
                public function picker(string $name, string $value): string
                {
                    return 'picker:' . $name . ':' . $value;
                }
            },
            'session' => static fn (): object => new class ($test) {
                public function __construct(private SeoControllerTest $test)
                {
                }

                public function flash(string $key, string $message): void
                {
                    $this->test->flashes[$key] = $message;
                }
            },
            'seo' => static fn (): object => new class ($test) {
                public function __construct(private SeoControllerTest $test)
                {
                }

                /** @param array<string, mixed> $data */
                public function saveMeta(string $type, int $id, array $data): object
                {
                    $meta = (object) ['id' => 7, 'seo_score' => 88];
                    $this->test->metaStore = ['type' => $type, 'id' => $id, 'data' => $data];

                    return $meta;
                }
            },
            'seoAnalysis' => static fn (): object => new class {
                /**
                 * @param array<string, mixed> $data
                 * @return array<string, mixed>
                 */
                public function analyze(array $data): array
                {
                    return ['score' => 90, 'echo' => $data['title'] ?? ''];
                }
            },
        ]);

        $app->map('redirect', function (string $target) use ($test): void {
            $test->redirectTarget = $target;
        });
        $app->map('json', function (mixed $data, int $code = 200) use ($test): void {
            $test->jsons[] = ['data' => $data, 'code' => $code];
        });
        $app->map('render', function (string $view, array $data) use ($test): void {
            $test->renders = ['view' => $view, 'data' => $data];
        });

        return $app;
    }

    /**
     * @param array<string, mixed> $settings
     */
    /**
     * @param array<string, mixed> $settings
     * @return \flight\Engine<object>
     */
    private function publicApp(array $settings = []): \flight\Engine
    {
        $test = $this;
        $app = $this->app([
            'settings' => static fn (): object => new class ($settings) {
                /** @param array<string, mixed> $data */
                public function __construct(private array $data)
                {
                }

                public function get(string $key, mixed $default = null): mixed
                {
                    return $this->data[$key] ?? $default;
                }
            },
            'seoSitemap' => static fn (): object => new class {
                public function generate(): string
                {
                    return '<urlset/>';
                }
            },
            'seoRobots' => static fn (): object => new class {
                public function generate(): string
                {
                    return "User-agent: *\n";
                }
            },
            'seoLlmsTxt' => static fn (): object => new class {
                public function generate(): string
                {
                    return "# Site\n";
                }
            },
        ]);

        $app->map('halt', function (int $code, string $body = '') use ($test): void {
            $test->halts[] = ['code' => $code, 'body' => $body];
        });

        return $app;
    }

    public function testSettingsRenders(): void
    {
        $controller = new class ($this->adminApp()) extends SeoAdminController {
            /** @param array<string, mixed> $data */
            protected function render(string $view, array $data = [], bool $layout = true): void
            {
                $this->app->render($view, $data);
            }
        };

        $controller->settings();

        self::assertSame('pubvana/seo/admin/settings', $this->renders['view']);
        self::assertSame('SEO Settings', $this->renders['data']['pageTitle']);
        self::assertArrayHasKey('aiCrawlers', $this->renders['data']);
    }

    public function testSaveSettings(): void
    {
        $controller = new SeoAdminController($this->adminApp([], [
            '_csrf_token' => 'tok',
            'title_separator' => '-',
            'sitemap_enabled' => '1',
            'social_profiles' => "https://a.test\nhttps://b.test\n",
            'ai_crawlers' => ['GPTBot' => 'allow', 'Bogus-Bot' => 'block'],
        ]));

        $controller->saveSettings();

        self::assertSame('-', $this->savedSettings['Seo.title_separator']);
        self::assertTrue($this->savedSettings['Seo.sitemap_enabled']);
        self::assertFalse($this->savedSettings['Seo.sitemap_include_pages']);
        self::assertSame(['https://a.test', 'https://b.test'], $this->savedSettings['Seo.social_profiles']);
        self::assertSame('allow', $this->savedSettings['Seo.ai_crawler_gptbot']);
        self::assertSame('block', $this->savedSettings['Seo.ai_crawler_bogus_bot']);
        self::assertArrayNotHasKey('Seo._csrf_token', $this->savedSettings);
        self::assertSame('SEO settings saved.', $this->flashes['success']);
        self::assertSame('/admin/seo', $this->redirectTarget);
    }

    public function testSaveMeta(): void
    {
        $controller = new SeoAdminController($this->adminApp([], [
            '_csrf_token' => 'tok',
            'content_type' => 'post',
            'content_id' => '5',
            'meta_title' => 'Hi',
        ]));

        $controller->saveMeta();

        self::assertSame('post', $this->metaStore['type']);
        self::assertSame(5, $this->metaStore['id']);
        self::assertSame(['meta_title' => 'Hi'], $this->metaStore['data']);
        self::assertNotEmpty($this->jsons);
        self::assertSame(['success' => true, 'id' => 7, 'score' => 88], $this->jsons[0]['data']);
    }

    public function testSaveMetaMissing(): void
    {
        $controller = new SeoAdminController($this->adminApp([], []));

        $controller->saveMeta();

        self::assertNotEmpty($this->jsons);
        self::assertSame(['error' => 'Missing content_type or content_id'], $this->jsons[0]['data']);
        self::assertSame(400, $this->jsons[0]['code']);
    }

    public function testAnalyze(): void
    {
        $controller = new SeoAdminController($this->adminApp(
            ['title' => 'Hello', 'focus_keywords' => 'seo, tips'],
            [],
        ));

        $controller->analyze();

        self::assertNotEmpty($this->jsons);
        self::assertSame(90, $this->jsons[0]['data']['score']);
        self::assertSame('Hello', $this->jsons[0]['data']['echo']);
    }

    public function testSitemapGating(): void
    {
        $controller = new SeoPublicController($this->publicApp(['Seo.sitemap_enabled' => false]));
        $controller->sitemap();
        self::assertSame(404, $this->halts[0]['code']);

        $this->halts = [];
        $controller = new class ($this->publicApp(['Seo.sitemap_enabled' => true])) extends SeoPublicController {
            public function response(): object
            {
                return new class {
                    /** @var array<string, string> */
                    public array $headers = [];

                    public function header(string $name, string $value): void
                    {
                    }
                };
            }
        };
        $controller->sitemap();
        self::assertNotEmpty($this->halts);
        self::assertSame(200, $this->halts[0]['code']);
        self::assertSame('<urlset/>', $this->halts[0]['body']);
    }

    public function testRobotsTxt(): void
    {
        $controller = new class ($this->publicApp()) extends SeoPublicController {
            public function response(): object
            {
                return new class {
                    public function header(string $name, string $value): void
                    {
                    }
                };
            }
        };
        $controller->robotsTxt();
        self::assertNotEmpty($this->halts);
        self::assertSame(200, $this->halts[0]['code']);
        self::assertStringContainsString('User-agent', $this->halts[0]['body']);
    }

    public function testLlmsGating(): void
    {
        $controller = new SeoPublicController($this->publicApp(['Seo.llms_txt_enabled' => false]));
        $controller->llmsTxt();
        self::assertSame(404, $this->halts[0]['code']);

        $this->halts = [];
        $controller = new class ($this->publicApp(['Seo.llms_txt_enabled' => true])) extends SeoPublicController {
            public function response(): object
            {
                return new class {
                    public function header(string $name, string $value): void
                    {
                    }
                };
            }
        };
        $controller->llmsTxt();
        self::assertNotEmpty($this->halts);
        self::assertSame(200, $this->halts[0]['code']);
        self::assertStringContainsString('# Site', $this->halts[0]['body']);
    }
}
