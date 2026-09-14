<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Controllers\Admin;

use Enlivenapp\FlightShield\Models\AuthGroup;
use Enlivenapp\FlightShield\Result;
use flight\Engine;
use flight\util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Controllers\Admin\UsersController;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * UsersController coverage.
 */
#[CoversClass(UsersController::class)]
final class UsersControllerTest extends TestCase
{
    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var array<string, mixed> */
    public array $sent = [];
    public bool $mailThrow = false;
    /** @var array<string, mixed> */
    public array $settingsRows = [];
    public FakeUsersSvc $usersSvc;
    public FakeUserAdmin $userAdminFake;

    protected function setUp(): void
    {
        parent::setUp();
        Sqlite::recreate();
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->sent = [];
        $this->mailThrow = false;
        $this->settingsRows = [];
        $this->usersSvc = new FakeUsersSvc();
        $this->userAdminFake = new FakeUserAdmin();
    }

    public function testIndexUsesPageOneFloorAndSuperadminFlag(): void
    {
        $app = $this->engine(query: ['page' => '0'], viewerSuperadmin: false);
        $this->controller($app)->index();

        self::assertSame('admin/users/index', $this->fetches[0]['view']);
        self::assertSame(1, $this->fetches[0]['data']['page']);
        self::assertSame(20, $this->fetches[0]['data']['perPage']);
        self::assertFalse($this->usersSvc->lastInclude);
        self::assertSame(7, $this->fetches[0]['data']['total']);
    }

    public function testIndexSuperadminSeesAll(): void
    {
        $app = $this->engine(query: ['page' => '3'], viewerSuperadmin: true);
        $this->controller($app)->index();

        self::assertSame(3, $this->fetches[0]['data']['page']);
        self::assertTrue($this->usersSvc->lastInclude);
    }

    public function testCreateShowsNonSuperadminGroups(): void
    {
        $app = $this->engine(viewerSuperadmin: false);
        $this->controller($app)->create();

        $aliases = array_map(static fn($g): string => $g->alias, $this->fetches[0]['data']['groups']);
        self::assertNotContains('superadmin', $aliases);
        self::assertContains('user', $aliases);
    }

    public function testCreateSuperadminSeesAllGroups(): void
    {
        $app = $this->engine(viewerSuperadmin: true);
        $this->controller($app)->create();

        $aliases = array_map(static fn($g): string => $g->alias, $this->fetches[0]['data']['groups']);
        self::assertContains('superadmin', $aliases);
    }

    public function testStoreSuccess(): void
    {
        $this->userAdminFake->createResult = (new Result())->setSuccess(true);
        $app = $this->engine(data: ['username' => 'u', 'email' => 'e', 'password' => 'p', 'group' => 'user', '_csrf_token' => 'x']);
        $this->controller($app)->store();

        self::assertSame('u', $this->userAdminFake->lastCreate['username']);
        self::assertSame(['/admin/users'], $this->redirects);
        self::assertSame('User created.', $this->flashes['success'][0]);
    }

    public function testStoreBlankGroupBecomesNullAndFailure(): void
    {
        $this->userAdminFake->createResult = (new Result())->setSuccess(false)->setReason('bad');
        $app = $this->engine(data: ['username' => 'u', 'group' => '']);
        $this->controller($app)->store();

        self::assertNull($this->userAdminFake->lastCreate['group']);
        self::assertSame(['/admin/users/create'], $this->redirects);
        self::assertSame('bad', $this->flashes['error'][0]);
    }

    public function testInviteRejectsBadEmail(): void
    {
        $this->controller($this->engine(data: ['email' => 'bad']))->invite();
        self::assertSame('Enter a valid email address.', $this->flashes['error'][0]);
        self::assertSame(['/admin/users'], $this->redirects);
    }

    public function testInviteRejectsWhenRegistrationDisabled(): void
    {
        $app = $this->engine(data: ['email' => 'a@b.test'], shield: ['allow_registration' => false]);
        $this->controller($app)->invite();
        self::assertSame('Registration is disabled, so invitations cannot be sent.', $this->flashes['error'][0]);
    }

    public function testInviteRejectsExistingIdentity(): void
    {
        $pdo = Sqlite::connection();
        $pdo->exec("INSERT INTO users (username, active) VALUES ('taken', 1)");
        $uid = (int) $pdo->lastInsertId();
        $pdo->exec("INSERT INTO auth_identities (user_id, type, secret) VALUES ($uid, 'email_password', 'taken@x.test')");

        $app = $this->engine(data: ['email' => 'taken@x.test']);
        $this->controller($app)->invite();
        self::assertSame('A user with that email address already exists.', $this->flashes['error'][0]);
    }

    public function testInviteSendsWithSiteUrlSetting(): void
    {
        $this->settingsRows = ['CMS.siteName' => 'My Site', 'CMS.siteUrl' => 'https://example.test/'];
        $app = $this->engine(data: ['email' => 'new@x.test']);
        $this->controller($app)->invite();

        self::assertSame('new@x.test', $this->sent['to']);
        self::assertStringContainsString('My Site', $this->sent['subject']);
        self::assertStringContainsString('https://example.test/auth/register', $this->sent['body']);
        self::assertSame('Invitation sent to new@x.test.', $this->flashes['success'][0]);
    }

    public function testInviteDerivesBaseUrlFromRequest(): void
    {
        $this->settingsRows = ['CMS.siteName' => 'S'];
        $app = $this->engine(data: ['email' => 'n2@x.test'], host: 'h.test', secure: true, base: '/sub');
        $this->controller($app)->invite();

        self::assertStringContainsString('https://h.test/sub/auth/register', $this->sent['body']);
    }

    public function testInviteMailFailure(): void
    {
        $this->mailThrow = true;
        $this->settingsRows = ['CMS.siteName' => 'S'];
        $app = $this->engine(data: ['email' => 'fail@x.test']);
        $this->controller($app)->invite();

        self::assertSame('Failed to send the invitation. Check the mail settings.', $this->flashes['error'][0]);
    }

    public function testEditMissingRedirects(): void
    {
        $this->usersSvc->findResult = null;
        $this->controller($this->engine())->edit('99');
        self::assertSame(['/admin/users'], $this->redirects);
    }

    public function testEditRenders(): void
    {
        $this->usersSvc->findResult = new FakeUser(5);
        $this->usersSvc->email = 'e@x.test';
        $this->controller($this->engine())->edit('5');

        self::assertSame('admin/users/edit', $this->fetches[0]['view']);
        self::assertSame('e@x.test', $this->fetches[0]['data']['email']);
    }

    public function testUpdateMissingRedirects(): void
    {
        $this->usersSvc->findResult = null;
        $this->controller($this->engine(data: []))->update('99');
        self::assertSame(['/admin/users'], $this->redirects);
    }

    public function testUpdateSyncDenied(): void
    {
        $this->usersSvc->findResult = new FakeUser(5);
        $this->userAdminFake->syncResult = (new Result())->setSuccess(false)->setReason('denied');
        $this->controller($this->engine(data: ['groups' => ['user']]))->update('5');

        self::assertSame(['/admin/users/5/edit'], $this->redirects);
        self::assertSame('denied', $this->flashes['error'][0]);
        self::assertFalse($this->usersSvc->profileUpdated);
    }

    public function testUpdateSuccessFiltersGroups(): void
    {
        $this->usersSvc->findResult = new FakeUser(5);
        $this->userAdminFake->syncResult = (new Result())->setSuccess(true);
        $app = $this->engine(data: ['groups' => ['user', 123, null, 'admin'], 'username' => 'n', 'email' => 'e', 'password' => '']);
        $this->controller($app)->update('5');

        self::assertSame(['user', 123, 'admin'], $this->userAdminFake->lastSync);
        self::assertArrayNotHasKey('password', $this->usersSvc->lastProfile);
        self::assertSame(['/admin/users/5/edit'], $this->redirects);
    }

    public function testDeleteMissingRedirects(): void
    {
        $this->usersSvc->findResult = null;
        $this->controller($this->engine())->delete('99');
        self::assertSame(['/admin/users'], $this->redirects);
    }

    public function testDeleteDeniedAndOk(): void
    {
        $this->usersSvc->findResult = new FakeUser(5);
        $this->userAdminFake->deleteResult = (new Result())->setSuccess(false)->setReason('no');
        $this->controller($this->engine())->delete('5');
        self::assertSame('no', $this->flashes['error'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->userAdminFake->deleteResult = (new Result())->setSuccess(true);
        $this->controller($this->engine())->delete('5');
        self::assertSame('User deleted.', $this->flashes['success'][0]);
    }

    public function testToggleMissingAndDeniedAndOk(): void
    {
        $this->usersSvc->findResult = null;
        $this->controller($this->engine())->toggle('99');
        self::assertSame(['/admin/users'], $this->redirects);

        $this->redirects = [];
        $this->usersSvc->findResult = new FakeUser(5, true);
        $this->userAdminFake->activeResult = (new Result())->setSuccess(false)->setReason('locked');
        $this->controller($this->engine())->toggle('5');
        self::assertSame('locked', $this->flashes['error'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->userAdminFake->activeResult = (new Result())->setSuccess(true);
        $this->controller($this->engine())->toggle('5');
        self::assertFalse($this->userAdminFake->lastActive);
        self::assertSame('User status toggled.', $this->flashes['success'][0]);
    }

    public function testBanSelfRefused(): void
    {
        $this->usersSvc->findResult = new FakeUser(5);
        $this->controller($this->engine(actorId: 5))->ban('5');
        self::assertSame('You cannot ban your own account.', $this->flashes['error'][0]);
    }

    public function testBanMissingAndOk(): void
    {
        $this->usersSvc->findResult = null;
        $this->controller($this->engine())->ban('99');
        self::assertSame(['/admin/users'], $this->redirects);

        $this->redirects = [];
        $this->usersSvc->findResult = new FakeUser(6);
        $this->controller($this->engine(data: ['ban_message' => ' spam '], actorId: 1))->ban('6');
        self::assertSame('spam', $this->userAdminFake->lastBan[1]);
        self::assertSame('User banned.', $this->flashes['success'][0]);
    }

    public function testUnbanAndForceReset(): void
    {
        $this->usersSvc->findResult = new FakeUser(6);
        $this->controller($this->engine())->unban('6');
        self::assertSame(6, $this->userAdminFake->lastUnban->id);
        self::assertSame('Ban lifted.', $this->flashes['success'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->usersSvc->findResult = new FakeUser(6);
        $this->controller($this->engine())->forceReset('6');
        self::assertSame('Password reset requirement updated.', $this->flashes['success'][0]);

        $this->flashes = [];
        $this->redirects = [];
        $this->usersSvc->findResult = null;
        $this->controller($this->engine())->forceReset('99');
        self::assertSame(['/admin/users/99/edit'], $this->redirects);
    }

    private function controller(Engine $app): UsersController
    {
        $test = $this;

        return new class($app, $test) extends UsersController {
            public function __construct(Engine $app, private UsersControllerTest $t)
            {
                parent::__construct($app);
            }

            protected function userAdmin(): \Pubvana\Services\UserAdminService
            {
                return $this->t->userAdminFake;
            }
        };
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $shield
     */
    private function engine(
        array $data = [],
        array $query = [],
        bool $viewerSuperadmin = false,
        int $actorId = 1,
        ?array $shield = null,
        string $host = 'localhost',
        bool $secure = false,
        string $base = ''
    ): Engine {
        $test = $this;
        $app = $this->app([
            'request' => static fn(): object => new class($data, $query, $host, $secure, $base) {
                public Collection $data;
                public Collection $query;
                public bool $secure;
                public string $base;
                private string $host;
                /** @param array<string, mixed> $d @param array<string, mixed> $q */
                public function __construct(array $d, array $q, string $h, bool $s, string $b)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection($q);
                    $this->host = $h;
                    $this->secure = $s;
                    $this->base = $b;
                }

                public function getHeader(string $n): string
                {
                    return $n === 'Host' ? $this->host : '';
                }
            },
            'auth' => fn(): object => new class($test, $viewerSuperadmin, $actorId) {
                public function __construct(private UsersControllerTest $t, private bool $sup, private int $aid)
                {
                }

                public function user(): object
                {
                    return new class($this->sup, $this->aid) {
                        public function __construct(private bool $sup, private int $id)
                        {
                        }

                        public function inGroup(string $g): bool
                        {
                            return $g === 'superadmin' && $this->sup;
                        }

                        /** @return list<string> */
                        public function getGroups(): array
                        {
                            return $this->sup ? ['superadmin'] : ['user'];
                        }
                    };
                }

                public function id(): int
                {
                    return $this->aid;
                }

                public function users(): FakeUsersSvc
                {
                    return $this->t->usersSvc;
                }

                public function groups(): object
                {
                    return new class {
                        /** @return list<AuthGroup> */
                        public function all(): array
                        {
                            $a = new AuthGroup(null);
                            $a->alias = 'superadmin';
                            $b = new AuthGroup(null);
                            $b->alias = 'user';

                            return [$a, $b];
                        }
                    };
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private UsersControllerTest $t)
                {
                }

                public function flash(string $k, mixed $v): void
                {
                    $this->t->flashes[$k][] = $v;
                }
            },
            'settings' => static fn(): object => new class($test) {
                public function __construct(private UsersControllerTest $t)
                {
                }

                public function get(string $k, mixed $d = null): mixed
                {
                    return $this->t->settingsRows[$k] ?? $d;
                }
            },
            'mailer' => static fn(): object => new class($test) {
                public function __construct(private UsersControllerTest $t)
                {
                }

                /** @param array<string, mixed> $o */
                public function sendHtml(string $to, string $subject, string $body, array $o = []): void
                {
                    if ($this->t->mailThrow) {
                        throw new \RuntimeException('down');
                    }
                    $this->t->sent = ['to' => $to, 'subject' => $subject, 'body' => $body];
                }
            },
            'db' => static fn(): \PDO => Sqlite::connection(),
            'adext' => static fn(): object => new class {
                /** @return array<string, mixed> */
                public function get(string $t, string $s, array $c = []): array
                {
                    return [];
                }
            },
            'view' => static function () use ($test): object {
                return new class($test) {
                    public function __construct(private UsersControllerTest $t)
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
        if ($shield !== null) {
            $app->set('enlivenapp.flight-shield', $shield);
        }
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->fetches[] = ['view' => 'render:' . $t, 'data' => $d];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}

/**
 * Fake user entity.
 */
final class FakeUser
{
    public int $id;
    public bool $active;

    public function __construct(int $id, bool $active = true)
    {
        $this->id = $id;
        $this->active = $active;
    }

    /** @return list<string> */
    public function getGroups(): array
    {
        return ['user'];
    }

    /** @return list<string> */
    public function getPermissions(): array
    {
        return [];
    }

    public function isBanned(): bool
    {
        return false;
    }

    public function getBanMessage(): ?string
    {
        return null;
    }

    public function requiresPasswordReset(): bool
    {
        return false;
    }

    public function inGroup(string $g): bool
    {
        return false;
    }
}

/**
 * Fake users management service.
 */
final class FakeUsersSvc
{
    public ?FakeUser $findResult = null;
    public bool $lastInclude = false;
    public ?string $email = null;
    /** @var array<string, mixed> */
    public array $lastProfile = [];
    public bool $profileUpdated = false;

    /** @return list<object> */
    public function paginated(int $page, int $perPage, bool $inc): array
    {
        $this->lastInclude = $inc;

        return [];
    }

    public function count(bool $inc): int
    {
        return 7;
    }

    public function find(int $id, bool $inc): ?FakeUser
    {
        $this->lastInclude = $inc;

        return $this->findResult;
    }

    public function getEmail(object $u): ?string
    {
        return $this->email;
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(object $u, array $data): Result
    {
        $this->lastProfile = $data;
        $this->profileUpdated = true;

        return (new Result())->setSuccess(true);
    }
}

/**
 * Fake UserAdminService standing in for the typed service.
 */
final class FakeUserAdmin extends \Pubvana\Services\UserAdminService
{
    /** @var array<string, mixed> */
    public array $lastCreate = [];
    public Result $createResult;
    /** @var list<string> */
    public array $lastSync = [];
    public Result $syncResult;
    public Result $deleteResult;
    public Result $activeResult;
    public ?bool $lastActive = null;
    /** @var array{0: object, 1: ?string}|null */
    public ?array $lastBan = null;
    public ?object $lastUnban = null;

    public function __construct()
    {
        $ok = (new Result())->setSuccess(true);
        $this->createResult = $ok;
        $this->syncResult = $ok;
        $this->deleteResult = $ok;
        $this->activeResult = $ok;
    }

    public function createUser(string $u, string $e, string $p, ?string $g): Result
    {
        $this->lastCreate = ['username' => $u, 'email' => $e, 'password' => $p, 'group' => $g];

        return $this->createResult;
    }

    /** @param array<array-key, mixed> $groups */
    public function syncGroups(object $user, array $groups): Result
    {
        $this->lastSync = array_values($groups);

        return $this->syncResult;
    }

    public function deleteUser(object $user): Result
    {
        return $this->deleteResult;
    }

    public function setActive(object $user, bool $active): Result
    {
        $this->lastActive = $active;

        return $this->activeResult;
    }

    public function ban(object $user, ?string $m = null): void
    {
        $this->lastBan = [$user, $m];
    }

    public function unBan(object $user): void
    {
        $this->lastUnban = $user;
    }

    public function forceReset(object $user, bool $f): void
    {
    }
}
