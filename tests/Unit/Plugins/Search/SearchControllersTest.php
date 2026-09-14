<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Search;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Search\Controllers\SearchAdminController;
use Pubvana\Plugins\Search\Controllers\SearchPublicController;
use Pubvana\Plugins\Search\Services\SearchService;
use Pubvana\Tests\Support\TestCase;

/**
 * Search admin + public controllers.
 */
#[CoversClass(SearchAdminController::class)]
#[CoversClass(SearchPublicController::class)]
final class SearchControllersTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var array<string, mixed> */
    public array $settings = [];
    /** @var array<string, array<string, mixed>> */
    public array $providers = [];
    /** @var array<string, string> */
    public array $query = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->settings = [];
        $this->providers = [];
        $this->query = [];
    }

    public function testAdminIndexRendersSourcesAndSettings(): void
    {
        $this->providers = [
            'pages' => ['label' => 'Pages', 'callable' => static fn(): array => []],
        ];
        $this->settings = ['Search.resultsPerPage' => '12', 'Search.minQueryLength' => '2'];

        (new SearchAdminController($this->engine()))->index();

        self::assertSame('pubvana/search/admin/index', $this->fetches[0]['view']);
        self::assertSame('Search', $this->fetches[0]['data']['pageTitle']);
        self::assertTrue($this->fetches[0]['data']['sources']['pages']['enabled']);
        self::assertSame(12, $this->fetches[0]['data']['resultsPerPage']);
        self::assertSame(2, $this->fetches[0]['data']['minQueryLength']);
    }

    public function testAdminSavePersistsSettingsAndToggles(): void
    {
        $this->providers = [
            'pages' => ['label' => 'Pages', 'callable' => static fn(): array => []],
            'blog' => ['label' => 'Blog', 'callable' => static fn(): array => []],
        ];

        // Only pages checked: blog gets disabled.
        $app = $this->engine(data: [
            'results_per_page' => '5',
            'min_query_length' => '4',
            'source_pages' => '1',
            '_csrf_token' => 'tok',
        ]);
        (new SearchAdminController($app))->save();

        self::assertSame('5', $this->settings['Search.resultsPerPage']);
        self::assertSame('4', $this->settings['Search.minQueryLength']);
        self::assertSame(['blog'], json_decode((string) $this->settings['Search.disabledSources'], true));
        self::assertSame('Search settings saved.', $this->flashes['success'][0]);
        self::assertSame(['/admin/search'], $this->redirects);
    }

    public function testAdminSaveClampsToMinimumOne(): void
    {
        $this->providers = [];

        $app = $this->engine(data: ['results_per_page' => '0', 'min_query_length' => '-3']);
        (new SearchAdminController($app))->save();

        self::assertSame('1', $this->settings['Search.resultsPerPage']);
        self::assertSame('1', $this->settings['Search.minQueryLength']);
    }

    public function testPublicSearchEmptyQuery(): void
    {
        $this->query = [];
        $this->controller($this->engine())->search();

        self::assertSame('pubvana/search/search', $this->fetches[0]['template']);
        self::assertSame('', $this->fetches[0]['data']['query']);
        self::assertSame([], $this->fetches[0]['data']['results']);
        self::assertArrayNotHasKey('pagination', $this->fetches[0]['data']);
    }

    public function testPublicSearchWithResults(): void
    {
        $this->providers = [
            'pages' => [
                'label' => 'Pages',
                'callable' => static fn(): array => [
                    ['title' => 'About Us', 'url' => '/page/about', 'excerpt' => 'hello', 'content' => '', 'published_at' => ''],
                ],
            ],
        ];
        $this->query = ['q' => 'about'];

        $this->controller($this->engine())->search();

        self::assertSame('about', $this->fetches[0]['data']['query']);
        self::assertSame(1, $this->fetches[0]['data']['total']);
        self::assertNull($this->fetches[0]['data']['error']);
        self::assertNull($this->fetches[0]['data']['pagination']);
    }

    public function testPublicSearchPaginationLinks(): void
    {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = ['title' => "Post {$i} alpha", 'url' => "/p{$i}", 'excerpt' => '', 'content' => '', 'published_at' => ''];
        }
        $this->providers = ['blog' => ['label' => 'Blog', 'callable' => static fn() => $items]];
        $this->settings = ['Search.resultsPerPage' => '2'];
        $this->query = ['q' => 'alpha', 'page' => '2'];

        $this->controller($this->engine())->search();

        $pagination = $this->fetches[0]['data']['pagination'];
        self::assertSame(2, $pagination['current']);
        self::assertSame(3, $pagination['total']);
        self::assertSame('/search?q=alpha&page=1', $pagination['prev']);
        self::assertSame('/search?q=alpha&page=3', $pagination['next']);
    }

    public function testPublicSearchPageFloor(): void
    {
        $this->providers = [
            'pages' => ['label' => 'Pages', 'callable' => static fn(): array => []],
        ];
        $this->query = ['q' => 'hello world', 'page' => '0'];

        $this->controller($this->engine())->search();
        self::assertSame(0, $this->fetches[0]['data']['total']);
    }

    private function controller(Engine $app): SearchPublicController
    {
        $test = $this;

        return new class($app, $test) extends SearchPublicController {
            public function __construct(Engine $app, private SearchControllersTest $t)
            {
                parent::__construct($app);
            }

            public function render(string $template, array $data = []): void
            {
                $this->t->fetches[] = ['template' => $template, 'data' => $data];
            }
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function engine(array $data = []): Engine
    {
        $test = $this;
        $app = $this->app([
            'request' => static fn(): object => new class($data, $test) {
                public Collection $data;
                public Collection $query;
                /** @param array<string, mixed> $d */
                public function __construct(array $d, SearchControllersTest $t)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection($t->query);
                }
            },
            'search' => function () use ($test, &$app): SearchService {
                return new SearchService($app);
            },
            'adext' => static fn(): object => new class($test) {
                public function __construct(private SearchControllersTest $t)
                {
                }

                /** @return array<string, mixed> */
                public function get(string $t, string $s, array $c = []): array
                {
                    if ($t === 'search' && $s === 'provider') {
                        return $this->t->providers;
                    }

                    return [];
                }
            },
            'settings' => static fn(): object => new class($test) {
                public function __construct(private SearchControllersTest $t)
                {
                }

                public function get(string $k, mixed $d = null): mixed
                {
                    return $this->t->settings[$k] ?? $d;
                }

                public function set(string $k, mixed $v): void
                {
                    $this->t->settings[$k] = $v;
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private SearchControllersTest $t)
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
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private SearchControllersTest $t)
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
        $app->set('CMS.siteName', 'Test Site');
        $app->set('CMS.siteUrl', 'https://example.org');
        $app->set('flight.base_url', '/');
        $app->map('auth', static fn(): object => new class {
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
        });
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        $app->map('halt', function (int $code, string $msg) use ($test): void {
            $test->fetches[] = ['halt' => $code, 'msg' => $msg];
        });
        \Flight::setEngine($app);

        return $app;
    }
}
