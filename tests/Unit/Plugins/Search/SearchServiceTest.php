<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Search;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Search\Services\SearchService;
use Pubvana\Tests\Support\TestCase;

/**
 * SearchService scoring, aggregation, pagination, source toggles.
 */
#[CoversClass(SearchService::class)]
final class SearchServiceTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $settings = [];
    /** @var array<string, array<string, mixed>> */
    public array $providers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = [];
        $this->providers = [];
    }

    public function testSearchRejectsShortQuery(): void
    {
        $service = $this->service();

        $result = $service->search('ab');
        self::assertSame([], $result['items']);
        self::assertSame(0, $result['total']);
        self::assertSame('ab', $result['query']);
        self::assertSame('Please enter at least 3 characters.', $result['error']);
        self::assertSame('', $result['from']);
        self::assertSame(10, $result['per_page']);
    }

    public function testSearchErrorsWithNoEnabledSources(): void
    {
        $service = $this->service();

        $result = $service->search('hello world');
        self::assertSame([], $result['items']);
        self::assertStringContainsString('No search sources', (string) $result['error']);
    }

    public function testSearchAggregatesAndSortsByScore(): void
    {
        $this->providers = [
            'pages' => [
                'label' => 'Pages',
                'callable' => static fn(string $term): array => [
                    ['title' => 'About Us', 'url' => '/page/about', 'excerpt' => 'hello there', 'content' => 'body', 'published_at' => '2026-01-01 00:00:00'],
                    ['title' => 'Unrelated', 'url' => '/page/other', 'excerpt' => 'nothing', 'content' => 'nothing here', 'published_at' => '2026-01-01 00:00:00'],
                ],
            ],
        ];
        $service = $this->service();

        $result = $service->search('about');
        self::assertSame(2, $result['total']);
        self::assertNull($result['error']);
        // Title match outranks the zero-score item.
        self::assertSame('About Us', strip_tags((string) $result['items'][0]['title']));
        self::assertSame('pages', $result['from']);
    }

    public function testSearchSkipsBadProvidersAndItems(): void
    {
        $this->providers = [
            'broken' => ['label' => 'Broken'],
            'thrower' => ['label' => 'Thrower', 'callable' => static function (): array {
                throw new \RuntimeException('boom');
            }],
            'nonarray' => ['label' => 'NonArray', 'callable' => static fn(): string => 'nope'],
            'mixed' => [
                'label' => 'Mixed',
                'callable' => static fn(): array => [
                    ['title' => '', 'url' => '/x'],
                    ['title' => 'No Url', 'url' => ''],
                    'not-an-array',
                    ['title' => 'Good Match', 'url' => '/good', 'excerpt' => '', 'content' => ''],
                ],
            ],
        ];
        $service = $this->service();

        $result = $service->search('good match');
        self::assertSame(1, $result['total']);
        self::assertStringContainsString('Good', strip_tags((string) $result['items'][0]['title']));
    }

    public function testSearchPaginates(): void
    {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = ['title' => "Post {$i} alpha", 'url' => "/p{$i}", 'excerpt' => '', 'content' => '', 'published_at' => '2026-01-01 00:00:00'];
        }
        $this->providers = [
            'blog' => ['label' => 'Blog', 'callable' => static fn() => $items],
        ];
        $this->settings = ['Search.resultsPerPage' => '2'];
        $service = $this->service();

        $page1 = $service->search('alpha', 1);
        self::assertSame(5, $page1['total']);
        self::assertCount(2, $page1['items']);
        self::assertSame(2, $page1['per_page']);

        $page3 = $service->search('alpha', 3);
        self::assertCount(1, $page3['items']);
    }

    public function testSearchHighlightsAndEscapes(): void
    {
        $this->providers = [
            'pages' => [
                'label' => 'Pages',
                'callable' => static fn(): array => [
                    ['title' => '<b>About</b> fish & chips', 'url' => '/a', 'excerpt' => 'About <script>x</script>', 'content' => '', 'published_at' => ''],
                ],
            ],
        ];
        $service = $this->service();

        $result = $service->search('about');
        $title = (string) $result['items'][0]['title'];
        self::assertStringNotContainsString('<script>', $title);
        self::assertStringContainsString('<mark>About</mark>', $title);
        self::assertStringContainsString('<mark>About</mark>', (string) $result['items'][0]['excerpt']);
    }

    public function testPhraseScoresAboveSingleWord(): void
    {
        $service = $this->service();

        $phrase = ['title' => 'hello world today', 'url' => '/a', 'excerpt' => '', 'content' => '', 'published_at' => ''];
        $single = ['title' => 'hello elsewhere', 'url' => '/b', 'excerpt' => '', 'content' => '', 'published_at' => ''];

        $phraseScore = $this->invoke($service, 'scoreItem', [$phrase, ['hello world']]);
        $singleScore = $this->invoke($service, 'scoreItem', [$single, ['hello']]);
        self::assertGreaterThan($singleScore, $phraseScore);
    }

    public function testRecencyBoostsRecentItems(): void
    {
        $service = $this->service();

        $fresh = ['title' => 'same title here', 'url' => '/a', 'excerpt' => '', 'content' => '', 'published_at' => date('Y-m-d H:i:s')];
        $old = ['title' => 'same title here', 'url' => '/b', 'excerpt' => '', 'content' => '', 'published_at' => '2000-01-01 00:00:00'];

        $freshScore = $this->invoke($service, 'scoreItem', [$fresh, ['same']]);
        $oldScore = $this->invoke($service, 'scoreItem', [$old, ['same']]);
        self::assertGreaterThan($oldScore, $freshScore);
    }

    public function testTokenizeHandlesPhrasesDuplicatesAndCase(): void
    {
        $service = $this->service();

        self::assertSame(['hello world', 'foo'], $this->invoke($service, 'tokenize', ['"Hello World" foo']));
        self::assertSame(['foo'], $this->invoke($service, 'tokenize', ['FOO foo']));
        self::assertSame([], $this->invoke($service, 'tokenize', ['   ']));
    }

    public function testAgeDaysVariants(): void
    {
        $service = $this->service();

        self::assertNull($this->invoke($service, 'ageDays', ['']));
        self::assertNull($this->invoke($service, 'ageDays', ['not-a-date']));
        self::assertSame(0, $this->invoke($service, 'ageDays', [date('Y-m-d H:i:s')]));
    }

    public function testSourceLabelJoinsUniqueSources(): void
    {
        $service = $this->service();

        $label = $this->invoke($service, 'sourceLabel', [[
            ['_source' => 'pages'],
            ['_source' => 'blog'],
            ['_source' => 'pages'],
            ['no-source' => true],
        ]]);
        self::assertSame('pages, blog', $label);
        self::assertSame('', $this->invoke($service, 'sourceLabel', [[]]));
    }

    public function testSourcesAndEnabledSources(): void
    {
        $this->providers = [
            'pages' => ['label' => 'Pages', 'callable' => static fn(): array => []],
            'blog' => ['label' => 'Blog', 'callable' => static fn(): array => []],
        ];
        $service = $this->service();

        self::assertSame(['pages', 'blog'], array_keys($service->sources()));

        $decorated = $service->enabledSources(true);
        self::assertTrue($decorated['pages']['enabled']);
        self::assertTrue($decorated['blog']['enabled']);

        $service->setSourceEnabled('blog', false);
        $enabled = $service->enabledSources(false);
        self::assertSame(['pages'], array_keys($enabled));

        $decorated = $service->enabledSources(true);
        self::assertFalse($decorated['blog']['enabled']);

        // Re-enable restores the source.
        $service->setSourceEnabled('blog', true);
        self::assertSame(['pages', 'blog'], array_keys($service->enabledSources(false)));

        // Disabling twice does not duplicate the key.
        $service->setSourceEnabled('blog', false);
        $service->setSourceEnabled('blog', false);
        $raw = json_decode((string) $this->settings['Search.disabledSources'], true);
        self::assertSame(['blog'], $raw);
    }

    public function testDisabledSourceKeysIgnoresGarbage(): void
    {
        $service = $this->service();

        self::assertSame([], $this->invoke($service, 'disabledSourceKeys', []));

        $this->settings = ['Search.disabledSources' => 'not-json'];
        self::assertSame([], $this->invoke($service, 'disabledSourceKeys', []));

        $this->settings = ['Search.disabledSources' => json_encode(['pages', 42, null])];
        self::assertSame(['pages'], $this->invoke($service, 'disabledSourceKeys', []));
    }

    public function testSettingReadsThrough(): void
    {
        $service = $this->service();

        self::assertSame('fallback', $service->setting('missing', 'fallback'));
        $this->settings = ['Search.resultsPerPage' => '7'];
        self::assertSame('7', $service->setting('resultsPerPage', 10));
    }

    private function service(): SearchService
    {
        $test = $this;
        $app = $this->app([
            'adext' => static fn(): object => new class($test) {
                public function __construct(private SearchServiceTest $t)
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
                public function __construct(private SearchServiceTest $t)
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
        ]);
        \Flight::setEngine($app);

        return new SearchService($app);
    }
}
