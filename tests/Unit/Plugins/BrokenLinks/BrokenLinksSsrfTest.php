<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\BrokenLinks;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * H3: BrokenLinks SSRF.
 *
 * Outbound checks must vet the target before connecting: only http/https,
 * resolved addresses must be public (block private/loopback/link-local incl.
 * cloud metadata), redirects are followed by hand with every hop re-vetted
 * (no CURLOPT_FOLLOWLOCATION), vetted addresses are pinned via CURLOPT_RESOLVE
 * so curl cannot re-resolve, a connect-time veto covers hosts PHP cannot
 * resolve, and response bodies are capped. A weak-mode escape hatch exists for
 * hosts with no DNS functions and libcurl older than 7.80.0.
 */
#[CoversClass(BrokenLinksService::class)]
final class BrokenLinksSsrfTest extends TestCase
{
    private SsrfBrokenLinksService $service;

    protected function setUp(): void
    {
        $app = $this->app([
            'settings' => fn () => new class {
                public function get(string $key, mixed $default = ''): mixed
                {
                    return '/var/www/html';
                }
            },
        ]);

        $this->service = new SsrfBrokenLinksService(
            Sqlite::recreate(),
            $app,
            ['timeout' => 3, 'max_redirects' => 5, 'max_bytes' => 1024],
        );
        $this->service->prereqSupported = false;
        $this->service->dnsMap = [
            'example.com'   => ['93.184.216.34'],
            'example.org'   => ['93.184.216.34'],
            'public.addr'   => ['8.8.8.8'],
            'private.local' => ['192.168.1.10'],
            'loopback.local' => ['127.0.0.1'],
        ];
    }

    public function testBlockedAddressRangesRejectedAndPublicAccepted(): void
    {
        $blocked = [
            '0.0.0.0',
            '10.0.0.5',
            '100.64.0.1',
            '127.0.0.1',
            '169.254.169.254',
            '172.16.3.4',
            '192.168.1.1',
            '192.0.2.10',
            '198.18.0.9',
            '198.51.100.7',
            '203.0.113.7',
            '224.0.0.1',
            '240.0.0.1',
            '255.255.255.255',
            '::',
            '::1',
            '::ffff:10.0.0.5',
            '64:ff9b::1',
            'fc00::1',
            'fe80::1',
            'fec0::1',
            'ff00::1',
            '2001:db8::1',
        ];

        foreach ($blocked as $ip) {
            self::assertFalse(
                $this->invoke($this->service, 'isPublicIp', [$ip]),
                $ip . ' should be blocked'
            );
        }

        $public = ['8.8.8.8', '93.184.216.34', '1.2.3.4', '::ffff:8.8.8.8', '2606:4700:4700::1111'];
        foreach ($public as $ip) {
            self::assertTrue(
                $this->invoke($this->service, 'isPublicIp', [$ip]),
                $ip . ' should be allowed'
            );
        }
    }

    public function testResolveSafeHostVetsEveryResolvedAddress(): void
    {
        self::assertSame(
            ['93.184.216.34'],
            $this->invoke($this->service, 'resolveSafeHost', ['http://example.com/a'])
        );
        self::assertNull($this->invoke($this->service, 'resolveSafeHost', ['http://private.local/a']));
        self::assertNull($this->invoke($this->service, 'resolveSafeHost', ['http://loopback.local/a']));
        self::assertNull($this->invoke($this->service, 'resolveSafeHost', ['http://unmapped.test/a']));
        self::assertNull($this->invoke($this->service, 'resolveSafeHost', ['ftp://example.com/a']));
    }

    public function testPrivateTargetRefusedWhenNoConnectTimeVetting(): void
    {
        // prereqSupported is false in setUp, so a private/unresolvable target
        // must be refused before any request is attempted.
        $result = $this->service->checkUrl('http://private.local/a');

        self::assertNull($result['status']);
        self::assertIsString($result['error']);
        self::assertStringContainsString('Cannot verify the target address', (string) $result['error']);
        self::assertSame([], $this->service->requests);
    }

    public function testUnresolvableHostRefusedWhenNoConnectTimeVetting(): void
    {
        $result = $this->service->checkUrl('http://unmapped.test/a');

        self::assertNull($result['status']);
        self::assertStringContainsString('Cannot verify the target address', (string) $result['error']);
        self::assertSame([], $this->service->requests);
    }

    public function testPrivateTargetEnabledViaConnectTimeVettingIsNotPinned(): void
    {
        $this->service->prereqSupported = true;
        $this->service->responses = [['status' => 200, 'location' => null]];
        $this->service->dnsMap['private.local'] = ['192.168.1.10'];

        $result = $this->service->checkUrl('http://private.local/a');

        self::assertSame(200, $result['status']);
        self::assertCount(1, $this->service->requests);
        self::assertSame('http://private.local/a', $this->service->requests[0]['url']);
        // No pin: vetting is delegated to the connect-time callback.
        self::assertSame([], $this->service->requests[0]['resolve']);
    }

    public function testSchemeEnforcedEvenInWeakMode(): void
    {
        $this->service->configure(['verify_targets' => false]);
        $this->service->responses = [['status' => 404, 'location' => null]];

        $result = $this->service->checkUrl('ftp://example.com/a');

        self::assertNull($result['status']);
        self::assertStringContainsString('http and https', (string) $result['error']);
        self::assertSame([], $this->service->requests);
    }

    public function testWeakModeShipsUnvettedRequestToSeam(): void
    {
        $this->service->configure(['verify_targets' => false]);
        $this->service->responses = [['status' => 404, 'location' => null]];

        $result = $this->service->checkUrl('http://8.8.8.8/a');

        self::assertSame(404, $result['status']);
        self::assertCount(1, $this->service->requests);
        self::assertSame([], $this->service->requests[0]['resolve']);
    }

    public function testPublicTargetPinnedToVettedAddress(): void
    {
        $this->service->responses = [['status' => 200, 'location' => null]];

        $result = $this->service->checkUrl('http://example.com/a');

        self::assertSame(200, $result['status']);
        self::assertSame(['93.184.216.34'], $this->service->requests[0]['resolve']);
    }

    public function testHead405FallsBackToGet(): void
    {
        $this->service->responses = [
            ['status' => 405, 'location' => null],
            ['status' => 200, 'location' => null],
        ];

        $result = $this->service->checkUrl('http://example.com/a');

        self::assertSame(200, $result['status']);
        self::assertSame(['HEAD', 'GET'], array_column($this->service->requests, 'method'));
    }

    public function testRedirectFollowedAndEachHopReVettedAndPinned(): void
    {
        $this->service->responses = [
            ['status' => 302, 'location' => 'http://example.org/next'],
            ['status' => 200, 'location' => null],
        ];

        $result = $this->service->checkUrl('http://example.com/start');

        self::assertSame(200, $result['status']);
        self::assertCount(2, $this->service->requests);
        self::assertSame('http://example.com/start', $this->service->requests[0]['url']);
        self::assertSame('http://example.org/next', $this->service->requests[1]['url']);
        self::assertSame(['93.184.216.34'], $this->service->requests[1]['resolve']);
    }

    public function testRelativeRedirectResolvedBeforeNextHop(): void
    {
        $this->service->responses = [
            ['status' => 302, 'location' => '/next'],
            ['status' => 200, 'location' => null],
        ];

        $result = $this->service->checkUrl('http://example.com/a/b');

        self::assertSame(200, $result['status']);
        self::assertSame('http://example.com/next', $this->service->requests[1]['url']);
    }

    public function testRedirectToPrivateHostStopsBeforeFetchingIt(): void
    {
        $this->service->responses = [
            ['status' => 302, 'location' => 'http://private.local/payload'],
        ];

        $result = $this->service->checkUrl('http://example.com/start');

        self::assertNull($result['status']);
        self::assertStringContainsString('Cannot verify the target address', (string) $result['error']);
        self::assertCount(1, $this->service->requests);
    }

    public function testRedirectWithoutLocationReportsError(): void
    {
        $this->service->responses = [['status' => 302, 'location' => null]];

        $result = $this->service->checkUrl('http://example.com/start');

        self::assertNull($result['status']);
        self::assertStringContainsString('Location', (string) $result['error']);
    }

    public function testRedirectChainCapped(): void
    {
        $this->service->responses = array_fill(0, 10, ['status' => 302, 'location' => '/loop']);

        $result = $this->service->checkUrl('http://example.com/start');

        self::assertNull($result['status']);
        self::assertStringContainsString('Too many redirects', (string) $result['error']);
        // max_redirects = 5 allows the initial request plus five hops.
        self::assertSame(6, count($this->service->requests));
    }

    public function testSourceNoFollowLocationAndUsesVettingSeam(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/BrokenLinks/Services/BrokenLinksService.php'
        );

        self::assertStringNotContainsString('CURLOPT_FOLLOWLOCATION, true', $src);
        self::assertStringNotContainsString('CURLOPT_MAXREDIRS', $src);
        self::assertStringContainsString('CURLOPT_RESOLVE', $src);
        self::assertStringContainsString('CURLOPT_WRITEFUNCTION', $src);
        self::assertStringContainsString('CURLOPT_PREREQFUNCTION', $src);
    }

    public function testConfigDeclaresVettingKeys(): void
    {
        $config = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/BrokenLinks/Config/Config.php'
        );

        self::assertStringContainsString("'verify_targets'", $config);
        self::assertStringContainsString("'max_bytes'", $config);
    }
}

/**
 * BrokenLinksService with DNS and the single-hop network call stubbed. All
 * safety vetting in performRequest() and the redirect loop in doHttpRequest()
 * run for real, so per-hop policy and pinning are exercised without a network.
 */
final class SsrfBrokenLinksService extends BrokenLinksService
{
    /** @var array<string, list<string>> */
    public array $dnsMap = [];

    public bool $prereqSupported = true;

    /** @var list<array{method: string, url: string, resolve: list<string>}> */
    public array $requests = [];

    /** @var list<array{status: int, location: ?string}> */
    public array $responses = [];

    protected function resolveHostIps(string $host): array
    {
        return $this->dnsMap[strtolower($host)] ?? [];
    }

    protected function supportsPrereqVetting(): bool
    {
        return $this->prereqSupported;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): void
    {
        $this->config = array_replace($this->config, $config);
    }

    /**
     * @param list<string> $pinned
     */
    protected function singleRequest(string $method, string $url, int $timeout, string $userAgent, array $pinned, bool $prereq): array
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'resolve' => $pinned];
        return array_shift($this->responses) ?? ['status' => 404, 'location' => null];
    }
}