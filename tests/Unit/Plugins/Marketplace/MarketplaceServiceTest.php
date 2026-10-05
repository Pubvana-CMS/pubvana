<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;
use Pubvana\Tests\Support\ZipFactory;

use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(MarketplaceService::class)]
final class MarketplaceServiceTest extends TestCase
{
    private SettingsStub $settings;
    private TestMarketplaceService $service;

    protected function setUp(): void
    {
        $this->settings = new SettingsStub();
        $app = $this->app([
            'settings' => fn (): SettingsStub => $this->settings,
        ]);
        $this->service = new TestMarketplaceService(
            Sqlite::recreate(),
            $app,
            ['store_url' => 'http://localhost', 'api_timeout' => 3],
        );
    }

    public function testCheckoutUrlPointsAtTheStoreCheckout(): void
    {
        self::assertSame('http://localhost/store/checkout', $this->service->checkoutUrl());
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
        $result = $this->service->connectAccount('you@example.com', 'secret123', 'secret124', 'register');

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

    public function testInstallFromPackageExplainsAnItemWithNoIdentity(): void
    {
        $result = $this->service->installFromPackage('');

        self::assertFalse($result['ok']);
        self::assertStringContainsString('package identity', (string) $result['reason']);
        self::assertStringNotContainsString('Invalid package', (string) $result['reason']);
    }

    /**
     * A store record written before the addon toggle was on has no package
     * id, only a slug. Those items install through the slug: the free
     * endpoint takes it and installFreePackage matches on either key.
     */
    public function testInstallFromPackageRoutesASlugOnlyItemThroughTheFreeEndpoint(): void
    {
        Sqlite::recreate();

        // A dedicated service: the shared one points store_url at localhost
        // without a development environment, which the download host policy
        // then refuses before the (stubbed) fetch is ever reached.
        $settings = new SettingsStub();
        $settings->data['Marketplace.account_token'] = 'abc:xyz';
        $app = $this->app(['settings' => fn (): SettingsStub => $settings]);
        $app->set('environment', 'development');

        $service = new TestMarketplaceService(
            Sqlite::connection(),
            $app,
            ['store_url' => 'http://localhost', 'api_timeout' => 3],
        );
        $service->getResponses = [
            '{"ok":true,"items":[{"id":5,"name":"Lux","slug":"lux","package":"","is_free":true,"version":"1.0.0"}]}',
            null,
        ];

        $result = $service->installFromPackage('lux');

        // The empty download stands in for the network: reaching it at all
        // proves the slug matched a catalog entry instead of being refused.
        self::assertFalse($result['ok']);
        self::assertStringContainsString('could not be downloaded', (string) $result['reason']);
        self::assertStringContainsString('slug=lux', (string) ($service->getUrls[1] ?? ''));
    }

    /**
     * Malicious stops the install. installPackage() is the shared end of a
     * paid install, a free install, and the Updates screen's addon update,
     * so one refusal here covers all three.
     */
    public function testAMaliciousVerdictRefusesAnInstallBeforeAnythingIsCopied(): void
    {
        $trust = new InstallTrustStub('malicious', 'Ships a backdoor.');
        $service = $this->installServiceWithTrust($trust);

        try {
            $result = $service->installFromPackage('pvtmp');

            self::assertFalse($result['ok']);
            self::assertStringContainsString('malicious', (string) $result['reason']);
            self::assertStringContainsString('Ships a backdoor.', (string) $result['reason']);
            self::assertSame(['pvtmp'], $trust->asked);
            self::assertDirectoryDoesNotExist(PROJECT_ROOT . '/plugins/pvtmp');
        } finally {
            $this->removeTestAddon($service);
        }
    }

    public function testACachedMaliciousVerdictRefusesWithoutALiveCall(): void
    {
        $trust = new InstallTrustStub('trusted');
        $trust->cached = ['status' => 'malicious', 'warning' => null];
        $service = $this->installServiceWithTrust($trust);

        try {
            $result = $service->installFromPackage('pvtmp');

            self::assertFalse($result['ok']);
            self::assertStringContainsString('malicious', (string) $result['reason']);
            self::assertSame([], $trust->asked);
            self::assertDirectoryDoesNotExist(PROJECT_ROOT . '/plugins/pvtmp');
        } finally {
            $this->removeTestAddon($service);
        }
    }

    public function testATrustedVerdictLetsTheInstallRun(): void
    {
        $trust = new InstallTrustStub('trusted');
        $service = $this->installServiceWithTrust($trust);

        try {
            $result = $service->installFromPackage('pvtmp');

            self::assertTrue($result['ok']);
            self::assertFileExists(PROJECT_ROOT . '/plugins/pvtmp/pubvana.json');
        } finally {
            $this->removeTestAddon($service);
        }
    }

    /**
     * A service whose app carries a trust stand-in, with one free catalog
     * item and a real zip queued behind it for the download.
     */
    private function installServiceWithTrust(InstallTrustStub $trust): TestMarketplaceService
    {
        Sqlite::recreate();

        $settings = new SettingsStub();
        $settings->data['Marketplace.account_token'] = 'abc:xyz';

        $app = $this->app([
            'settings'    => fn (): SettingsStub => $settings,
            'trustClient' => fn (): InstallTrustStub => $trust,
        ]);
        $app->set('environment', 'development');

        $manifest = ['name' => 'pubvana/pvtmp', 'type' => 'plugin', 'semver' => '1.0.0'];
        $zipPath = sys_get_temp_dir() . '/pv-mkt-trust-' . uniqid() . '.zip';
        ZipFactory::write($zipPath, [
            ['name' => 'pvtmp/pubvana.json', 'content' => (string) json_encode($manifest), 'mode' => 0100644],
            ['name' => 'pvtmp/Plugin.php', 'content' => '<?php', 'mode' => 0100644],
        ]);
        $zipBytes = (string) file_get_contents($zipPath);
        @unlink($zipPath);

        $catalog = (string) json_encode([
            'ok'    => true,
            'items' => [
                ['id' => 5, 'name' => 'Tmp', 'slug' => 'pvtmp', 'package' => 'pvtmp', 'is_free' => true, 'version' => '1.0.0'],
            ],
        ]);

        $service = new TestMarketplaceService(
            Sqlite::connection(),
            $app,
            ['store_url' => 'http://localhost', 'api_timeout' => 3],
        );
        $service->getResponses = [$catalog, $zipBytes];

        return $service;
    }

    private function removeTestAddon(TestMarketplaceService $service): void
    {
        $dest = PROJECT_ROOT . '/plugins/pvtmp';
        if (is_dir($dest)) {
            $this->invoke($service, 'rmdir', [$dest]);
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

    /** @var list<string> Every GET URL, in order. */
    public array $getUrls = [];

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
        $this->getUrls[] = $url;
        return array_shift($this->getResponses);
    }

    protected function userAgent(): string
    {
        return 'Pubvana-Marketplace-Test';
    }
}

/**
 * Trust client stand-in for the install gate. packageItem() answers a fixed
 * identity; the two lookups answer the configured verdicts.
 */
final class InstallTrustStub
{
    /** @var list<string> Slugs asked with a live call. */
    public array $asked = [];

    /** @var array{status: string, warning: ?string}|null */
    public ?array $cached = null;

    public function __construct(
        private string $liveStatus = 'unknown',
        private ?string $liveWarning = null,
    ) {
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array{type: string, slug: string, version: string, author: string, origin: string}
     */
    public function packageItem(array $manifest): array
    {
        return [
            'type'    => 'plugin',
            'slug'    => 'pvtmp',
            'version' => '1.0.0',
            'author'  => 'pubvana',
            'origin'  => 'local',
        ];
    }

    /**
     * @return array{status: string, warning: ?string, checked_at: string}|null
     */
    public function getCachedStatus(string $type, string $slug, string $version, string $author): ?array
    {
        return $this->cached === null ? null : $this->cached + ['checked_at' => '2026-10-04 00:00:00'];
    }

    /**
     * @return array{status: string, warning: ?string, answered: bool}
     */
    public function checkAddon(string $type, string $slug, string $version, string $author, string $origin = 'local'): array
    {
        $this->asked[] = $slug;

        return ['status' => $this->liveStatus, 'warning' => $this->liveWarning, 'answered' => true];
    }
}