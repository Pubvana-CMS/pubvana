<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Pages\Controllers\PagesPublicController;
use Pubvana\Plugins\Pages\Services\PagesService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PagesPublicController coverage.
 *
 * render() is captured through a public override (widened visibility),
 * so tests assert template choice + view data without the Vision pipeline.
 */
#[CoversClass(PagesPublicController::class)]
final class PagesPublicControllerTest extends TestCase
{
    private PDO $pdo;
    private PagesService $pages;

    /** @var array<string, mixed> */
    public array $renders = [];
    /** @var list<array{code: int, msg: string}> */
    public array $halts = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, mixed> */
    public array $settingsRows = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        PagesSchema::create($this->pdo);
        $this->pages = new PagesService($this->pdo, ['route_prefix' => '/page', 'max_revisions' => 15]);
        $this->renders = [];
        $this->halts = [];
        $this->redirects = [];
        $this->settingsRows = [];
    }

    public function testIndexRedirectsHome(): void
    {
        $this->controller($this->engine())->index();

        self::assertSame(['/'], $this->redirects);
    }

    public function testViewRendersPublishedPage(): void
    {
        $page = $this->pages->createPage([
            'title' => 'About',
            'content' => '<p>Hi</p>',
            'status' => 'published',
            'allow_comments' => '1',
        ], 4);

        $this->controller($this->engine())->view('about');

        self::assertSame('pubvana/pages/page', $this->renders[0]['template']);
        $data = $this->renders[0]['data'];
        self::assertSame('About', $data['title']);
        self::assertSame('<p>Hi</p>', $data['content']);
        self::assertNull($data['featured_image']);
        self::assertFalse($data['ai_disclosure']);
        self::assertSame(['type' => 'page', 'id' => (int) $page->id], $data['commentable']);
        self::assertTrue($data['allow_comments']);
        self::assertFalse($data['is_homepage']);
        self::assertSame('page', $data['seo_context']['content_type']);
        self::assertSame((int) $page->id, $data['seo_context']['content_id']);
        self::assertSame('website', $data['seo_context']['og_type']);
    }

    public function testViewHomepageFlag(): void
    {
        $this->pages->createPage(['title' => 'Home', 'status' => 'published'], 1);

        $this->controller($this->engine())->view('home', true);
        self::assertTrue($this->renders[0]['data']['is_homepage']);
    }

    public function testViewHaltsOnMissingOrDraft(): void
    {
        $this->controller($this->engine())->view('nope');
        self::assertSame([['code' => 404, 'msg' => 'Page not found']], $this->halts);

        $this->halts = [];
        $this->pages->createPage(['title' => 'Draft', 'status' => 'draft'], 1);
        $this->controller($this->engine())->view('draft');
        self::assertSame([['code' => 404, 'msg' => 'Page not found']], $this->halts);
    }

    public function testViewAiDisclosure(): void
    {
        $this->pages->createPage(['title' => 'AI', 'status' => 'published', 'ai_generated' => '1'], 1);

        $this->settingsRows = ['Seo.ai_disclosure_enabled' => true];
        $this->controller($this->engine())->view('ai');
        self::assertTrue($this->renders[0]['data']['ai_disclosure']);
        self::assertTrue($this->renders[0]['data']['seo_context']['ai_generated']);

        $this->renders = [];
        $this->halts = [];
        $this->settingsRows = ['Seo.ai_disclosure_enabled' => false];
        $this->controller($this->engine())->view('ai');
        self::assertFalse($this->renders[0]['data']['ai_disclosure']);
    }

    private function controller(Engine $app): PagesPublicController
    {
        $test = $this;

        return new class($app, $test) extends PagesPublicController {
            public function __construct(Engine $app, private PagesPublicControllerTest $t)
            {
                parent::__construct($app);
            }

            public function render(string $template, array $data = []): void
            {
                $this->t->renders[] = ['template' => $template, 'data' => $data];
            }
        };
    }

    private function engine(): Engine
    {
        $test = $this;
        $pages = $this->pages;
        $app = $this->app([
            'slugify' => static fn(string $text): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-')),
            'request' => static fn(): object => new class {
                public Collection $data;
                public Collection $query;

                public function __construct()
                {
                    $this->data = new Collection([]);
                    $this->query = new Collection([]);
                }
            },
            'db' => fn(): PDO => $this->pdo,
            'pages' => static fn(): PagesService => $pages,
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/page';
                }
            },
            'settings' => static fn(): object => new class($test) {
                public function __construct(private PagesPublicControllerTest $t)
                {
                }

                public function get(string $k, mixed $d = null): mixed
                {
                    return $this->t->settingsRows[$k] ?? $d;
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
        $app->set('CMS.siteName', 'Test Site');
        $app->set('CMS.siteUrl', 'https://example.org');
        $app->set('flight.base_url', '/');
        $app->map('halt', function (int $code, string $msg) use ($test): void {
            $test->halts[] = ['code' => $code, 'msg' => $msg];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}
