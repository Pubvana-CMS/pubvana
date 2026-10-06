<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use Pubvana\Models\TrustCache;
use Pubvana\Services\TrustClientService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Test-friendly TrustClientService: the HTTP seam is overridden so no test
 * touches the network. Each queued body string (or null for transport
 * failure) is the content of one response.
 */
final class FakeHttpTrustClient extends TrustClientService
{
    /** @var list<array<string, mixed>> */
    public array $requests = [];

    /** @var list<string> */
    public array $urls = [];

    /** @var list<?string> */
    public array $bodies = [];

    protected function httpPostJson(string $url, array $payload): ?string
    {
        $this->urls[] = $url;
        $this->requests[] = $payload;
        $body = array_shift($this->bodies);
        return $body;
    }
}

/**
 * Settings stand-in: array-backed get/set.
 */
final class ArraySettings
{
    /** @var array<string, mixed> */
    public array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->store[$key] = $value;
    }
}

#[\PHPUnit\Framework\Attributes\CoversClass(TrustClientService::class)]
final class TrustClientServiceTest extends TestCase
{
    private \PDO $pdo;
    private FakeHttpTrustClient $service;
    private ArraySettings $settings;
    private string $maliciousFile;

    protected function setUp(): void
    {
        $this->pdo = Sqlite::recreate();
        $this->settings = new ArraySettings();
        $this->maliciousFile = (string) tempnam(sys_get_temp_dir(), 'trust-malicious-');
        @unlink($this->maliciousFile);

        $app = $this->app([
            'settings'     => fn (): ArraySettings => $this->settings,
            'pluginLoader' => fn () => new class {
                /** @return array<string, mixed> */
                public function discoverLocal(): array
                {
                    return [
                        'pubvana/blog' => [
                            'source'   => 'local',
                            'folder'   => 'Blog',
                            'manifest' => ['name' => 'pubvana/blog', 'semver' => '1.0.0'],
                            'name'     => 'Blog',
                        ],
                        'orphan' => [
                            'source'   => 'local',
                            'folder'   => 'Orphan',
                            'manifest' => ['name' => 'jane/orphan', 'semver' => '2.0.0', 'author' => 'jane'],
                        ],
                    ];
                }

                /** @return array<string, mixed> */
                public function discoverVendor(): array
                {
                    return [
                        'enlivenapp/flight-shield' => ['source' => 'vendor', 'version' => '1.2.3'],
                    ];
                }
            },
            'themes' => fn () => new class($this->defaultThemeVersion()) {
                public function __construct(private string $themeVersion)
                {
                }

                /** @return list<array<string, mixed>> */
                public function discover(): array
                {
                    return [
                        ['folder' => 'default', 'semver' => $this->themeVersion, 'author' => 'pubvana'],
                        ['folder' => 'noversion'],
                    ];
                }
            },
        ]);

        // SITE_URL is deployment config: the service reads it from the app.
        $app->set('siteUrl', 'https://test.example.com/');

        $this->service = new FakeHttpTrustClient($this->pdo, $app, $this->maliciousFile);
    }

    protected function tearDown(): void
    {
        if (is_file($this->maliciousFile)) {
            @unlink($this->maliciousFile);
        }
    }

    // -----------------------------------------------------------------
    // Malicious-list envelope parsing
    // -----------------------------------------------------------------

    public function testExtractMaliciousListAcceptsValidEntries(): void
    {
        $list = $this->service->extractMaliciousList([
            'results'   => [],
            'malicious' => [
                ['type' => 'plugin', 'slug' => 'acme'],
                ['type' => 'theme', 'slug' => 'shady'],
            ],
        ]);

        self::assertSame([
            ['type' => 'plugin', 'slug' => 'acme'],
            ['type' => 'theme', 'slug' => 'shady'],
        ], $list);
    }

    public function testExtractMaliciousListDropsMalformedAndDuplicates(): void
    {
        $list = $this->service->extractMaliciousList([
            'malicious' => [
                ['type' => 'plugin', 'slug' => 'acme'],
                ['type' => 'widget', 'slug' => 'no'],   // bad type
                ['type' => 'plugin', 'slug' => ''],      // empty slug
                'not-an-array',
                ['type' => 'plugin', 'slug' => 'acme'],  // duplicate
            ],
        ]);

        self::assertSame([['type' => 'plugin', 'slug' => 'acme']], $list);
    }

    public function testExtractMaliciousListHandlesAbsentKey(): void
    {
        self::assertSame([], $this->service->extractMaliciousList(['results' => []]));
        self::assertSame([], $this->service->extractMaliciousList(['malicious' => 'nope']));
    }

    // -----------------------------------------------------------------
    // Malicious-list cache file
    // -----------------------------------------------------------------

    public function testMaliciousListRoundTrip(): void
    {
        $this->service->maliciousListWrite(
            [['type' => 'plugin', 'slug' => 'acme']],
            [['type' => 'theme', 'slug' => 'shady']]
        );

        $state = $this->service->maliciousListRead();
        self::assertSame([['type' => 'plugin', 'slug' => 'acme']], $state['current']);
        self::assertSame([['type' => 'theme', 'slug' => 'shady']], $state['processed']);
    }

    public function testMaliciousListMissingOrCorruptFileReadsEmpty(): void
    {
        @unlink($this->maliciousFile);
        self::assertSame(['current' => [], 'processed' => []], $this->service->maliciousListRead());

        file_put_contents($this->maliciousFile, '{corrupt json');
        self::assertSame(['current' => [], 'processed' => []], $this->service->maliciousListRead());
    }

    // -----------------------------------------------------------------
    // Addon collection
    // -----------------------------------------------------------------

    public function testCollectInstalledAddonsShape(): void
    {
        $addons = $this->service->collectInstalledAddons();

        // Plugin rows exactly. The theme row is not compared field by field:
        // its values come from themes/default/pubvana.json, the same file the
        // service reads, so the comparison asserts nothing.
        self::assertSame([
            ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'author' => 'pubvana', 'origin' => 'local'],
            ['type' => 'plugin', 'slug' => 'orphan', 'version' => '2.0.0', 'author' => 'jane', 'origin' => 'local'],
            ['type' => 'plugin', 'slug' => 'flight-shield', 'version' => '1.2.3', 'author' => 'enlivenapp', 'origin' => 'composer'],
        ], array_slice($addons, 0, 3));
        self::assertCount(4, $addons, 'the theme is still collected');
    }

    public function testPluginItemNullWithoutVersion(): void
    {
        self::assertNull($this->service->pluginItem('x', ['source' => 'vendor', 'version' => '']));
        self::assertNull($this->service->pluginItem('x', ['source' => 'local', 'folder' => 'X', 'manifest' => []]));
    }

    public function testPluginItemSplitsNameIntoAuthorAndSlug(): void
    {
        $local = $this->service->pluginItem('pubvana/blog', [
            'source'   => 'local',
            'folder'   => 'Blog',
            'manifest' => ['name' => 'pubvana/blog', 'semver' => '1.0.0'],
        ]);
        self::assertNotNull($local);
        self::assertSame('blog', $local['slug']);
        self::assertSame('pubvana', $local['author']);
        self::assertSame('local', $local['origin']);

        $vendor = $this->service->pluginItem('enlivenapp/flight-shield', ['source' => 'vendor', 'version' => '1.2.3']);
        self::assertNotNull($vendor);
        self::assertSame('flight-shield', $vendor['slug']);
        self::assertSame('enlivenapp', $vendor['author']);
        self::assertSame('composer', $vendor['origin']);
    }

    public function testPluginItemRejectsIncompleteNames(): void
    {
        // A composer-style id is a filename only as the loader's fallback;
        // the identity comes from the manifest, and an unnamed plugin or
        // a name without a vendor prefix has nothing to ask about.
        self::assertNull($this->service->pluginItem('x', ['source' => 'local', 'folder' => 'X', 'manifest' => ['semver' => '1.0.0']]));
        self::assertNull($this->service->pluginItem('x', ['source' => 'local', 'folder' => 'X', 'manifest' => ['name' => 'slugless', 'semver' => '1.0.0']]));
    }

    public function testThemeItemReadsFolderManifest(): void
    {
        $item = $this->service->themeItem('default');
        self::assertNotNull($item);
        self::assertSame('theme', $item['type']);
        self::assertSame('default', $item['slug']);
        self::assertSame('pubvana', $item['author']);
    }

    public function testThemeItemRefusesFolderTraversal(): void
    {
        // The folder locates a manifest; it is not a path. Anything that
        // could step out of the themes root resolves to no theme at all.
        self::assertNull($this->service->themeItem('..'));
        self::assertNull($this->service->themeItem('.'));
        self::assertNull($this->service->themeItem('../themes/default'));
        self::assertNull($this->service->themeItem('default/../../'));
        self::assertNull($this->service->themeItem('/etc'));
        self::assertNull($this->service->themeItem('nope'));
    }

    /**
     * The install gate asks about a package before it is on disk, so the
     * identity has to come out of the raw pubvana.json alone.
     */
    public function testPackageItemReadsAPluginManifest(): void
    {
        $item = $this->service->packageItem([
            'name'   => 'pubvana/blog',
            'type'   => 'plugin',
            'semver' => '1.0.0',
        ]);

        self::assertSame([
            'type'    => 'plugin',
            'slug'    => 'blog',
            'version' => '1.0.0',
            'author'  => 'pubvana',
            'origin'  => 'local',
        ], $item);
    }

    public function testPackageItemReadsAThemeManifest(): void
    {
        $item = $this->service->packageItem([
            'type'   => 'theme',
            'slug'   => 'default',
            'author' => 'pubvana',
            'semver' => '1.4.13',
        ]);

        self::assertSame([
            'type'    => 'theme',
            'slug'    => 'default',
            'version' => '1.4.13',
            'author'  => 'pubvana',
            'origin'  => 'local',
        ], $item);
    }

    public function testPackageItemAnswersNullWhenTheManifestCannotNameTheAddon(): void
    {
        self::assertNull($this->service->packageItem(['name' => 'slugless', 'semver' => '1.0.0']));
        self::assertNull($this->service->packageItem(['name' => 'pubvana/blog']));
        self::assertNull($this->service->packageItem(['type' => 'theme', 'slug' => 'default', 'semver' => '1.0.0']));
        self::assertNull($this->service->packageItem(['name' => 'pubvana/blog', 'semver' => '']));
    }

    // -----------------------------------------------------------------
    // Checks (HTTP seam canned)
    // -----------------------------------------------------------------

    public function testCheckAddonUpsertsAnswer(): void
    {
        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'Blog', 'version' => '1.0.0', 'status' => 'trusted', 'warning' => null],
            ],
        ]);

        $result = $this->service->checkAddon('plugin', 'Blog', '1.0.0', 'pubvana');

        self::assertSame('trusted', $result['status']);
        self::assertNull($result['warning']);
        self::assertTrue($result['answered']);

        $cached = $this->service->getCachedStatus('plugin', 'Blog', '1.0.0', 'pubvana');
        self::assertNotNull($cached);
        self::assertSame('trusted', $cached['status']);
    }

    public function testCheckAddonFailsClosedWithoutCachePoisoning(): void
    {
        $result = $this->service->checkAddon('plugin', 'Blog', '1.0.0', 'pubvana');

        self::assertSame('unknown', $result['status']);
        self::assertFalse($result['answered']);
        self::assertNull($this->service->getCachedStatus('plugin', 'Blog', '1.0.0', 'pubvana'));
    }

    public function testCheckAddonStashesMaliciousListForNextCron(): void
    {
        $this->service->bodies[] = (string) json_encode([
            'results'   => [
                ['type' => 'plugin', 'slug' => 'Blog', 'version' => '1.0.0', 'status' => 'trusted', 'warning' => null],
            ],
            'malicious' => [
                ['type' => 'plugin', 'slug' => 'blog'],
            ],
        ]);

        $this->service->checkAddon('plugin', 'Blog', '1.0.0', 'pubvana');

        $state = $this->service->maliciousListRead();
        self::assertContains(['type' => 'plugin', 'slug' => 'blog'], $state['current']);
    }

    public function testCheckAddonEmptyListClearsAStaleFinding(): void
    {
        $this->service->maliciousListWrite(
            [['type' => 'plugin', 'slug' => 'blog']],
            [['type' => 'plugin', 'slug' => 'blog']]
        );

        $this->service->bodies[] = (string) json_encode([
            'results'   => [
                ['type' => 'plugin', 'slug' => 'Blog', 'version' => '1.0.0', 'status' => 'trusted', 'warning' => null],
            ],
            'malicious' => [],
        ]);

        $this->service->checkAddon('plugin', 'Blog', '1.0.0', 'pubvana');

        $state = $this->service->maliciousListRead();
        self::assertSame([], $state['current']);
        self::assertSame([], $state['processed']);
    }

    public function testRecheckAllBatchesInstalledAddons(): void
    {
        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'status' => 'trusted', 'warning' => null],
            ],
        ]);

        $outcome = $this->service->recheckAll();

        self::assertTrue($outcome['ok']);
        self::assertCount(1, $this->service->requests);

        // Every installed addon rides in one batch (2 local plugins,
        // 1 vendor package, 1 theme = 4 items). Core is not an addon and
        // is never asked about.
        $items = $this->service->requests[0]['items'] ?? [];
        self::assertCount(4, $items);
        $slugs = array_column($items, 'slug');
        self::assertContains('blog', $slugs);
        self::assertContains('default', $slugs);
        self::assertNotContains('pubvana/pubvana', $slugs);
    }

    public function testRecheckAllFailureReturnsNotOkAndCachesNothing(): void
    {
        $this->service->bodies[] = null; // transport failure

        $outcome = $this->service->recheckAll();

        self::assertFalse($outcome['ok']);
        self::assertNull($this->service->getCachedStatus('plugin', 'blog', '1.0.0', 'pubvana'));
    }

    public function testRecheckAllWritesMaliciousList(): void
    {
        $this->service->bodies[] = (string) json_encode([
            'results'   => [],
            'malicious' => [['type' => 'plugin', 'slug' => 'acme']],
        ]);

        $this->service->recheckAll();

        $state = $this->service->maliciousListRead();
        self::assertSame([['type' => 'plugin', 'slug' => 'acme']], $state['current']);
    }

    public function testCheckAddonsMatchesAnswersBackToItems(): void
    {
        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'Blog', 'version' => '1.0.0', 'status' => 'known', 'warning' => 'dto'],
            ],
        ]);

        $outcome = $this->service->checkAddons([
            ['type' => 'plugin', 'slug' => 'Blog', 'version' => '1.0.0', 'author' => 'pubvana', 'origin' => 'local'],
        ]);

        self::assertTrue($outcome['ok']);
        self::assertCount(1, $outcome['results']);
        self::assertSame('dto', $outcome['results'][0]['warning']);

        $cached = $this->service->getCachedStatus('plugin', 'Blog', '1.0.0', 'pubvana');
        self::assertSame('known', $cached !== null ? $cached['status'] : null);
    }

    public function testEnsureCacheForAllChecksThenSkips(): void
    {
        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'status' => 'trusted', 'warning' => null],
                ['type' => 'plugin', 'slug' => 'orphan', 'version' => '2.0.0', 'status' => 'unknown', 'warning' => null],
                ['type' => 'plugin', 'slug' => 'flight-shield', 'version' => '1.2.3', 'status' => 'malicious', 'warning' => 'bad'],
                ['type' => 'theme', 'slug' => 'default', 'version' => $this->defaultThemeVersion(), 'status' => 'trusted', 'warning' => null],
            ],
        ]);

        $this->service->ensureCacheForAll();
        self::assertCount(1, $this->service->requests);        $second = new FakeHttpTrustClient($this->pdo, $this->serviceApp(), $this->maliciousFile);
        $second->ensureCacheForAll();
        self::assertCount(0, $second->requests);
    }

    // -----------------------------------------------------------------
    // Cron flow
    // -----------------------------------------------------------------

    public function testCheckIfDueHonorsNewFindingsAndAdvancesProcessed(): void
    {
        // Blog is currently trusted on a fresh TTL.
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c');
        $this->service->maliciousListWrite(
            [['type' => 'plugin', 'slug' => 'blog']], // current
            []                                        // processed: new finding
        );

        // The honoring re-ask answers malicious.
        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'status' => 'malicious', 'warning' => 'bad code'],
            ],
            'malicious' => [['type' => 'plugin', 'slug' => 'blog']],
        ]);

        $this->service->checkIfDue();

        $cached = $this->service->getCachedStatus('plugin', 'blog', '1.0.0', 'pubvana');
        self::assertSame('malicious', $cached !== null ? $cached['status'] : null);
        self::assertSame('bad code', $cached !== null ? $cached['warning'] : null);

        $state = $this->service->maliciousListRead();
        self::assertSame([['type' => 'plugin', 'slug' => 'blog']], $state['processed']);
    }

    public function testCheckIfDueClearedFindingStopsBeingReasked(): void
    {
        // Blog is trusted and on the list, already processed. TTL expired, so
        // the batch re-asks it. The home site now answers with an empty list.
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);
        $this->service->maliciousListWrite(
            [['type' => 'plugin', 'slug' => 'blog']],
            [['type' => 'plugin', 'slug' => 'blog']]
        );

        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'status' => 'trusted', 'warning' => null],
            ],
            'malicious' => [],
        ]);

        $this->service->checkIfDue();

        // The cleared entry is gone from both lists, so the next TTL batch
        // will not force it past the trusted-skip again.
        $state = $this->service->maliciousListRead();
        self::assertSame([], $state['current']);
        self::assertSame([], $state['processed']);

        // Second run, TTL expired again: Blog is trusted and unlisted, so it
        // must not be forced past the trusted-skip again.
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);
        $this->service->checkIfDue();
        $lastRequest = $this->service->requests[count($this->service->requests) - 1] ?? [];
        self::assertNotContains('blog', array_column($lastRequest['items'] ?? [], 'slug'));
    }

    public function testCheckIfDueDropsFindingsForUninstalledAddons(): void
    {
        // Fresh TTL: no batch fires after the honoring step
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c');
        $this->service->maliciousListWrite(
            [['type' => 'plugin', 'slug' => 'not-installed']],
            []
        );

        $this->service->checkIfDue();

        self::assertCount(0, $this->service->requests);
        $state = $this->service->maliciousListRead();
        self::assertSame([['type' => 'plugin', 'slug' => 'not-installed']], $state['processed']);
    }

    public function testCheckIfDueSkipsTrustedUnlessListed(): void
    {
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->cachePut('plugin', 'flight-shield', '1.2.3', 'enlivenapp', 'trusted');
        $this->cachePut('plugin', 'orphan', '2.0.0', 'jane', 'unknown');
        // TTL expired
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);

        $this->service->checkIfDue();

        self::assertCount(1, $this->service->requests);
        $items = $this->service->requests[0]['items'] ?? [];
        self::assertSame('orphan', $items[0]['slug'] ?? null);
        // A quiet-success or answered batch stamps the TTL
        self::assertArrayHasKey(TrustClientService::LAST_CHECK_KEY, $this->settings->store);
    }

    public function testCheckIfDueListedTrustedIsRechecked(): void
    {
        // Everything trusted and cached, so the batch would normally be empty
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->cachePut('plugin', 'orphan', '2.0.0', 'jane', 'trusted');
        $this->cachePut('plugin', 'flight-shield', '1.2.3', 'enlivenapp', 'trusted');
        $this->cachePut('theme', 'default', $this->defaultThemeVersion(), 'pubvana', 'trusted');
        // TTL expired
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);
        // Blog is on the home-site list; already processed (so no immediate
        // honor), but the batch must include it anyway.
        $this->service->maliciousListWrite(
            [['type' => 'plugin', 'slug' => 'blog']],
            [['type' => 'plugin', 'slug' => 'blog']]
        );

        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'status' => 'malicious', 'warning' => 'oops'],
            ],
            'malicious' => [['type' => 'plugin', 'slug' => 'blog']],
        ]);

        $this->service->checkIfDue();

        self::assertCount(1, $this->service->requests);
        $items = $this->service->requests[0]['items'] ?? [];
        self::assertCount(1, $items);
        self::assertSame('blog', $items[0]['slug'] ?? null);
        self::assertSame('1.0.0', $items[0]['version'] ?? null);

        $cached = $this->service->getCachedStatus('plugin', 'blog', '1.0.0', 'pubvana');
        self::assertSame('malicious', $cached !== null ? $cached['status'] : null);
    }

    public function testCheckIfDueFreshTtlAndNoFindingsMakesNoRequests(): void
    {
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->cachePut('plugin', 'orphan', '2.0.0', 'jane', 'trusted');
        $this->cachePut('plugin', 'flight-shield', '1.2.3', 'enlivenapp', 'trusted');
        $this->cachePut('theme', 'default', $this->defaultThemeVersion(), 'pubvana', 'trusted');
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c');

        $this->service->checkIfDue();

        self::assertCount(0, $this->service->requests);
    }

    public function testCheckIfDueFailureStampsNothingAndKeepsProcessed(): void
    {
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);
        $this->service->maliciousListWrite([['type' => 'plugin', 'slug' => 'blog']], []);

        // HTTP transport failures: honoring POST, then the TTL-expired batch
        // (Blog is listed, so the trusted-skip does not exclude it)
        $this->service->bodies[] = null;
        $this->service->bodies[] = null;

        $this->service->checkIfDue();

        self::assertCount(2, $this->service->requests);
        // The TTL stamp must stay expired and processed must not advance
        self::assertSame(
            date('c', time() - 86400 * 2),
            $this->settings->store[TrustClientService::LAST_CHECK_KEY]
        );
        $state = $this->service->maliciousListRead();
        self::assertSame([], $state['processed']);
        self::assertSame([['type' => 'plugin', 'slug' => 'blog']], $state['current']);
    }

    public function testCheckIfDuePrunesProcessedForClearedFindings(): void
    {
        // Two findings processed; the home site now lists only one of them.
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);
        $this->service->maliciousListWrite(
            [['type' => 'plugin', 'slug' => 'blog'], ['type' => 'plugin', 'slug' => 'orphan']],
            [['type' => 'plugin', 'slug' => 'blog'], ['type' => 'plugin', 'slug' => 'orphan']]
        );

        $this->service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'status' => 'malicious', 'warning' => 'still bad'],
                ['type' => 'plugin', 'slug' => 'orphan', 'version' => '2.0.0', 'status' => 'trusted', 'warning' => null],
            ],
            'malicious' => [['type' => 'plugin', 'slug' => 'blog']],
        ]);

        $this->service->checkIfDue();

        $state = $this->service->maliciousListRead();
        self::assertSame([['type' => 'plugin', 'slug' => 'blog']], $state['current']);
        self::assertSame([['type' => 'plugin', 'slug' => 'blog']], $state['processed']);
    }

    public function testVersionChangeIsANaturalCacheMiss(): void
    {
        $this->cachePut('plugin', 'blog', '1.0.0', 'pubvana', 'trusted');
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);

        // The plugin folder got an update: 2.0.0 in the manifest
        $app = $this->app([
            'settings'     => fn (): ArraySettings => $this->settings,
            'pluginLoader' => fn () => new class {
                /** @return array<string, mixed> */
                public function discoverLocal(): array
                {
                    return [
                        'pubvana/blog' => [
                            'source'   => 'local',
                            'folder'   => 'Blog',
                            'manifest' => ['name' => 'pubvana/blog', 'semver' => '2.0.0'],
                            'name'     => 'Blog',
                        ],
                    ];
                }

                /** @return array<string, mixed> */
                public function discoverVendor(): array
                {
                    return [];
                }
            },
            'themes' => fn () => new class {
                /** @return list<array<string, mixed>> */
                public function discover(): array
                {
                    return [];
                }
            },
        ]);

        $service = new FakeHttpTrustClient($this->pdo, $app, $this->maliciousFile);
        $service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '2.0.0', 'status' => 'unknown', 'warning' => null],
            ],
        ]);

        $service->checkIfDue();

        self::assertCount(1, $service->requests);
        self::assertSame('2.0.0', $service->requests[0]['items'][0]['version'] ?? null);
    }

    public function testPayloadCarriesIdentityAndOrigin(): void
    {
        $this->cachePut('plugin', 'orphan', '2.0.0', 'jane', 'unknown');
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);

        $this->service->checkIfDue();

        $payload = $this->service->requests[0] ?? null;
        self::assertNotNull($payload);
        self::assertSame($this->manifestSemver(), $payload['pv_version'] ?? null);
        self::assertSame('https://test.example.com', $payload['base_url'] ?? null);
        // Blog (local, uncached) leads the batch; Orphan's identity proves
        // the manifest name split and the local origin survive the trip.
        self::assertSame('pubvana', $payload['items'][0]['author'] ?? null);
        self::assertSame('orphan', $payload['items'][1]['slug'] ?? null);
        self::assertSame('jane', $payload['items'][1]['author'] ?? null);
        self::assertSame('local', $payload['items'][1]['origin'] ?? null);
    }

    // -----------------------------------------------------------------
    // Endpoint selection (M13)
    // -----------------------------------------------------------------

    public function testProductionEndpointIsTheHomeSite(): void
    {
        self::assertSame('https://pubvanacms.com/api/trust/v1/check', TrustClientService::TRUST_API_URL);
    }

    public function testProductionAppHitsTheHomeSiteEndpoint(): void
    {
        $app = $this->app([
            'environment' => 'production',
            'settings'    => fn (): ArraySettings => $this->settings,
        ]);
        $service = new FakeHttpTrustClient($this->pdo, $app, $this->maliciousFile);

        self::assertSame(TrustClientService::TRUST_API_URL, $this->invoke($service, 'effectiveApiUrl'));
    }

    public function testUnsetEnvironmentFallsBackToProduction(): void
    {
        $app = $this->app([
            'settings' => fn (): ArraySettings => $this->settings,
        ]);
        $service = new FakeHttpTrustClient($this->pdo, $app, $this->maliciousFile);

        self::assertSame(TrustClientService::TRUST_API_URL, $this->invoke($service, 'effectiveApiUrl'));
    }

    public function testDevelopmentAppHitsTheHomeSiteEndpoint(): void
    {
        $app = $this->app([
            'environment' => 'development',
            'settings'    => fn (): ArraySettings => $this->settings,
        ]);
        $service = new FakeHttpTrustClient($this->pdo, $app, $this->maliciousFile);

        self::assertSame(TrustClientService::TRUST_API_URL, $this->invoke($service, 'effectiveApiUrl'));
    }

    public function testExplicitOverrideBeatsTheHomeSite(): void
    {
        $app = $this->app([
            'environment' => 'development',
            'settings'    => fn (): ArraySettings => $this->settings,
        ]);
        $service = new FakeHttpTrustClient($this->pdo, $app, $this->maliciousFile, 'https://override.example/check');

        self::assertSame('https://override.example/check', $this->invoke($service, 'effectiveApiUrl'));
    }

    public function testBatchPostsToTheSelectedEndpoint(): void
    {
        $app = $this->app([
            'environment'  => 'development',
            'settings'     => fn (): ArraySettings => $this->settings,
            'pluginLoader' => fn () => new class {
                /** @return array<string, mixed> */
                public function discoverLocal(): array
                {
                    return [
                        'pubvana/blog' => [
                            'source'   => 'local',
                            'folder'   => 'Blog',
                            'manifest' => ['name' => 'pubvana/blog', 'semver' => '1.0.0'],
                            'name'     => 'Blog',
                        ],
                    ];
                }

                /** @return array<string, mixed> */
                public function discoverVendor(): array
                {
                    return [];
                }
            },
            'themes' => fn () => new class {
                /** @return list<array<string, mixed>> */
                public function discover(): array
                {
                    return [];
                }
            },
        ]);
        $service = new FakeHttpTrustClient($this->pdo, $app, $this->maliciousFile);
        $this->settings->store[TrustClientService::LAST_CHECK_KEY] = date('c', time() - 86400 * 2);
        $service->bodies[] = (string) json_encode([
            'results' => [
                ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0.0', 'status' => 'unknown', 'warning' => null],
            ],
        ]);

        $service->checkIfDue();

        self::assertSame([TrustClientService::TRUST_API_URL], $service->urls);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function cachePut(string $type, string $slug, string $version, string $author, string $status): void
    {
        (new TrustCache($this->pdo))->upsert($type, $slug, $version, $author, $status, null);
    }

    private function serviceApp(): \flight\Engine
    {
        return $this->app([
            'settings'     => fn (): ArraySettings => $this->settings,
            'pluginLoader' => fn () => new class {
                /** @return array<string, mixed> */
                public function discoverLocal(): array
                {
                    return [];
                }

                /** @return array<string, mixed> */
                public function discoverVendor(): array
                {
                    return [];
                }
            },
            'themes' => fn () => new class {
                /** @return list<array<string, mixed>> */
                public function discover(): array
                {
                    return [];
                }
            },
        ]);
    }

    private function manifestSemver(): string
    {
        $payload = json_decode((string) file_get_contents(PROJECT_ROOT . '/pubvana.json'), true, 512, JSON_THROW_ON_ERROR);
        return (string) ($payload['semver'] ?? '');
    }

    /**
     * The Default theme's real version. TrustClientService::themeItem() reads
     * themes/{folder}/pubvana.json itself, so these fixtures have to track
     * that file instead of pinning a number that a theme bump invalidates.
     */
    private function defaultThemeVersion(): string
    {
        $payload = json_decode(
            (string) file_get_contents(PROJECT_ROOT . '/themes/default/pubvana.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return (string) ($payload['semver'] ?? '');
    }
}
