<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\PluginsController;
use Pubvana\Models\TrustCache;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PluginsController coverage.
 */
#[CoversClass(PluginsController::class)]
final class PluginsControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var array<string, mixed> */
    public array $halts = [];
    public FakePluginLoader $loader;
    public FakePluginTrust $trust;
    private ?string $ajaxBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        Sqlite::recreate();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->halts = [];
        $this->loader = new FakePluginLoader();
        $this->trust = new FakePluginTrust();
        $this->ajaxBackup = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    protected function tearDown(): void
    {
        if ($this->ajaxBackup === null) {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        } else {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->ajaxBackup;
        }
        parent::tearDown();
    }

    public function testIndexRendersSortedRows(): void
    {
        $this->loader->local = [
            'pubvana/blog' => ['name' => 'Blog', 'version' => '1.0', 'source' => 'local'],
            'pubvana/shop' => ['name' => 'Shop', 'version' => '2.0', 'source' => 'local'],
        ];
        $this->loader->vendor = [
            'enlivenapp/x' => ['name' => 'X', 'version' => '1.0', 'source' => 'vendor'],
        ];
        $this->loader->states = [
            'pubvana/blog' => ['enabled' => true, 'priority' => 60, 'required' => false],
            'pubvana/shop' => ['enabled' => false, 'priority' => 10, 'required' => true],
        ];
        $this->trust->items = [
            'pubvana/blog' => ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->statuses = [
            'plugin|blog|1.0|a' => ['status' => 'trusted', 'warning' => null],
        ];
        (new PluginsController($this->engine()))->index();

        self::assertSame('admin/plugins/index', $this->fetches[0]['view']);
        $rows = $this->fetches[0]['data']['plugins'];
        // Required first, then priority, then name.
        self::assertSame('pubvana/shop', $rows[0]['id']);
        self::assertTrue($rows[0]['required']);
        self::assertSame('vendor', $rows[1]['source']);
        self::assertSame('trusted', $rows[2]['trust_status']);
        self::assertSame('none', $rows[0]['trust_status']);
        self::assertSame([], $this->fetches[0]['data']['maliciousActive']);
    }

    public function testIndexFlagsMaliciousActive(): void
    {
        $this->loader->local = [
            'pubvana/evil' => ['name' => 'Evil', 'version' => '1.0'],
        ];
        $this->loader->states = [
            'pubvana/evil' => ['enabled' => true, 'priority' => 50, 'required' => false],
        ];
        $this->trust->items = [
            'pubvana/evil' => ['type' => 'plugin', 'slug' => 'evil', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->statuses = [
            'plugin|evil|1.0|a' => ['status' => TrustCache::STATUS_MALICIOUS, 'warning' => 'w'],
        ];
        (new PluginsController($this->engine()))->index();

        self::assertCount(1, $this->fetches[0]['data']['maliciousActive']);
        self::assertSame('Evil', $this->fetches[0]['data']['maliciousActive'][0]['name']);
    }

    public function testIndexDegradesOnTrustOutage(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->trust->throw = true;
        (new PluginsController($this->engine()))->index();

        self::assertSame('none', $this->fetches[0]['data']['plugins'][0]['trust_status']);
    }

    public function testIndexFallsBackToLoaderWhenNoState(): void
    {
        $this->loader->local = ['pubvana/new' => ['name' => 'New', 'version' => '1.0']];
        $this->loader->states = [];
        $this->loader->enabledFallback = ['pubvana/new' => true];
        $this->loader->requiredFallback = ['pubvana/new' => false];
        (new PluginsController($this->engine()))->index();

        self::assertTrue($this->fetches[0]['data']['plugins'][0]['enabled']);
    }

    public function testSaveNoChanges(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $app = $this->engine(data: ['plugins' => []]);
        (new PluginsController($app))->save();

        self::assertSame('No changes to apply.', $this->flashes['plugins_flash'][0]);
        self::assertSame(['/admin/plugins'], $this->redirects);
    }

    public function testSaveIgnoresUnknownAndNonArrayRows(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->seedState('pubvana/blog', false);
        $app = $this->engine(data: ['plugins' => [
            'pubvana/ghost' => ['enabled' => '1'],
            'pubvana/blog' => 'not-an-array',
        ]]);
        (new PluginsController($app))->save();

        self::assertSame('No changes to apply.', $this->flashes['plugins_flash'][0]);
    }

    public function testSaveSkipsMissingStateRow(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $app = $this->engine(data: ['plugins' => ['pubvana/blog' => ['enabled' => '1']]]);
        (new PluginsController($app))->save();

        self::assertSame('No changes to apply.', $this->flashes['plugins_flash'][0]);
    }

    public function testSaveDisablesEnabledPlugin(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->seedState('pubvana/blog', true);
        $app = $this->engine(data: ['plugins' => ['pubvana/blog' => ['enabled' => '0']]]);
        (new PluginsController($app))->save();

        self::assertSame('1 plugin updated.', $this->flashes['plugins_flash'][0]);
        self::assertSame(['/admin/plugins'], $this->redirects);
        self::assertFalse($this->readEnabled('pubvana/blog'));
    }

    public function testSaveRequiredPluginCannotBeDisabled(): void
    {
        $this->loader->local = ['pubvana/core' => ['name' => 'Core', 'version' => '1.0']];
        $this->seedState('pubvana/core', true, true);
        $app = $this->engine(data: ['plugins' => ['pubvana/core' => ['enabled' => '0']]]);
        (new PluginsController($app))->save();

        self::assertSame('No changes to apply.', $this->flashes['plugins_flash'][0]);
        self::assertTrue($this->readEnabled('pubvana/core'));
    }

    public function testSaveEnablesWithNoMigrations(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->seedState('pubvana/blog', false);
        $this->loader->migrationPaths = [[[], []]];
        $app = $this->engine(data: ['plugins' => ['pubvana/blog' => ['enabled' => '1']]]);
        (new PluginsController($app))->save();

        self::assertSame('1 plugin updated.', $this->flashes['plugins_flash'][0]);
        self::assertTrue($this->readEnabled('pubvana/blog'));
    }

    public function testSaveEnableRefusedByCachedMalicious(): void
    {
        $this->loader->local = ['pubvana/evil' => ['name' => 'Evil', 'version' => '1.0']];
        $this->seedState('pubvana/evil', false);
        $this->trust->items = [
            'pubvana/evil' => ['type' => 'plugin', 'slug' => 'evil', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->cached = ['status' => TrustCache::STATUS_MALICIOUS, 'warning' => 'w', 'checked_at' => 'x'];
        $app = $this->engine(data: ['plugins' => ['pubvana/evil' => ['enabled' => '1']]]);
        (new PluginsController($app))->save();

        self::assertStringContainsString("'pubvana/evil' has been found malicious", $this->flashes['plugins_flash'][0]);
        self::assertFalse($this->readEnabled('pubvana/evil'));
    }

    public function testSaveAjaxAnswersJson(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->seedState('pubvana/blog', true);
        $app = $this->engine(data: ['plugins' => ['pubvana/blog' => ['enabled' => '0']]]);
        try {
            (new PluginsController($app))->save();
            self::fail('must halt');
        } catch (PluginHaltProbe) {
        }
        self::assertSame(['ok' => true, 'message' => '1 plugin updated.'], $this->halts[0]['data']);
        self::assertCount(0, $this->redirects);
    }

    public function testSaveAjaxUnknownTrustNeedsConfirm(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->loader->local = ['pubvana/new' => ['name' => 'New', 'version' => '1.0']];
        $this->seedState('pubvana/new', false);
        $this->trust->items = [
            'pubvana/new' => ['type' => 'plugin', 'slug' => 'new', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->live = ['status' => TrustCache::STATUS_UNKNOWN, 'warning' => null, 'answered' => true];
        $app = $this->engine(data: ['plugins' => ['pubvana/new' => ['enabled' => '1']]]);
        try {
            (new PluginsController($app))->save();
            self::fail('must halt');
        } catch (PluginHaltProbe) {
        }
        // The trust gate halts first with the confirm payload.
        self::assertTrue($this->halts[0]['data']['needsConfirm']);
        self::assertFalse($this->readEnabled('pubvana/new'));
    }

    public function testSavePluralMessage(): void
    {
        $this->loader->local = [
            'pubvana/a' => ['name' => 'A', 'version' => '1.0'],
            'pubvana/b' => ['name' => 'B', 'version' => '1.0'],
        ];
        $this->seedState('pubvana/a', true);
        $this->seedState('pubvana/b', true);
        $app = $this->engine(data: ['plugins' => [
            'pubvana/a' => ['enabled' => '0'],
            'pubvana/b' => ['enabled' => '0'],
        ]]);
        (new PluginsController($app))->save();

        self::assertSame('2 plugins updated.', $this->flashes['plugins_flash'][0]);
    }

    public function testRecheckUnknownPlugin(): void
    {
        $this->loader->local = [];
        try {
            (new PluginsController($this->engine(data: ['plugin' => 'pubvana/ghost'])))->recheck();
            self::fail('must halt');
        } catch (PluginHaltProbe) {
        }
        self::assertSame(404, $this->halts[0]['code']);
    }

    public function testRecheckNoVersion(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->trust->items = [];
        try {
            (new PluginsController($this->engine(data: ['plugin' => 'pubvana/blog'])))->recheck();
            self::fail('must halt');
        } catch (PluginHaltProbe) {
        }
        self::assertSame(422, $this->halts[0]['code']);
    }

    public function testRecheckUnreachable(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->trust->items = [
            'pubvana/blog' => ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->live = ['status' => 'unknown', 'warning' => null, 'answered' => false];
        try {
            (new PluginsController($this->engine(data: ['plugin' => 'pubvana/blog'])))->recheck();
            self::fail('must halt');
        } catch (PluginHaltProbe) {
        }
        self::assertSame(503, $this->halts[0]['code']);
    }

    public function testRecheckSuccess(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->trust->items = [
            'pubvana/blog' => ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->live = ['status' => 'trusted', 'warning' => null, 'answered' => true];
        try {
            (new PluginsController($this->engine(data: ['plugin' => 'pubvana/blog'])))->recheck();
            self::fail('must halt');
        } catch (PluginHaltProbe) {
        }
        self::assertTrue($this->halts[0]['data']['ok']);
        self::assertSame('trusted', $this->halts[0]['data']['status']);
    }

    public function testRecheckTrustThrowDegrades(): void
    {
        $this->loader->local = ['pubvana/blog' => ['name' => 'Blog', 'version' => '1.0']];
        $this->trust->items = [
            'pubvana/blog' => ['type' => 'plugin', 'slug' => 'blog', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->throwCheck = true;
        try {
            (new PluginsController($this->engine(data: ['plugin' => 'pubvana/blog'])))->recheck();
            self::fail('must halt');
        } catch (PluginHaltProbe) {
        }
        self::assertSame(503, $this->halts[0]['code']);
    }

    private function seedState(string $id, bool $enabled, bool $required = false): void
    {
        $pdo = Sqlite::connection();
        $stmt = $pdo->prepare('INSERT INTO plugin_state (plugin_id, enabled, priority, required) VALUES (?, ?, 50, ?)');
        $stmt->execute([$id, $enabled ? 1 : 0, $required ? 1 : 0]);
    }

    private function readEnabled(string $id): bool
    {
        $pdo = Sqlite::connection();
        $stmt = $pdo->prepare('SELECT enabled FROM plugin_state WHERE plugin_id = ?');
        $stmt->execute([$id]);

        return (bool) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    private function engine(array $data = []): Engine
    {
        $test = $this;
        $app = $this->app([
            'request' => static fn(): object => new class($data) {
                public Collection $data;
                public Collection $query;
                /** @param array<string, mixed> $d */
                public function __construct(array $d)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection([]);
                }
            },
            'db' => static fn(): \PDO => Sqlite::connection(),
            'pluginLoader' => fn(): FakePluginLoader => $this->loader,
            'trustClient' => fn(): FakePluginTrust => $this->trust,
            'auth' => static fn(): object => new class {
                public function user(): object
                {
                    return new class {
                        /** @return list<string> */
                        public function getGroups(): array
                        {
                            return ['admin'];
                        }
                    };
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private PluginsControllerTest $t)
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
                    public function __construct(private PluginsControllerTest $t)
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
        $app->map('jsonHalt', function (mixed $d, int $c = 200) use ($test): never {
            $test->halts[] = ['data' => $d, 'code' => $c];
            throw new PluginHaltProbe();
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * PluginLoader double.
 */
final class FakePluginLoader
{
    /** @var array<string, array<string, mixed>> */
    public array $local = [];
    /** @var array<string, array<string, mixed>> */
    public array $vendor = [];
    /** @var array<string, array{enabled: bool, priority: int, required: bool}> */
    public array $states = [];
    /** @var array<string, bool> */
    public array $enabledFallback = [];
    /** @var array<string, bool> */
    public array $requiredFallback = [];
    /** @var list<array{0: list<string>, 1: list<string>}> */
    public array $migrationPaths = [];

    /** @return array<string, array<string, mixed>> */
    public function discoverLocal(): array
    {
        return $this->local;
    }

    /** @return array<string, array<string, mixed>> */
    public function discoverVendor(): array
    {
        return $this->vendor;
    }

    /** @return array<string, mixed>|null */
    public function getPluginState(string $id): ?array
    {
        return $this->states[$id] ?? null;
    }

    public function isEnabled(string $id): bool
    {
        return $this->enabledFallback[$id] ?? false;
    }

    public function isRequired(string $id): bool
    {
        return $this->requiredFallback[$id] ?? false;
    }

    /** @param array<string, mixed> $info @return array{0: list<string>, 1: list<string>} */
    public function pluginMigrationPatterns(string $id, array $info): array
    {
        return array_shift($this->migrationPaths) ?? [[], []];
    }
}

/**
 * Trust client double.
 */
final class FakePluginTrust
{
    /** @var array<string, array<string, string>> */
    public array $items = [];
    /** @var array<string, array<string, mixed>> */
    public array $statuses = [];
    /** @var array<string, mixed>|null */
    public ?array $cached = null;
    /** @var array<string, mixed>|null */
    public ?array $live = null;
    public bool $throw = false;
    public bool $throwCheck = false;

    public function ensureCacheForAll(): void
    {
        if ($this->throw) {
            throw new \RuntimeException('down');
        }
    }

    /** @return array<string, array<string, mixed>> */
    public function statusesForAll(): array
    {
        if ($this->throw) {
            throw new \RuntimeException('down');
        }

        return $this->statuses;
    }

    /** @param array<string, mixed> $info @return array<string, string>|null */
    public function pluginItem(string $id, array $info): ?array
    {
        if ($this->throw) {
            throw new \RuntimeException('down');
        }

        return $this->items[$id] ?? null;
    }

    /** @return array<string, mixed>|null */
    public function getCachedStatus(string $t, string $s, string $v, string $a): ?array
    {
        if ($this->throw) {
            throw new \RuntimeException('down');
        }

        return $this->cached;
    }

    /** @return array<string, mixed> */
    public function checkAddon(string $t, string $s, string $v, string $a, string $o): array
    {
        if ($this->throwCheck) {
            throw new \RuntimeException('down');
        }

        return $this->live ?? ['status' => 'trusted', 'warning' => null, 'answered' => true];
    }
}

/**
 * Probe for jsonHalt.
 */
final class PluginHaltProbe extends \RuntimeException
{
}
