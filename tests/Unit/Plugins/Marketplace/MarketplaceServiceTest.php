<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Marketplace\Models\MarketplaceInstall;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use Pubvana\Tests\Support\ZipFactory;

use function array_diff;
use function array_values;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(MarketplaceService::class)]
final class MarketplaceServiceTest extends TestCase
{
    private SettingsStub $settings;
    private TestMarketplaceService $service;
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->settings = new SettingsStub();
        $app = $this->app([
            'settings' => fn (): SettingsStub => $this->settings,
        ]);
        $this->pdo = Sqlite::recreate();
        MarketplaceSchema::create($this->pdo);
        $this->service = new TestMarketplaceService(
            $this->pdo,
            $app,
            ['store_url' => 'http://localhost', 'api_timeout' => 3],
        );
    }

    public function testConnectAccountRejectsInvalidEmailWithoutCallingStore(): void
    {
        $result = $this->service->connectAccount('not-an-email', 'secret123', 'secret123');

        self::assertFalse($result['ok']);
        self::assertSame([], $this->service->sent);
        self::assertFalse($this->service->connected());
    }

    public function testConnectAccountRequiresPassword(): void
    {
        $result = $this->service->connectAccount('you@example.com', '', 'secret123');

        self::assertFalse($result['ok']);
        self::assertSame([], $this->service->sent);
    }

    public function testConnectAccountRequiresMatchingConfirmation(): void
    {
        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret124');

        self::assertFalse($result['ok']);
        self::assertSame([], $this->service->sent);
    }

    public function testConnectAccountSendsEmailPasswordAndPasswordConf(): void
    {
        $this->service->responses = ['{"ok":true,"token":"abc:xyz"}'];

        $result = $this->service->connectAccount('You@Example.com', 'secret123', 'secret123');

        self::assertTrue($result['ok']);
        self::assertCount(1, $this->service->sent);
        $sent = $this->service->sent[0];
        self::assertSame('http://localhost/api/store/auth/token', $sent['url']);
        self::assertSame('you@example.com', $sent['payload']['email']);
        self::assertSame('secret123', $sent['payload']['password']);
        self::assertSame('secret123', $sent['payload']['password_conf']);
    }

    public function testConnectAccountStoresTokenOnSuccess(): void
    {
        $this->service->responses = ['{"ok":true,"token":"abc:xyz"}'];

        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret123');

        self::assertTrue($result['ok']);
        self::assertSame('abc:xyz', $this->settings->data['Marketplace.account_token']);
        self::assertSame('you@example.com', $this->settings->data['Marketplace.account_email']);
        self::assertTrue($this->service->connected());
    }

    public function testConnectAccountSurfacesStoreReason(): void
    {
        $this->service->responses = ['{"ok":false,"reason":"The email or password is incorrect. Reset your password at the store if you forgot it."}'];

        $result = $this->service->connectAccount('you@example.com', 'wrong', 'wrong');

        self::assertFalse($result['ok']);
        self::assertStringContainsString('incorrect', (string) $result['reason']);
        self::assertFalse($this->service->connected());
    }

    public function testConnectAccountSurfacesActivationRequired(): void
    {
        $this->service->responses = ['{"ok":true,"activation_required":true}'];

        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret123');

        self::assertFalse($result['ok']);
        self::assertStringContainsString('activation', (string) $result['reason']);
        self::assertFalse($this->service->connected());
    }

    public function testConnectAccountReportsUnreachableStore(): void
    {
        $this->service->responses = [null];

        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret123');

        self::assertFalse($result['ok']);
        self::assertStringContainsString('could not be reached', (string) $result['reason']);
    }

    public function testItemsCarryLicenseScopeForDisclosure(): void
    {
        $this->settings->data['Marketplace.account_token'] = 'abc:xyz';
        $this->service->getResponses = [
            '{"ok":true,"items":[{"id":7,"name":"Demo","is_free":false,"license_scope":"single_site","price":19.0,"currency":"USD","item_type":"plugin"}]}',
        ];

        $items = $this->service->items('USD');

        self::assertCount(1, $items);
        self::assertSame('single_site', $items[0]['license_scope']);
        self::assertFalse($items[0]['is_free']);
    }

    // -----------------------------------------------------------------
    // License domain + verification
    // -----------------------------------------------------------------

    public function testSiteDomainUsesTheSettingsValueAndDropsThePort(): void
    {
        $this->property($this->service, 'app')->set('CMS.siteUrl', 'http://env.example');
        $this->settings->data['CMS.siteUrl'] = 'https://www.Example.com:8443/blog';

        self::assertSame('example.com', $this->invoke($this->service, 'siteDomain'));
    }

    public function testSiteDomainFallsBackToLocalhostWithoutTheHostHeader(): void
    {
        // Nothing in the settings store; the app-level value must not be used.
        $this->property($this->service, 'app')->set('CMS.siteUrl', 'http://spoofed.example');

        self::assertSame('localhost', $this->invoke($this->service, 'siteDomain'));
    }

    public function testVerifyPurchasesReportsNotConnected(): void
    {
        $result = $this->service->verifyPurchases();

        self::assertFalse($result['ok']);
        self::assertSame([], $result['purchases']);
        self::assertStringContainsString('Not connected', $result['reason']);
        self::assertSame([], $this->service->getResponses);
    }

    public function testVerifyPurchasesReportsStoreFailure(): void
    {
        $this->settings->data['Marketplace.account_token'] = 'abc:xyz';
        $this->service->getResponses = [null];

        $result = $this->service->verifyPurchases();

        self::assertFalse($result['ok']);
        self::assertSame([], $result['purchases']);
        self::assertStringContainsString('could not be reached', $result['reason']);
    }

    public function testVerifyPurchasesReconcilesAndReportsSuccess(): void
    {
        $this->settings->data['Marketplace.account_token'] = 'abc:xyz';
        $this->settings->data['CMS.siteUrl'] = 'https://example.com';
        $this->service->getResponses = [
            '{"ok":true,"purchases":[{"product_id":7,"name":"Demo","license_key":"LIC-7","licensed":1,"scope":"single_site","expires":"2027-01-01"}]}',
        ];

        $result = $this->service->verifyPurchases();

        self::assertTrue($result['ok']);
        self::assertSame('', $result['reason']);
        self::assertCount(1, $result['purchases']);

        $row = (new MarketplaceInstall($this->pdo))->findByProductId(7);
        self::assertNotNull($row);
        self::assertSame('example.com', (string) $row->registered_domain);
        self::assertSame(1, (int) $row->license_valid);
    }

    // -----------------------------------------------------------------
    // Extraction staging
    // -----------------------------------------------------------------

    public function testPrepareExtractDirCreatesAMissingDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/pv-mkt-extract-new-' . uniqid();

        try {
            self::assertTrue($this->invoke($this->service, 'prepareExtractDir', [$dir]));
            self::assertTrue(is_dir($dir));
        } finally {
            @rmdir($dir);
        }
    }

    public function testPrepareExtractDirClearsLeftovers(): void
    {
        $dir = sys_get_temp_dir() . '/pv-mkt-extract-' . uniqid();
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/stale.php', '<?php // stale');
        mkdir($dir . '/stale-dir', 0755, true);

        try {
            self::assertTrue($this->invoke($this->service, 'prepareExtractDir', [$dir]));
            self::assertTrue(is_dir($dir));
            self::assertSame([], array_values(array_diff(scandir($dir) ?: [], ['.', '..'])));
        } finally {
            @unlink($dir . '/stale.php');
            @rmdir($dir . '/stale-dir');
            @rmdir($dir);
        }
    }

    public function testZipEntriesAreSafeRejectsSymlinkEntries(): void
    {
        $zipPath = sys_get_temp_dir() . '/pv-mkt-sym-' . uniqid() . '.zip';
        ZipFactory::write($zipPath, [
            ['name' => 'link', 'content' => '../../victim', 'mode' => 0120777],
            ['name' => 'pkg/pubvana.json', 'content' => '{}', 'mode' => 0100644],
        ]);

        $archive = new \ZipArchive();
        try {
            if ($archive->open($zipPath) !== true) {
                self::fail('test zip could not be opened');
            }

            self::assertFalse($this->invoke($this->service, 'zipEntriesAreSafe', [$archive]));
            $archive->close();
        } finally {
            @unlink($zipPath);
        }
    }

    public function testZipEntriesAreSafeAcceptsPlainEntries(): void
    {
        $zipPath = sys_get_temp_dir() . '/pv-mkt-plain-' . uniqid() . '.zip';
        ZipFactory::write($zipPath, [
            ['name' => 'pkg/pubvana.json', 'content' => '{}', 'mode' => 0100644],
            ['name' => 'pkg/src/', 'content' => '', 'mode' => 040755],
        ]);

        $archive = new \ZipArchive();
        try {
            if ($archive->open($zipPath) !== true) {
                self::fail('test zip could not be opened');
            }

            self::assertTrue($this->invoke($this->service, 'zipEntriesAreSafe', [$archive]));
            $archive->close();
        } finally {
            @unlink($zipPath);
        }
    }
}

/**
 * Lightweight settings stand-in backed by an in-memory array.
 */
final class SettingsStub
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
 * MarketplaceService with HTTP methods stubbed so tests never hit the network.
 */
final class TestMarketplaceService extends MarketplaceService
{
    /** @var list<array{url: string, payload: array<string, mixed>}> */
    public array $sent = [];

    /** @var list<?string> */
    public array $responses = [];

    /** @var list<?string> */
    public array $getResponses = [];

    public function coreSemverForTest(): string
    {
        return '3.0.0';
    }

    protected function sitePubvanaVersion(): string
    {
        return $this->coreSemverForTest();
    }

    protected function httpPostJson(string $url, array $payload, ?int $maxBytes = null): ?string
    {
        $this->sent[] = ['url' => $url, 'payload' => $payload];
        return array_shift($this->responses);
    }

    protected function httpGet(string $url, ?int $maxBytes = null): ?string
    {
        return array_shift($this->getResponses);
    }

    protected function userAgent(): string
    {
        return 'Pubvana-Marketplace-Test';
    }
}