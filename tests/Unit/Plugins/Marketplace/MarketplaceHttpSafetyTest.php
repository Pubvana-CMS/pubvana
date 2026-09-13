<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * H2: marketplace download allow-list bypass.
 *
 * The outbound HTTP layer must only reach http/https store hosts, re-validate
 * every redirect hop by hand (no CURLOPT_FOLLOWLOCATION / stream follow), keep
 * loopback/dev hosts out of production, and carry the account token only in an
 * Authorization: Bearer header, never in the query string.
 */
#[CoversClass(MarketplaceService::class)]
final class MarketplaceHttpSafetyTest extends TestCase
{
    private HttpSafetySettingsStub $settings;
    private HttpSafetyService $service;

    protected function setUp(): void
    {
        $this->settings = new HttpSafetySettingsStub();
        $app = $this->app([
            'settings' => fn (): HttpSafetySettingsStub => $this->settings,
        ]);
        $this->service = new HttpSafetyService(
            Sqlite::recreate(),
            $app,
            ['store_url' => 'http://plugindev', 'api_timeout' => 3],
        );
    }

    public function testLoopbackHostsRejectedInProduction(): void
    {
        // environment is unset, so environment() defaults to production.
        self::assertFalse($this->invoke($this->service, 'isAllowedStoreUrl', ['http://localhost/api/store/items']));
        self::assertFalse($this->invoke($this->service, 'isAllowedStoreUrl', ['http://plugindev/api/store/items']));
    }

    public function testLoopbackHostsAllowedOnlyInDevelopment(): void
    {
        $this->useDevelopment();
        self::assertTrue($this->invoke($this->service, 'isAllowedStoreUrl', ['http://localhost/api/store/items']));
        self::assertTrue($this->invoke($this->service, 'isAllowedStoreUrl', ['http://plugindev/api/store/items']));
    }

    public function testOnlyHttpAndHttpsSchemesAllowed(): void
    {
        $this->useDevelopment();
        self::assertTrue($this->invoke($this->service, 'isAllowedStoreUrl', ['https://pubvanacms.com/api/store/items']));
        self::assertFalse($this->invoke($this->service, 'isAllowedStoreUrl', ['ftp://pubvanacms.com/x']));
        self::assertFalse($this->invoke($this->service, 'isAllowedStoreUrl', ['file:///etc/passwd']));
    }

    public function testStoreHostAllowList(): void
    {
        self::assertTrue($this->invoke($this->service, 'isAllowedStoreUrl', ['https://pubvanacms.com/api/store/items']));
        self::assertTrue($this->invoke($this->service, 'isAllowedStoreUrl', ['https://store.pubvanacms.com/api/store/items']));
        self::assertFalse($this->invoke($this->service, 'isAllowedStoreUrl', ['https://evil.com/api/store/items']));
        self::assertFalse($this->invoke($this->service, 'isAllowedStoreUrl', ['https://pubvanacms.com.evil.com/api/store/items']));
    }

    public function testRedirectToDisallowedHostStopsWithoutFetchingIt(): void
    {
        $this->useDevelopment();
        $this->service->fetchResponses = [
            ['body' => '', 'status' => 302, 'location' => 'http://evil.com/payload'],
            ['body' => 'stolen', 'status' => 200, 'location' => null],
        ];

        $result = $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/items']);

        self::assertNull($result);
        self::assertCount(1, $this->service->fetched);
        self::assertSame('http://plugindev/api/store/items', $this->service->fetched[0]['url']);
    }

    public function testRedirectToLoopbackRejectedInProduction(): void
    {
        $this->service->fetchResponses = [
            ['body' => '', 'status' => 302, 'location' => 'http://localhost/private'],
        ];

        $result = $this->invoke($this->service, 'httpGet', ['http://pubvanacms.com/api/store/items']);

        self::assertNull($result);
        self::assertCount(1, $this->service->fetched);
    }

    public function testRelativeRedirectWithinStoreIsFollowed(): void
    {
        $this->useDevelopment();
        $this->service->fetchResponses = [
            ['body' => '', 'status' => 302, 'location' => '/api/store/items'],
            ['body' => '{"ok":true}', 'status' => 200, 'location' => null],
        ];

        $result = $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/categories']);

        self::assertSame('{"ok":true}', $result);
        self::assertSame('http://plugindev/api/store/items', $this->service->fetched[1]['url']);
    }

    public function testSchemeRelativeRedirectResolvedAgainstBaseScheme(): void
    {
        $this->useDevelopment();
        $this->service->fetchResponses = [
            ['body' => '', 'status' => 302, 'location' => '//plugindev/api/store/items'],
            ['body' => '{"ok":true}', 'status' => 200, 'location' => null],
        ];

        $result = $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/categories']);

        self::assertSame('http://plugindev/api/store/items', $this->service->fetched[1]['url']);
        self::assertSame('{"ok":true}', $result);
    }

    public function testSchemeRelativeRedirectToEvilHostRejected(): void
    {
        $this->useDevelopment();
        $this->service->fetchResponses = [
            ['body' => '', 'status' => 302, 'location' => '//evil.com/api/store/items'],
        ];

        $result = $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/items']);

        self::assertNull($result);
        self::assertCount(1, $this->service->fetched);
    }

    public function testRedirectChainIsCapped(): void
    {
        $this->useDevelopment();
        $this->service->fetchResponses = array_fill(0, 10, ['body' => '', 'status' => 302, 'location' => '/loop']);

        $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/items']);

        // MAX_REDIRECTS = 3 follows plus the terminal hop.
        self::assertLessThanOrEqual(4, count($this->service->fetched));
    }

    public function testBearerHeaderSentOnlyWhenConnected(): void
    {
        $this->useDevelopment();

        $this->service->fetchResponses = [['body' => '{}', 'status' => 200, 'location' => null]];
        $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/items']);
        self::assertSame([], $this->service->fetched[0]['headers']);

        $this->settings->data['Marketplace.account_token'] = 'abc:xyz';
        $this->service->fetchResponses = [['body' => '{}', 'status' => 200, 'location' => null]];
        $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/items']);
        self::assertSame(['Authorization: Bearer abc:xyz'], $this->service->fetched[1]['headers']);
    }

    public function testAccountTokenNeverAppearsInQueryString(): void
    {
        $this->useDevelopment();
        $this->settings->data['Marketplace.account_token'] = 'abc:xyz';

        $this->service->fetchResponses = [['body' => '{}', 'status' => 200, 'location' => null]];
        $this->invoke($this->service, 'httpGet', ['http://plugindev/api/store/items']);

        self::assertDoesNotMatchRegularExpression('/[?&]token=/', $this->service->fetched[0]['url']);
    }

    public function testApiUrlBuildsNoTokenQuery(): void
    {
        $this->settings->data['Marketplace.account_token'] = 'abc:xyz';

        self::assertSame('http://plugindev/api/store/items', $this->invoke($this->service, 'apiUrl', ['items']));
    }

    public function testSourceNeverEchoesTokenOrAutoFollowsRedirects(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Marketplace/Services/MarketplaceService.php'
        );

        self::assertStringNotContainsString('?token=', $src);
        self::assertStringNotContainsString('&token=', $src);
        self::assertStringNotContainsString('CURLOPT_FOLLOWLOCATION => true', $src);

        // One stream context for GET and one for POST; PHP defaults to
        // auto-following, so both must disable it explicitly.
        self::assertSame(2, substr_count($src, "'follow_location' => 0"));

        // Callers fixed their query separator when the token query was dropped.
        self::assertSame(1, substr_count($src, "'?currency='"));
        self::assertSame(1, substr_count($src, "'?domain='"));
        self::assertSame(1, substr_count($src, "'?slug='"));
        self::assertSame(0, substr_count($src, "'&currency='"));
    }

    private function useDevelopment(): void
    {
        $this->property($this->service, 'app')->set('environment', 'development');
    }

    // -----------------------------------------------------------------
    // Response-size caps (L2)
    // -----------------------------------------------------------------

    public function testByteCapDefaultsAndFloors(): void
    {
        // Default: the named config key is absent, so the hard default holds.
        self::assertSame(1048576, $this->invoke($this->service, 'byteCap', [null, 'max_bytes', 1048576]));

        // Zip downloads get their own default.
        self::assertSame(26214400, $this->invoke($this->service, 'byteCap', [null, 'max_zip_bytes', 26214400]));

        // An explicit value wins; a misconfigured tiny value is floored at 1KB.
        self::assertSame(5000000, $this->invoke($this->service, 'byteCap', [5000000, 'max_bytes', 1048576]));
        self::assertSame(1024, $this->invoke($this->service, 'byteCap', [10, 'max_bytes', 1048576]));
    }

    public function testByteCapReadsConfigKey(): void
    {
        $service = new HttpSafetyService(
            Sqlite::recreate(),
            $this->app(['settings' => fn (): HttpSafetySettingsStub => $this->settings]),
            ['store_url' => 'http://plugindev', 'max_bytes' => 5000, 'max_zip_bytes' => 99],
        );

        self::assertSame(5000, $this->invoke($service, 'byteCap', [null, 'max_bytes', 1048576]));
        // max(1024, 99): the floor wins over a nonsense config value.
        self::assertSame(1024, $this->invoke($service, 'byteCap', [null, 'max_zip_bytes', 26214400]));
    }

    public function testBothHttpTransportsCarryTheSizeCap(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Marketplace/Services/MarketplaceService.php'
        );

        // The capped write callback is on both the GET and POST curl paths.
        self::assertSame(2, substr_count($src, 'CURLOPT_WRITEFUNCTION'));

        // The stream fallback is capped through the shared fread-loop helper
        // (two call sites plus the definition).
        self::assertSame(3, substr_count($src, 'streamBodyWithCap('));

        // The zip download call site overrides the default cap.
        self::assertStringContainsString("'max_zip_bytes'", $src);
    }

    public function testStreamBodyCapAbandonsOversizedBody(): void
    {
        $path = $this->tempFile(str_repeat('a', 4096));
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);

        $response = $this->invoke($this->service, 'streamBodyWithCap', [$path, $context, 1024]);

        self::assertNull($response['body']);
    }

    public function testStreamBodyCapKeepsBodyWithinLimit(): void
    {
        $path = $this->tempFile(str_repeat('b', 1000));
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);

        $response = $this->invoke($this->service, 'streamBodyWithCap', [$path, $context, 1024]);

        self::assertSame(str_repeat('b', 1000), $response['body']);
    }

    public function testStreamBodyCapMissingSource(): void
    {
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);

        $response = $this->invoke($this->service, 'streamBodyWithCap', ['/nonexistent/nope', $context, 1024]);

        self::assertNull($response['body']);
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mktcap');
        self::assertNotFalse($path);
        self::assertNotFalse(file_put_contents($path, $contents));

        return $path;
    }
}

/**
 * Lightweight settings stand-in backed by an in-memory array.
 */
final class HttpSafetySettingsStub
{
    /** @var array<string, mixed> */
    public array $data = [];

    public function get(string $key, mixed $default = ''): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}

/**
 * MarketplaceService with only the single-request fetch stubbed; httpGet() runs
 * its real redirect loop so per-hop validation is exercised without the network.
 */
final class HttpSafetyService extends MarketplaceService
{
    /** @var list<array{url: string, headers: list<string>}> */
    public array $fetched = [];

    /** @var list<array{body: ?string, status: int, location: ?string}> */
    public array $fetchResponses = [];

    protected function httpFetchOnce(string $url, array $headers, int $timeout, ?int $maxBytes = null): ?array
    {
        $this->fetched[] = ['url' => $url, 'headers' => $headers];
        return array_shift($this->fetchResponses);
    }

    protected function sitePubvanaVersion(): string
    {
        return '3.0.0';
    }
}