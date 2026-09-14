<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\Controllers\UpdatesAdminController;
use Pubvana\Plugins\Updates\Services\UpdateProgress;
use Pubvana\Plugins\Updates\Services\UpdateService;
use Pubvana\Tests\Support\TestCase;

use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

use const JSON_THROW_ON_ERROR;

/**
 * UpdatesAdminController over a canned UpdateService subclass.
 *
 * apply() happy paths shell out to a background runway process, so only
 * the early JSON branches (lock, no target, breaking confirm, trust
 * refusal) are exercised here; the exec and sync paths belong to the
 * UpdateApplyService tests.
 */
#[CoversClass(UpdatesAdminController::class)]
final class UpdatesAdminControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var list<array<string, mixed>> */
    public array $jsons = [];
    /** @var list<mixed> */
    public array $halts = [];
    public ?FakeUpdates $updates = null;
    public FakeTrustClient $trust;
    public ?FakeMarketplace $marketplace = null;
    public bool $canManage = false;
    public ?string $lockedDir = null;
    /** @var list<string> */
    public array $tempDirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->jsons = [];
        $this->trust = new FakeTrustClient();
        $this->canManage = true;
        $this->lockedDir = null;
    }

    protected function tearDown(): void
    {
        if ($this->lockedDir !== null && is_file($this->lockedDir . '/operation.lock')) {
            @unlink($this->lockedDir . '/operation.lock');
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        }
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // guard()
    // ------------------------------------------------------------------

    public function testGuardRefusesWithoutPermission(): void
    {
        $this->canManage = false;
        (new UpdatesAdminController($this->engine()))->check();

        self::assertSame('You do not have permission to manage updates.', $this->flashes['error'][0]);
        self::assertSame(['/admin'], $this->redirects);
        self::assertSame([], $this->fetches);
    }

    // ------------------------------------------------------------------
    // check()
    // ------------------------------------------------------------------

    public function testCheckFlashesByStatusAndRechecksTrust(): void
    {
        $updates = $this->updates();
        $updates->checkResult = ['status' => 'available', 'target_version' => '3.1.0'];

        (new UpdatesAdminController($this->engine()))->check();

        self::assertSame('Version 3.1.0 is available.', $this->flashes['info'][0]);
        self::assertTrue($this->trust->rechecked);
        self::assertSame(['/admin/updates'], $this->redirects);

        $this->flashes = [];
        $this->redirects = [];
        $this->trust->rechecked = false;
        $updates->checkResult = ['status' => 'up_to_date'];
        (new UpdatesAdminController($this->engine()))->check();
        self::assertSame('You are running the latest version.', $this->flashes['success'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $updates->checkResult = ['status' => 'error', 'error' => 'feed down'];
        (new UpdatesAdminController($this->engine()))->check();
        self::assertSame('Check failed: feed down', $this->flashes['danger'][0]);
    }

    public function testCheckSurvivesTrustOutage(): void
    {
        $updates = $this->updates();
        $updates->checkResult = ['status' => 'available', 'target_version' => '3.1.0'];
        $app = $this->engine();
        $app->map('trustClient', static function (): object {
            throw new \RuntimeException('trust down');
        });

        (new UpdatesAdminController($app))->check();

        self::assertSame('Version 3.1.0 is available.', $this->flashes['info'][0]);
    }

    // ------------------------------------------------------------------
    // apply()
    // ------------------------------------------------------------------

    public function testApplyRefusesWhenLocked(): void
    {
        $this->lock();

        (new UpdatesAdminController($this->engine()))->apply();

        self::assertSame([['status' => 'error', 'message' => 'An update or backup operation is already in progress.']], $this->jsons);
    }

    public function testApplyRefusesWithoutTarget(): void
    {
        $this->updates()->lastCheckResult = [];

        (new UpdatesAdminController($this->engine()))->apply();

        self::assertSame([['status' => 'error', 'message' => 'No update is available to apply.']], $this->jsons);
    }

    public function testApplyAsksForBreakingConfirmation(): void
    {
        $this->updates()->lastCheckResult = [
            'target_version' => '3.1.0',
            'breaking_changes' => ['thing changed'],
        ];

        (new UpdatesAdminController($this->engine()))->apply();

        self::assertSame([[
            'status' => 'confirm_breaking',
            'message' => 'This update path contains breaking changes. Review them and confirm to apply.',
        ]], $this->jsons);
    }

    public function testApplyRefusesCachedMaliciousVerdict(): void
    {
        $this->updates()->lastCheckResult = ['target_version' => '3.1.0'];
        $this->trust->cached = ['status' => 'malicious', 'warning' => 'bad code', 'checked_at' => '2026-01-01'];

        (new UpdatesAdminController($this->engine()))->apply();

        self::assertSame([['status' => 'error', 'message' => 'Update has been found malicious by the Pubvana trust service']], $this->jsons);
    }

    // ------------------------------------------------------------------
    // status() / settings()
    // ------------------------------------------------------------------

    public function testStatusEmitsProgressOrIdle(): void
    {
        $this->lockedDir = $this->tempDir();
        $this->updates()->config['updates_path'] = $this->lockedDir;

        (new UpdatesAdminController($this->engine()))->status();
        self::assertSame([['status' => 'idle']], $this->jsons);

        $this->jsons = [];
        file_put_contents($this->lockedDir . '/update_progress.json', '{"status":"in_progress","step":2}');

        (new UpdatesAdminController($this->engine()))->status();
        self::assertSame([['status' => 'in_progress', 'step' => 2]], $this->jsons);
    }

    public function testSettingsSavesAutoUpdate(): void
    {
        (new UpdatesAdminController($this->engine(data: ['auto_update' => '1', '_csrf_token' => 'x'])))->settings();

        self::assertTrue($this->updates()->autoSaved);
        self::assertSame([['status' => 'ok', 'message' => 'Update settings saved.']], $this->jsons);

        $this->updates()->autoSaved = null;
        (new UpdatesAdminController($this->engine(data: [])))->settings();
        self::assertFalse($this->updates()->autoSaved);
    }

    // ------------------------------------------------------------------
    // skip() / unskip()
    // ------------------------------------------------------------------

    public function testSkipValidAndInvalid(): void
    {
        (new UpdatesAdminController($this->engine(data: ['version' => '3.1.0'])))->skip();

        self::assertSame('3.1.0', $this->updates()->skipped);
        self::assertTrue($this->updates()->checked);
        self::assertSame('Version 3.1.0 skipped. The next applicable release will be offered instead.', $this->flashes['success'][0]);
        self::assertSame(['/admin/updates'], $this->redirects);

        $this->flashes = [];
        $this->redirects = [];
        $this->updates()->skipped = null;
        (new UpdatesAdminController($this->engine(data: ['version' => 'bad version!'])))->skip();
        self::assertSame('Invalid version to skip.', $this->flashes['danger'][0]);
        self::assertNull($this->updates()->skipped);
    }

    public function testUnskip(): void
    {
        (new UpdatesAdminController($this->engine(data: ['version' => '3.1.0'])))->unskip();

        self::assertSame('3.1.0', $this->updates()->unskipped);
        self::assertSame('Version 3.1.0 will be offered again.', $this->flashes['success'][0]);

        $this->flashes = [];
        (new UpdatesAdminController($this->engine(data: ['version' => ''])))->unskip();
        self::assertArrayNotHasKey('success', $this->flashes);
    }

    // ------------------------------------------------------------------
    // addonUpdate() / addonCheck() / addonUpdateAll()
    // ------------------------------------------------------------------

    public function testAddonUpdateRequiresPackage(): void
    {
        (new UpdatesAdminController($this->engine()))->addonUpdate();

        self::assertSame('No package given for the update.', $this->flashes['danger'][0]);
        self::assertSame(['/admin/updates'], $this->redirects);
    }

    public function testAddonUpdateSuccessFailureAndOutage(): void
    {
        $this->marketplaceInstallResults = ['pubvana/blog' => ['ok' => true, 'version' => '2.0.0', 'reason' => '']];
        (new UpdatesAdminController($this->engine(data: ['package' => 'pubvana/blog'])))->addonUpdate();
        self::assertSame('Package updated to version 2.0.0.', $this->flashes['success'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->marketplaceInstallResults = ['pubvana/blog' => ['ok' => false, 'reason' => 'not a verified purchase']];
        (new UpdatesAdminController($this->engine(data: ['package' => 'pubvana/blog'])))->addonUpdate();
        self::assertSame('not a verified purchase', $this->flashes['danger'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $app = $this->engine(data: ['package' => 'pubvana/blog']);
        $app->map('marketplace', static function (): object {
            throw new \RuntimeException('missing');
        });
        (new UpdatesAdminController($app))->addonUpdate();
        self::assertSame('The Marketplace is not available: missing', $this->flashes['danger'][0]);
    }

    public function testAddonCheck(): void
    {
        (new UpdatesAdminController($this->engine()))->addonCheck();

        self::assertTrue($this->marketplaceRefreshed);
        self::assertSame('Checked the Marketplace catalog for addon updates.', $this->flashes['success'][0]);

        $this->flashes = [];
        $app = $this->engine();
        $app->map('marketplace', static function (): object {
            throw new \RuntimeException('missing');
        });
        (new UpdatesAdminController($app))->addonCheck();
        self::assertSame('The Marketplace is not available: missing', $this->flashes['danger'][0]);
    }

    public function testAddonUpdateAllWithNoUpdates(): void
    {
        $this->marketplaceUpdates = [];

        (new UpdatesAdminController($this->engine()))->addonUpdateAll();

        self::assertSame('No addon updates are available.', $this->flashes['info'][0]);
        self::assertSame(['/admin/updates'], $this->redirects);
    }

    public function testAddonUpdateAllAppliesPackages(): void
    {
        $this->marketplaceUpdates = ['pubvana/blog' => ['latest_version' => '2.0.0']];
        $this->marketplaceInstallResults = [
            'pubvana/blog' => ['ok' => true, 'reason' => 'Installed.', 'version' => null],
        ];

        (new UpdatesAdminController($this->engine()))->addonUpdateAll();

        self::assertSame('Updated 1 package.', $this->flashes['success'][0]);

        // Mixed result: one failure surfaces the package and reason.
        $this->flashes = [];
        $this->redirects = [];
        $this->marketplaceUpdates = [
            'pubvana/blog' => ['latest_version' => '2.0.0'],
            'pubvana/forms' => ['latest_version' => '1.2.0'],
        ];
        $this->marketplaceInstallResults = [
            'pubvana/blog' => ['ok' => true, 'reason' => 'Installed.', 'version' => null],
            'pubvana/forms' => ['ok' => false, 'reason' => 'no license'],
        ];
        (new UpdatesAdminController($this->engine()))->addonUpdateAll();
        self::assertSame('Updated 1 of 2 packages. Failed: pubvana/forms (no license)', $this->flashes['warning'][0]);
    }

    // ------------------------------------------------------------------
    // internals
    // ------------------------------------------------------------------

    private function lock(): void
    {
        $dir = $this->tempDir();
        file_put_contents($dir . '/operation.lock', '{"operation":"update"}');
        $this->lockedDir = $dir;
        $this->updates()->config['updates_path'] = $dir;
    }

    private function tempDir(): string
    {
        $dir = sys_get_temp_dir() . '/pv-updates-' . uniqid();
        mkdir($dir, 0775, true);

        return $dir;
    }

    private function updates(): FakeUpdates
    {
        if ($this->updates === null) {
            $this->updates = new FakeUpdates($this->bareEngine());
        }

        return $this->updates;
    }

    private function bareEngine(): Engine
    {
        $app = new Engine();
        $app->init();

        return $app;
    }

    public bool $marketplaceRefreshed = false;
    /** @var array<string, array<string, mixed>> */
    public array $marketplaceUpdates = [];
    /** @var array<string, array<string, mixed>> */
    public array $marketplaceInstallResults = [];

    private function engine(array $data = []): Engine
    {
        $test = $this;
        $updates = $this->updates();
        $trust = $this->trust;
        $dir = $this->lockedDir;
        $app = $this->app([
            'request' => static fn(): object => new class($data) {
                public Collection $data;
                /** @param array<string, mixed> $d */
                public function __construct(array $d)
                {
                    $this->data = new Collection($d);
                }
            },
            'updates' => static fn(): UpdateService => $updates,
            'trustClient' => static fn(): FakeTrustClient => $trust,
            'auth' => function () use ($test): object {
                return new class($test) {
                    public function __construct(private UpdatesAdminControllerTest $t)
                    {
                    }

                    public function user(): ?object
                    {
                        if (!$this->t->canManage) {
                            return null;
                        }

                        return new class($this->t) {
                            public function __construct(private UpdatesAdminControllerTest $t)
                            {
                            }

                            public function can(string $permission): bool
                            {
                                return $this->t->canManage;
                            }

                            /** @return list<string> */
                            public function getGroups(): array
                            {
                                return ['admin'];
                            }

                            public string $username = 'admin';
                        };
                    }
                };
            },
            'pluginLoader' => static fn(): object => new class {
                /** @return array<string, array<string, mixed>> */
                public function discoverLocal(): array
                {
                    return [];
                }

                /** @return array<string, array<string, mixed>> */
                public function discoverVendor(): array
                {
                    return [];
                }

                public function routePrefix(string $id): string
                {
                    return '/updates';
                }
            },
            'marketplace' => function () use ($test): object {
                return new class($test) {
                    public function __construct(private UpdatesAdminControllerTest $t)
                    {
                    }

                    public function refreshCatalog(): void
                    {
                        $this->t->marketplaceRefreshed = true;
                    }

                    /** @return array<string, array<string, mixed>> */
                    public function checkAddonUpdates(): array
                    {
                        return $this->t->marketplaceUpdates;
                    }

                    /** @return array<string, mixed> */
                    public function installFromPackage(string $package): array
                    {
                        return $this->t->marketplaceInstallResults[$package] ?? ['ok' => false, 'reason' => 'unknown package'];
                    }
                };
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private UpdatesAdminControllerTest $t)
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
            'adext' => static fn(): object => new class {
                /** @return array<string, mixed> */
                public function get(string $t, string $s, array $c = []): array
                {
                    return [];
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private UpdatesAdminControllerTest $t)
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
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        $app->map('json', function (mixed $v) use ($test): void {
            $test->jsons[] = $v;
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * Canned UpdateService: behavior flags per test, temp-dir storage.
 *
 * @property array<string, mixed> $config
 */
final class FakeUpdates extends UpdateService
{
    /** @var array<string, mixed> */
    public array $config = ['updates_path' => ''];

    /** @var array<string, mixed> */
    public array $lastCheckResult = ['status' => 'up_to_date', 'target_version' => ''];

    /** @var array<string, mixed> */
    public array $checkResult = ['status' => 'up_to_date'];

    public bool $isDueResult = false;
    public bool $autoEnabled = false;
    public ?bool $autoSaved = false;
    public ?string $skipped = null;
    public ?string $unskipped = null;
    public bool $checked = false;

    public function storageDir(): string
    {
        return rtrim((string) ($this->config['updates_path'] ?? ''), '/');
    }

    /** @return array<string, mixed> */
    public function lastCheck(): array
    {
        return $this->lastCheckResult;
    }

    public function isDue(): bool
    {
        return $this->isDueResult;
    }

    /** @return array<string, mixed> */
    public function check(bool $force = false): array
    {
        $this->checked = true;

        return $this->checkResult;
    }

    public function autoUpdateEnabled(): bool
    {
        return $this->autoEnabled;
    }

    public function setAutoUpdate(bool $enabled): void
    {
        $this->autoSaved = $enabled;
    }

    /** @return list<string> */
    public function skippedVersions(): array
    {
        return [];
    }

    public function skipVersion(string $version): void
    {
        $this->skipped = $version;
    }

    public function unskipVersion(string $version): void
    {
        $this->unskipped = $version;
    }

    /** @return array<string, mixed> */
    public function preFlight(string $targetVersion, bool $includeLocks = true): array
    {
        return [];
    }

    /**
     * @return array{themes: list<array<string, mixed>>, plugins: list<array<string, mixed>>, marketplaceConnected: bool}
     */
    public function addons(): array
    {
        return [
            'themes' => [['folder' => 'default', 'package' => null, 'version' => '1.0.0']],
            'plugins' => [['folder' => 'Blog', 'package' => 'pubvana/blog', 'version' => '1.0.0']],
            'marketplaceConnected' => false,
        ];
    }
}

/**
 * Canned TrustClient: no network.
 */
final class FakeTrustClient
{
    public bool $rechecked = false;
    /** @var array{status: string, warning: ?string, checked_at: string}|null */
    public ?array $cached = null;

    /** @return array<string, array{status: string, warning: ?string, checked_at: string}> */
    public function statusesForAll(): array
    {
        return [];
    }

    /**
     * @return array{type: string, slug: string, version: string, author: string, origin: string}
     */
    public function coreItem(string $version): array
    {
        return ['type' => 'core', 'slug' => 'pubvana', 'version' => $version, 'author' => 'pubvana', 'origin' => 'core'];
    }

    public function themeItem(string $folder): ?array
    {
        return null;
    }

    public function pluginItem(string $id, array $info): ?array
    {
        return null;
    }

    public function recheckAll(?string $target = null): void
    {
        $this->rechecked = true;
    }

    /**
     * @return array{status: string, warning: ?string, checked_at: string}|null
     */
    public function getCachedStatus(string $type, string $slug, string $version, string $author): ?array
    {
        return $this->cached;
    }
}
