<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\ThemesController;
use Pubvana\Models\BlockPlacement;
use Pubvana\Services\RegionManager;
use Pubvana\Services\ThemeService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ThemesController coverage.
 */
#[CoversClass(ThemesController::class)]
final class ThemesControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var array<string, mixed> */
    public array $halts = [];
    public FakeThemeSvc $themesSvc;
    public FakeRegions $regions;
    public FakeThemeTrust $trust;
    /** @var array<string, mixed> */
    public array $savedOptions = [];
    private ?string $ajaxBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        Sqlite::recreate();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->halts = [];
        $this->savedOptions = [];
        $this->themesSvc = new FakeThemeSvc();
        $this->regions = new FakeRegions();
        $this->trust = new FakeThemeTrust();
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

    public function testIndexRendersWithTrust(): void
    {
        $this->seedTheme('Alpha', 'alpha', 1);
        $this->seedTheme('Beta', 'beta', 0);
        $this->trust->items = [
            'alpha' => ['type' => 'theme', 'slug' => 'alpha', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->statuses = [
            'theme|alpha|1.0|a' => ['status' => 'trusted', 'warning' => null],
        ];
        (new ThemesController($this->engine()))->index();

        self::assertTrue($this->themesSvc->synced);
        self::assertSame('admin/themes/index', $this->fetches[0]['view']);
        self::assertCount(2, $this->fetches[0]['data']['themes']);
        self::assertSame('trusted', $this->fetches[0]['data']['trust']['alpha']['status']);
        self::assertSame('none', $this->fetches[0]['data']['trust']['beta']['status']);
    }

    public function testIndexDegradesOnTrustOutage(): void
    {
        $this->seedTheme('Alpha', 'alpha', 1);
        $this->trust->throw = true;
        (new ThemesController($this->engine()))->index();

        self::assertSame('none', $this->fetches[0]['data']['trust']['alpha']['status']);
        self::assertSame([], $this->fetches[0]['data']['maliciousActive']);
    }

    public function testIndexFlagsMaliciousActive(): void
    {
        $this->seedTheme('Bad', 'bad', 1);
        $this->trust->items = [
            'bad' => ['type' => 'theme', 'slug' => 'bad', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->statuses = [
            'theme|bad|1.0|a' => ['status' => 'malicious', 'warning' => 'w'],
        ];
        (new ThemesController($this->engine()))->index();

        self::assertCount(1, $this->fetches[0]['data']['maliciousActive']);
        self::assertSame('Bad', $this->fetches[0]['data']['maliciousActive'][0]['name']);
    }

    public function testActivateMissingRedirects(): void
    {
        (new ThemesController($this->engine()))->activate('99');
        self::assertSame(['/admin/themes'], $this->redirects);
    }

    public function testActivateSuccessNonAjax(): void
    {
        $id = $this->seedTheme('Alpha', 'alpha', 0);
        $this->themesSvc->activateResult = 'activated';
        $this->trust->items = [];
        (new ThemesController($this->engine()))->activate((string) $id);

        self::assertSame(['/admin/themes'], $this->redirects);
    }

    public function testActivateRefusedByTrustGate(): void
    {
        $id = $this->seedTheme('Bad', 'bad', 0);
        $this->trust->items = [
            'bad' => ['type' => 'theme', 'slug' => 'bad', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->cached = ['status' => 'malicious', 'warning' => 'w', 'checked_at' => 'x'];
        (new ThemesController($this->engine()))->activate((string) $id);

        self::assertSame(['/admin/themes'], $this->redirects);
        self::assertSame(0, $this->themesSvc->activateCalls);
    }

    public function testActivateAjaxSuccess(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $id = $this->seedTheme('Alpha', 'alpha', 0);
        $this->themesSvc->activateResult = 'activated';
        $this->trust->items = [];
        try {
            (new ThemesController($this->engine()))->activate((string) $id);
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertSame(['ok' => true], $this->halts[0]['data']);
    }

    public function testActivateAjaxFailure(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $id = $this->seedTheme('Alpha', 'alpha', 0);
        $this->themesSvc->activateResult = 'disabled';
        $this->trust->items = [];
        try {
            (new ThemesController($this->engine()))->activate((string) $id);
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertSame('Theme is disabled and cannot be activated.', $this->halts[0]['data']['error']);
    }

    public function testRecheckByFolderGuards(): void
    {
        try {
            (new ThemesController($this->engine(data: [])))->recheckByFolder();
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertSame(422, $this->halts[0]['code']);

        $this->halts = [];
        $this->trust->throwItem = true;
        try {
            (new ThemesController($this->engine(data: ['folder' => 'x'])))->recheckByFolder();
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertSame(422, $this->halts[0]['code']);
    }

    public function testRecheckByFolderSuccessAndUnreachable(): void
    {
        $this->trust->items = [
            'alpha' => ['type' => 'theme', 'slug' => 'alpha', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->live = ['status' => 'trusted', 'warning' => null, 'answered' => true];
        try {
            (new ThemesController($this->engine(data: ['folder' => 'alpha'])))->recheckByFolder();
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertTrue($this->halts[0]['data']['ok']);

        $this->halts = [];
        $this->trust->live = ['status' => 'unknown', 'warning' => null, 'answered' => false];
        try {
            (new ThemesController($this->engine(data: ['folder' => 'alpha'])))->recheckByFolder();
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertSame(503, $this->halts[0]['code']);
    }

    public function testRecheckUnknownTheme(): void
    {
        try {
            (new ThemesController($this->engine()))->recheck('99');
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertSame(404, $this->halts[0]['code']);
    }

    public function testRecheckSuccess(): void
    {
        $id = $this->seedTheme('Alpha', 'alpha', 0);
        $this->trust->items = [
            'alpha' => ['type' => 'theme', 'slug' => 'alpha', 'version' => '1.0', 'author' => 'a', 'origin' => 'o'],
        ];
        $this->trust->live = ['status' => 'known', 'warning' => null, 'answered' => true];
        try {
            (new ThemesController($this->engine()))->recheck((string) $id);
            self::fail('must halt');
        } catch (ThemeHaltProbe) {
        }
        self::assertSame('known', $this->halts[0]['data']['status']);
    }

    public function testOptionsMissingRedirects(): void
    {
        (new ThemesController($this->engine()))->options('99');
        self::assertSame(['/admin/themes'], $this->redirects);
    }

    public function testOptionsRendersEmptyWhenNoManifest(): void
    {
        $id = $this->seedTheme('NoManifest', 'no_such_folder_xyz', 0);
        (new ThemesController($this->engine()))->options((string) $id);
        self::assertSame('admin/themes/options', $this->fetches[0]['view']);
        self::assertSame([], $this->fetches[0]['data']['options']);
    }

    public function testSaveOptionsMissingRedirects(): void
    {
        (new ThemesController($this->engine(data: [])))->saveOptions('99');
        self::assertSame(['/admin/themes'], $this->redirects);
    }

    public function testSaveOptionsWritesNothingWithoutManifest(): void
    {
        $id = $this->seedTheme('NoManifest', 'no_such_folder_xyz', 0);
        (new ThemesController($this->engine(data: [])))->saveOptions((string) $id);
        self::assertSame([], $this->savedOptions);
        self::assertSame(["/admin/themes/{$id}/options"], $this->redirects);
    }

    public function testRegionsAndPlacementActions(): void
    {
        (new ThemesController($this->engine()))->regions();
        self::assertSame('admin/themes/regions', $this->fetches[0]['view']);

        (new ThemesController($this->engine(data: ['region_id' => 'r', 'block_key' => 'b'])))->placeBlock();
        self::assertSame([['r', 'b']], $this->regions->placed);
        self::assertSame(['/admin/themes/regions'], $this->redirects);

        $this->redirects = [];
        (new ThemesController($this->engine(data: [])))->placeBlock();
        self::assertSame(['/admin/themes/regions'], $this->redirects);

        (new ThemesController($this->engine(data: ['placement_id' => '3'])))->removePlacement();
        self::assertSame([3], $this->regions->removed);

        (new ThemesController($this->engine(data: ['region_id' => 'r', 'placement_ids' => [1, 2]])))->reorderPlacements();
        self::assertSame([['r', [1, 2]]], $this->regions->reordered);

        (new ThemesController($this->engine(data: ['placement_id' => '5', 'region_id' => 'n'])))->movePlacement();
        self::assertSame([[5, 'n']], $this->regions->moved);
    }

    public function testSaveBlockValuesGuardsAndSaves(): void
    {
        (new ThemesController($this->engine(data: [])))->saveBlockValues();
        self::assertSame(['/admin/themes/regions'], $this->redirects);

        $this->redirects = [];
        $pid = $this->seedPlacement('sidebar', 'core/text');
        $this->regions->blocks = ['core/text' => ['options' => ['body' => ['type' => 'textarea'], 'title' => ['type' => 'text']]]];
        $app = $this->engine(data: [
            'placement_id' => (string) $pid,
            'values' => ['body' => '<b>hi</b><script>x</script>', 'title' => 'T', 'list' => [['a' => 'b']]],
        ]);
        (new ThemesController($app))->saveBlockValues();

        self::assertSame(['/admin/themes/regions'], $this->redirects);
        self::assertArrayHasKey('body', $this->regions->savedValues);
        self::assertStringNotContainsString('<script>', (string) $this->regions->savedValues['body']);
        self::assertSame('T', $this->regions->savedValues['title']);
    }

    private function seedTheme(string $name, string $folder, int $active): int
    {
        $pdo = Sqlite::connection();
        $stmt = $pdo->prepare('INSERT INTO themes (name, folder, is_active) VALUES (?, ?, ?)');
        $stmt->execute([$name, $folder, $active]);

        return (int) $pdo->lastInsertId();
    }

    private function seedPlacement(string $region, string $key): int
    {
        $pdo = Sqlite::connection();
        $stmt = $pdo->prepare('INSERT INTO block_placements (region_id, block_key, sort_order) VALUES (?, ?, 0)');
        $stmt->execute([$region, $key]);

        return (int) $pdo->lastInsertId();
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
            'themes' => fn(): FakeThemeSvc => $this->themesSvc,
            'regions' => fn(): FakeRegions => $this->regions,
            'trustClient' => fn(): FakeThemeTrust => $this->trust,
            'media' => static fn(): object => new class {
                public function picker(string $n, mixed $v): string
                {
                    return 'picker';
                }
            },
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
                public function __construct(private ThemesControllerTest $t)
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
                    public function __construct(private ThemesControllerTest $t)
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
        $app->map('json', function (mixed $d) use ($test): void {
            $test->halts[] = ['data' => $d, 'code' => 200];
        });
        $app->map('jsonHalt', function (mixed $d, int $c = 200) use ($test): never {
            $test->halts[] = ['data' => $d, 'code' => $c];
            throw new ThemeHaltProbe();
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * ThemeService double.
 */
final class FakeThemeSvc extends ThemeService
{
    public bool $synced = false;
    public string $activateResult = 'activated';
    public int $activateCalls = 0;

    public function __construct()
    {
    }

    public function sync(): void
    {
        $this->synced = true;
    }

    /** @return list<string> */
    public function getValidationResults(): array
    {
        return [];
    }

    public function activate(int $id): string
    {
        $this->activateCalls++;

        return $this->activateResult;
    }

    public function getThemeOption(int $themeId, string $key, ?string $default = null): ?string
    {
        return $default;
    }

    public function saveThemeOption(int $themeId, string $key, string $value): void
    {
    }
}

/**
 * RegionManager double.
 */
final class FakeRegions extends RegionManager
{
    /** @var list<array{0: string, 1: string}> */
    public array $placed = [];
    /** @var list<int> */
    public array $removed = [];
    /** @var list<array{0: string, 1: list<int>}> */
    public array $reordered = [];
    /** @var list<array{0: int, 1: string}> */
    public array $moved = [];
    /** @var array<string, mixed> */
    public array $savedValues = [];
    /** @var array<string, mixed> */
    public array $blocks = [];

    public function __construct()
    {
    }

    /** @return array<string, mixed> */
    public function getAllPlacements(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getPlacementValues(int $placementId): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getRegions(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getAvailableBlocks(): array
    {
        return $this->blocks;
    }

    /** @return array<string, mixed> */
    public function getOrphanedPlacements(): array
    {
        return [];
    }

    public function savePlacement(string $regionId, string $blockKey): ?BlockPlacement
    {
        $this->placed[] = [$regionId, $blockKey];

        return null;
    }

    public function removePlacement(int $placementId): void
    {
        $this->removed[] = $placementId;
    }

    /** @param list<int> $placementIds */
    public function reorderPlacements(string $regionId, array $placementIds): void
    {
        $this->reordered[] = [$regionId, $placementIds];
    }

    public function movePlacement(int $placementId, string $newRegionId): void
    {
        $this->moved[] = [$placementId, $newRegionId];
    }

    /** @param array<string, mixed> $values */
    public function savePlacementValues(int $placementId, array $values): void
    {
        $this->savedValues = $values;
    }
}

/**
 * Trust client double.
 */
final class FakeThemeTrust
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
    public bool $throwItem = false;

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

    /** @return array<string, string>|null */
    public function themeItem(string $folder): ?array
    {
        if ($this->throwItem) {
            throw new \RuntimeException('down');
        }

        return $this->items[$folder] ?? null;
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
        if ($this->throw) {
            throw new \RuntimeException('down');
        }

        return $this->live ?? ['status' => 'trusted', 'warning' => null, 'answered' => true];
    }
}

/**
 * Probe for jsonHalt.
 */
final class ThemeHaltProbe extends \RuntimeException
{
}
