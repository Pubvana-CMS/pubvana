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
        // Nothing was scored, so there is no ceiling to report.
        self::assertSame(0.0, $result['max_score']);
    }

    public function testSearchErrorsWithNoEnabledSources(): void
    {
        $service = $this->service();

        $result = $service->search('hello world');
        self::assertSame([], $result['items']);
        self::assertStringContainsString('No search sources', (string) $result['error']);
        self::assertSame(0.0, $result['max_score']);
    }

    /**
     * The ceiling moves with the tokenized query. A word token can reach title
     * prefix 12 + excerpt 5 + content 3; a phrase token can reach title 20 +
     * excerpt 12 + content 8. Recency adds its maximum once, not per token.
     */
    public function testMaxScoreMovesWithTheQuery(): void
    {
        $service = $this->service();

        self::assertSame(24.0, $this->invoke($service, 'maxScore', [['pubvana']]));
        self::assertSame(44.0, $this->invoke($service, 'maxScore', [['alpha bravo']]));
        self::assertSame(44.0, $this->invoke($service, 'maxScore', [['one', 'two']]));
        self::assertSame(64.0, $this->invoke($service, 'maxScore', [['alpha bravo', 'three']]));
        self::assertSame(64.0, $this->invoke($service, 'maxScore', [['one', 'two', 'three']]));
    }

    /**
     * The ceiling must be a real upper bound, or the ratio it produces is
     * meaningless. This is the invariant that makes score/max readable.
     */
    public function testMaxScoreIsAnUpperBoundForEveryItem(): void
    {
        $this->providers = [
            'pages' => [
                'label' => 'Pages',
                'callable' => static fn(): array => [
                    ['title' => 'alpha bravo', 'url' => '/1', 'excerpt' => 'alpha bravo', 'content' => 'alpha bravo', 'published_at' => date('Y-m-d H:i:s')],
                    ['title' => 'my alpha bravo', 'url' => '/2', 'excerpt' => 'alpha', 'content' => 'bravo', 'published_at' => date('Y-m-d H:i:s')],
                    ['title' => 'other', 'url' => '/3', 'excerpt' => '', 'content' => 'alpha', 'published_at' => ''],
                ],
            ],
        ];
        $service = $this->service();

        $result = $service->search('alpha bravo');

        self::assertSame(44.0, $result['max_score']);
        self::assertSame(3, $result['total']);
        foreach ($result['items'] as $item) {
            self::assertLessThanOrEqual($result['max_score'], $item['_score']);
        }
    }

    /**
     * A perfect hit is the ceiling minus only what recency did not pay, so an
     * item on today's date at a title prefix hit reaches the ceiling exactly.
     */
    public function testMaxScoreIsReachableByAPerfectHit(): void
    {
        $this->providers = [
            'pages' => [
                'label' => 'Pages',
                'callable' => static fn(): array => [
                    ['title' => 'alpha bravo', 'url' => '/1', 'excerpt' => 'alpha', 'content' => 'alpha', 'published_at' => date('Y-m-d H:i:s')],
                ],
            ],
        ];
        $service = $this->service();

        $result = $service->search('alpha');

        self::assertSame(24.0, $result['max_score']);
        self::assertSame(24.0, $result['items'][0]['_score']);
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

    /**
     * A provider that throws must reach the log. It contributes no items, so
     * without the log line a broken source is indistinguishable from a term
     * that genuinely matched nothing.
     */
    public function testThrowingProviderIsLogged(): void
    {
        $this->providers = [
            'pubvana.blog' => [
                'label' => 'Blog Posts',
                'callable' => static function (): array {
                    throw new \RuntimeException('SQLSTATE[42000]: syntax error near ESCAPE');
                },
            ],
        ];
        $service = $this->service();

        $log = tempnam(sys_get_temp_dir(), 'pubvana-search-log-');
        if ($log === false) {
            self::fail('Could not create a scratch log file.');
        }

        $previous = ini_get('error_log');
        ini_set('error_log', $log);

        try {
            $result = $service->search('pubvana');
        } finally {
            ini_set('error_log', is_string($previous) ? $previous : '');
        }

        $written = (string) file_get_contents($log);
        @unlink($log);

        // The visitor still sees a plain empty result set.
        self::assertSame(0, $result['total']);
        self::assertNull($result['error']);

        self::assertStringContainsString("source 'pubvana.blog' failed for term 'pubvana'", $written);
        self::assertStringContainsString('SQLSTATE[42000]', $written);
    }

    /**
     * A source with no callable, and one returning a non-array, are both
     * skipped and reported rather than silently dropped.
     */
    public function testSkippedSourceShapesAreLogged(): void
    {
        $this->providers = [
            'no-callable' => ['label' => 'No Callable'],
            'wrong-type' => ['label' => 'Wrong Type', 'callable' => static fn(): string => 'nope'],
        ];
        $service = $this->service();

        $log = tempnam(sys_get_temp_dir(), 'pubvana-search-log-');
        if ($log === false) {
            self::fail('Could not create a scratch log file.');
        }

        $previous = ini_get('error_log');
        ini_set('error_log', $log);

        try {
            $result = $service->search('anything here');
        } finally {
            ini_set('error_log', is_string($previous) ? $previous : '');
        }

        $written = (string) file_get_contents($log);
        @unlink($log);

        self::assertSame(0, $result['total']);
        self::assertStringContainsString("source 'no-callable' has no callable", $written);
        self::assertStringContainsString("source 'wrong-type' returned string", $written);
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

    /**
     * The title tiers must stay reachable: prefix beats whole word beats inner
     * substring. Ordering the checks the other way round made the 10-point
     * whole-word tier dead, because a word-boundary hit always implies a
     * substring hit.
     */
    public function testTitleTiersPayPrefixThenWholeWordThenSubstring(): void
    {
        $service = $this->service();

        $subject = static fn(string $title): array => [
            'title' => $title, 'url' => '/x', 'excerpt' => '', 'content' => '', 'published_at' => '',
        ];

        self::assertSame(12.0, $this->invoke($service, 'scoreItem', [$subject('alpha bravo'), ['alpha']]));
        self::assertSame(10.0, $this->invoke($service, 'scoreItem', [$subject('my alpha bravo'), ['alpha']]));
        self::assertSame(8.0, $this->invoke($service, 'scoreItem', [$subject('myalphabet bravo'), ['alpha']]));
    }

    /**
     * A provider that ships the stripped body earns the content tier; one that
     * omits it forfeits that tier and scores only on title and excerpt.
     */
    public function testContentTierScoresBodyOnlyMatches(): void
    {
        $service = $this->service();

        $withBody = $this->invoke($service, 'scoreItem', [[
            'title' => 'Unrelated heading', 'url' => '/x', 'excerpt' => '', 'content' => 'alpha in the body', 'published_at' => '',
        ], ['alpha']]);
        $withoutBody = $this->invoke($service, 'scoreItem', [[
            'title' => 'Unrelated heading', 'url' => '/x', 'excerpt' => '', 'published_at' => '',
        ], ['alpha']]);

        self::assertSame(3.0, $withBody);
        self::assertSame(0.0, $withoutBody);
    }

    public function testPhraseScoresInSuppliedContent(): void
    {
        $service = $this->service();

        $score = $this->invoke($service, 'scoreItem', [[
            'title' => 'Heading', 'url' => '/x', 'excerpt' => '', 'content' => 'the alpha bravo sequence', 'published_at' => '',
        ], ['alpha bravo']]);

        self::assertSame(8.0, $score);
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
