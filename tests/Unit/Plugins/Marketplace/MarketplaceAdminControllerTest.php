<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Marketplace\Controllers\MarketplaceAdminController;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * MarketplaceAdminController over a stubbed MarketplaceService.
 *
 * The service itself is HTTP-heavy (covered by MarketplaceServiceTest);
 * here every service call is a canned result so the controller's branches
 * (flashes, redirects, view data) are exercised without the network.
 */
#[CoversClass(MarketplaceAdminController::class)]
final class MarketplaceAdminControllerTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var list<array<string, mixed>> */
    public array $jsons = [];
    public FakeMarketplace $marketplace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        MarketplaceSchema::create($this->pdo);
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->jsons = [];
        $this->marketplace = new FakeMarketplace();
    }

    public function testIndexDisconnected(): void
    {
        $this->marketplace->connected = false;

        (new MarketplaceAdminController($this->engine()))->index();

        self::assertSame('pubvana/marketplace/admin/index', $this->fetches[0]['view']);
        $data = $this->fetches[0]['data'];
        self::assertSame('Marketplace', $data['pageTitle']);
        self::assertFalse($data['connected']);
        self::assertSame([], $data['categories']);
        self::assertSame([], $data['items']);
        self::assertSame('/admin/marketplace', $data['adminBase']);
    }

    public function testIndexConnected(): void
    {
        $this->marketplace->connected = true;
        $this->marketplace->email = 'you@example.com';
        $this->marketplace->categories = [['name' => 'Themes']];
        $this->marketplace->items = [['name' => 'Demo']];

        (new MarketplaceAdminController($this->engine(userEmail: 'admin@example.com')))->index();

        $data = $this->fetches[0]['data'];
        self::assertTrue($data['connected']);
        self::assertSame('you@example.com', $data['accountEmail']);
        self::assertSame('admin@example.com', $data['prefillEmail']);
        self::assertCount(1, $data['categories']);
        self::assertCount(1, $data['items']);
    }

    public function testConnectWithoutEmail(): void
    {
        (new MarketplaceAdminController($this->engine(userEmail: null)))->connect();

        self::assertSame('No logged-in admin email is available to connect with.', $this->flashes['danger'][0]);
        self::assertSame(['/admin/marketplace'], $this->redirects);
    }

    public function testConnectSuccessAndFailure(): void
    {
        $this->marketplace->connectResult = ['ok' => true];
        (new MarketplaceAdminController($this->engine(data: ['password' => 's', 'password_conf' => 's'])))->connect();
        self::assertSame('Connected to the Pubvana account. Browse the catalog below.', $this->flashes['success'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->marketplace->connectResult = ['ok' => false, 'reason' => 'Bad password.'];
        (new MarketplaceAdminController($this->engine(data: ['password' => 's', 'password_conf' => 's'])))->connect();
        self::assertSame('Bad password.', $this->flashes['danger'][0]);
        self::assertSame(['/admin/marketplace'], $this->redirects);
    }

    public function testDisconnect(): void
    {
        (new MarketplaceAdminController($this->engine()))->disconnect();

        self::assertTrue($this->marketplace->disconnected);
        self::assertSame('Disconnected from the Pubvana account.', $this->flashes['info'][0]);
        self::assertSame(['/admin/marketplace'], $this->redirects);
    }

    public function testPurchases(): void
    {
        $this->marketplace->connected = true;
        $this->marketplace->records = [['store_product_id' => 1]];

        (new MarketplaceAdminController($this->engine()))->purchases();

        self::assertSame('pubvana/marketplace/admin/purchases', $this->fetches[0]['view']);
        self::assertCount(1, $this->fetches[0]['data']['records']);

        $this->fetches = [];
        $this->marketplace->connected = false;
        (new MarketplaceAdminController($this->engine()))->purchases();
        self::assertSame([], $this->fetches[0]['data']['records']);
    }

    public function testVerify(): void
    {
        (new MarketplaceAdminController($this->engine()))->verify();

        self::assertTrue($this->marketplace->verified);
        self::assertSame('Purchases verified against pubvanacms.com.', $this->flashes['success'][0]);
        self::assertSame(['/admin/marketplace/purchases'], $this->redirects);
    }

    public function testAddToCartEmitsJson(): void
    {
        $this->marketplace->cartResult = ['ok' => true];
        $app = $this->engine(data: ['product_id' => '7', 'currency' => 'EUR', 'scope' => 'multi_site']);
        (new MarketplaceAdminController($app))->addToCart();

        self::assertSame([['ok' => true]], $this->jsons);
        self::assertSame([7, 'EUR', 'multi_site'], $this->marketplace->cartArgs);
    }

    public function testInstallDomainMovePath(): void
    {
        $this->marketplace->record = new \stdClass();
        $this->marketplace->needsMove = true;

        (new MarketplaceAdminController($this->engine(data: ['product_id' => '5'])))->install();

        self::assertStringContainsString('bound to another domain', $this->flashes['warning'][0]);
        self::assertTrue($this->marketplace->moveRequested);
        self::assertSame(['/admin/marketplace/purchases'], $this->redirects);
    }

    public function testInstallSuccessAndFailure(): void
    {
        $this->marketplace->installResult = ['ok' => true, 'reason' => 'Installed.'];
        (new MarketplaceAdminController($this->engine(data: ['product_id' => '5'])))->install();
        self::assertSame('Installed.', $this->flashes['success'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->marketplace->installResult = ['ok' => false, 'reason' => 'Nope.'];
        (new MarketplaceAdminController($this->engine(data: ['product_id' => '5'])))->install();
        self::assertSame('Nope.', $this->flashes['danger'][0]);
        self::assertSame(['/admin/marketplace/purchases'], $this->redirects);
    }

    public function testInstallFree(): void
    {
        $this->marketplace->freeResult = ['ok' => true, 'reason' => 'Installed.'];
        (new MarketplaceAdminController($this->engine(data: ['package' => 'pubvana/blog'])))->installFree();

        self::assertSame('Installed.', $this->flashes['success'][0]);
        self::assertSame('pubvana/blog', $this->marketplace->freePackage);
        self::assertSame(['/admin/marketplace'], $this->redirects);
    }

    public function testReinstallAllDisconnected(): void
    {
        $this->marketplace->connected = false;
        (new MarketplaceAdminController($this->engine()))->reinstallAll();

        self::assertSame('Connect a Pubvana account first.', $this->flashes['danger'][0]);
        self::assertSame(['/admin/marketplace'], $this->redirects);
    }

    public function testReinstallAllSuccessAndPartial(): void
    {
        $this->marketplace->connected = true;
        $this->marketplace->reinstallResult = ['ok' => 3, 'skipped' => 1, 'failed' => []];
        (new MarketplaceAdminController($this->engine()))->reinstallAll();
        self::assertStringContainsString('Reinstalled 3 items', $this->flashes['success'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->marketplace->reinstallResult = ['ok' => 2, 'skipped' => 1, 'failed' => ['x']];
        (new MarketplaceAdminController($this->engine()))->reinstallAll();
        self::assertStringContainsString('Some items failed', $this->flashes['warning'][0]);
        self::assertSame(['/admin/marketplace/purchases'], $this->redirects);
    }

    public function testCartOpen(): void
    {
        $this->marketplace->checkout = 'https://store.test/cart';
        (new MarketplaceAdminController($this->engine()))->cartOpen();

        self::assertSame(['https://store.test/cart'], $this->redirects);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function engine(array $data = [], ?string $userEmail = 'admin@example.com'): Engine
    {
        $test = $this;
        $marketplace = $this->marketplace;
        $app = $this->app([
            'request' => static fn(): object => new class($data) {
                public Collection $data;
                /** @param array<string, mixed> $d */
                public function __construct(array $d)
                {
                    $this->data = new Collection($d);
                }
            },
            'marketplace' => static fn(): FakeMarketplace => $marketplace,
            'auth' => function () use ($userEmail): object {
                return new class($userEmail) {
                    public function __construct(private ?string $email)
                    {
                    }

                    public function user(): ?object
                    {
                        if ($this->email === null) {
                            return null;
                        }

                        return new class {
                            /** @return list<string> */
                            public function getGroups(): array
                            {
                                return ['admin'];
                            }
                        };
                    }

                    public function users(): object
                    {
                        $email = $this->email;

                        return new class($email) {
                            public function __construct(private ?string $email)
                            {
                            }

                            public function getEmail(object $user): ?string
                            {
                                return $this->email;
                            }
                        };
                    }
                };
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/marketplace';
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private MarketplaceAdminControllerTest $t)
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
                    public function __construct(private MarketplaceAdminControllerTest $t)
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
 * Canned MarketplaceService stand-in: no HTTP, behavior flags per test.
 */
final class FakeMarketplace
{
    public bool $connected = false;
    public string $email = '';
    /** @var array<int, array<string, mixed>> */
    public array $categories = [];
    /** @var array<int, array<string, mixed>> */
    public array $items = [];
    /** @var array<int, array<string, mixed>> */
    public array $records = [];
    /** @var array<string, mixed> */
    public array $connectResult = ['ok' => true];
    public bool $disconnected = false;
    public bool $verified = false;
    /** @var array<string, mixed> */
    public array $cartResult = ['ok' => true];
    /** @var list<mixed> */
    public array $cartArgs = [];
    public ?object $record = null;
    public bool $needsMove = false;
    public bool $moveRequested = false;
    /** @var array<string, mixed> */
    public array $installResult = ['ok' => true, 'reason' => 'Installed.'];
    /** @var array<string, mixed> */
    public array $freeResult = ['ok' => true, 'reason' => 'Installed.'];
    public string $freePackage = '';
    /** @var array<string, mixed> */
    public array $reinstallResult = ['ok' => 0, 'skipped' => 0, 'failed' => []];
    public string $checkout = '';

    public function connected(): bool
    {
        return $this->connected;
    }

    public function accountEmail(): string
    {
        return $this->email;
    }

    /** @return array<int, array<string, mixed>> */
    public function categories(): array
    {
        return $this->categories;
    }

    /** @return array<int, array<string, mixed>> */
    public function items(): array
    {
        return $this->items;
    }

    /** @return array<string, array{type: string, folder: string, version: string}> */
    public function localPackageVersions(): array
    {
        return [];
    }

    /** @return array<int, array<string, mixed>> */
    public function localInstallRecords(): array
    {
        return $this->records;
    }

    /** @return array<string, mixed> */
    public function connectAccount(string $e, string $p, string $c): array
    {
        return $this->connectResult;
    }

    public function disconnectAccount(): void
    {
        $this->disconnected = true;
    }

    /** @return array<string, mixed> */
    public function purchases(): array
    {
        $this->verified = true;

        return ['ok' => true];
    }

    /** @return array<string, mixed> */
    public function addToCart(int $id, string $currency, string $scope): array
    {
        $this->cartArgs = [$id, $currency, $scope];

        return $this->cartResult;
    }

    public function installRecordForProduct(int $id): ?object
    {
        return $this->record;
    }

    public function needsDomainMove(int $id): bool
    {
        return $this->needsMove;
    }

    public function requestDomainMove(int $id): void
    {
        $this->moveRequested = true;
    }

    /** @return array<string, mixed> */
    public function install(int $id): array
    {
        return $this->installResult;
    }

    /** @return array<string, mixed> */
    public function installFromPackage(string $package): array
    {
        $this->freePackage = $package;

        return $this->freeResult;
    }

    /** @return array<string, mixed> */
    public function reinstallAll(): array
    {
        return $this->reinstallResult;
    }

    public function checkoutUrl(): string
    {
        return $this->checkout;
    }
}
