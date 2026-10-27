<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Result;
use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController as RealProfilesAdminController;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Plugins\Profiles\Services\ProfileSecurityService;
use Pubvana\Services\UrlService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Test double for the admin controller.
 *
 * The controller builds its security service directly, so this subclass
 * replaces it with a recorder. The static pointer is set by setUp().
 */
final class ProfilesAdminController extends RealProfilesAdminController
{
    public static ?ProfilesAdminControllerTest $test = null;

    protected function securityService(): ProfileSecurityService
    {
        $test = self::$test;
        if ($test === null) {
            return parent::securityService();
        }

        return new class ($this->app, $test) extends ProfileSecurityService {
            public function __construct(Engine $app, private ProfilesAdminControllerTest $t)
            {
                parent::__construct($app);
            }

            public function twoFactorEnabled(): bool
            {
                return $this->t->twoFactor;
            }

            /** @return list<array<string, mixed>> */
            public function sessions(object $user): array
            {
                return $this->t->securitySessions;
            }

            /** @param array<string, mixed> $data */
            public function updateCredentials(object $user, array $data): Result
            {
                $this->t->securityCalls['credentials'][] = $data;

                return $this->t->securityResult;
            }

            public function updatePassword(object $user, string $current, string $new, string $confirm): Result
            {
                $this->t->securityCalls['password'][] = [$current, $new, $confirm];

                return $this->t->securityResult;
            }

            public function revokeSession(object $user, int $id): bool
            {
                $this->t->securityCalls['revoke'][] = $id;

                return $this->t->sessionRevoked;
            }

            public function revokeOtherSessions(object $user): int
            {
                $this->t->securityCalls['revokeOthers'][] = true;

                return $this->t->revokedOthers;
            }
        };
    }
}

/**
 * ProfilesAdminController over the real Profile model.
 */
#[CoversClass(RealProfilesAdminController::class)]
final class ProfilesAdminControllerTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var list<array{0: int, 1: string}> */
    public array $avatarSweeps = [];
    /** @var list<string> */
    public array $legacyDeletes = [];
    public int $currentUserId = 7;
    public bool $canEditAny = false;
    public Result $securityResult;
    public bool $twoFactor = false;
    /** @var list<array<string, mixed>> */
    public array $securitySessions = [];
    public bool $sessionRevoked = true;
    public int $revokedOthers = 0;
    /** @var array<string, list<mixed>> */
    public array $securityCalls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ProfilesSchema::create($this->pdo);
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->avatarSweeps = [];
        $this->legacyDeletes = [];
        $this->currentUserId = 7;
        $this->canEditAny = false;
        $this->securityResult = (new Result())->setSuccess(true);
        $this->twoFactor = false;
        $this->securitySessions = [];
        $this->sessionRevoked = true;
        $this->revokedOthers = 0;
        $this->securityCalls = [];
        ProfilesAdminController::$test = $this;
    }

    public function testIndexRendersOwnProfile(): void
    {
        (new ProfilesAdminController($this->engine()))->index();

        self::assertSame('pubvana/profiles/admin/profile/index', $this->fetches[0]['view']);
        $data = $this->fetches[0]['data'];
        self::assertSame('My Profile', $data['pageTitle']);
        self::assertSame(7, (int) $data['profile']->user_id);
        self::assertSame('AVATAR', $data['avatarPicker']);
        self::assertSame('/admin/profile', $data['returnUrl']);
        self::assertSame('/admin/profile', $data['adminBase']);
    }

    public function testShowOwnProfile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");

        (new ProfilesAdminController($this->engine()))->show('7');

        self::assertSame('pubvana/profiles/admin/profile/index', $this->fetches[0]['view']);
        self::assertStringContainsString('ada', $this->fetches[0]['data']['pageTitle']);
        self::assertSame('/admin/users/7/edit', $this->fetches[0]['data']['returnUrl']);
    }

    public function testShowOtherWithoutPermissionRedirects(): void
    {
        (new ProfilesAdminController($this->engine()))->show('9');

        self::assertSame('You do not have permission to edit other users\' profiles.', $this->flashes['error'][0]);
        self::assertSame(['/admin/users'], $this->redirects);
    }

    public function testShowOtherWithPermission(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (9, 'grace', 1)");
        $this->canEditAny = true;

        (new ProfilesAdminController($this->engine()))->show('9');

        self::assertSame('pubvana/profiles/admin/profile/index', $this->fetches[0]['view']);
        self::assertSame(9, (int) $this->fetches[0]['data']['profile']->user_id);
    }

    public function testShowUnknownUserRedirectsWithoutTouchingProfiles(): void
    {
        // Superadmins bypass profile.edit.any, so this is the exact path a
        // superadmin takes with a bogus id. It must be a clean redirect, not
        // an insert against the profiles user_id foreign key.
        $this->canEditAny = true;

        (new ProfilesAdminController($this->engine()))->show('99999');

        self::assertSame('User not found.', $this->flashes['error'][0]);
        self::assertSame(['/admin/users'], $this->redirects);
        self::assertSame([], $this->fetches, 'no view may render for a missing user');

        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM profiles');
        self::assertNotFalse($stmt);
        self::assertSame(0, (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? -1), 'nothing may be inserted');
    }

    public function testShowDeletedUserRedirects(): void
    {
        // findById() excludes soft-deleted rows, so a deleted user reads as not found.
        $this->pdo->exec("INSERT INTO users (id, username, active, deleted_at) VALUES (9, 'gone', 1, '2026-01-01 00:00:00')");
        $this->canEditAny = true;

        (new ProfilesAdminController($this->engine()))->show('9');

        self::assertSame('User not found.', $this->flashes['error'][0]);
        self::assertSame(['/admin/users'], $this->redirects);
    }

    public function testUpdateOwnProfile(): void
    {
        $app = $this->engine(data: ['display_name' => 'Ada Lovelace']);
        (new ProfilesAdminController($app))->update('7');

        self::assertSame('Profile updated.', $this->flashes['success'][0]);
        self::assertSame(['/admin/profile'], $this->redirects);
        $profile = (new Profile($this->pdo))->findByUserId(7);
        self::assertNotNull($profile);
        self::assertSame('Ada Lovelace', $profile->display_name);
    }

    public function testUpdateOtherWithoutPermissionRedirects(): void
    {
        $app = $this->engine(data: ['display_name' => 'x']);
        (new ProfilesAdminController($app))->update('9');

        self::assertSame('You do not have permission to edit other users\' profiles.', $this->flashes['error'][0]);
        self::assertSame(['/admin/users'], $this->redirects);
    }

    public function testUpdateRejectsBadWebsite(): void
    {
        // A rejected write flashes the error, bounces to the form, and saves
        // nothing: findOrCreate() may create the row, but the bad website is
        // never persisted (updateFromArray returns false before save()).
        $app = $this->engine(data: ['display_name' => 'Ada', 'website' => 'javascript:alert(1)']);
        (new ProfilesAdminController($app))->update('7');

        self::assertSame('Website, Twitter, Facebook and LinkedIn must be full http:// or https:// URLs.', $this->flashes['error'][0]);
        self::assertSame(['/admin/profile'], $this->redirects);
        self::assertArrayNotHasKey('success', $this->flashes);

        $profile = (new Profile($this->pdo))->findByUserId(7);
        self::assertNotNull($profile);
        self::assertNull($profile->website, 'the rejected website must not be stored');
        self::assertNull($profile->display_name, 'nothing from a rejected write is saved');
    }

    public function testUpdateHonorsPostedReturnUrl(): void
    {
        $app = $this->engine(data: ['display_name' => 'Ada', 'return_url' => '/admin/users/7/edit']);
        (new ProfilesAdminController($app))->update('7');

        self::assertSame(['/admin/users/7/edit'], $this->redirects);
    }

    public function testUpdateSweepsAvatarFilesTheRowNoLongerPointsAt(): void
    {
        (new Profile($this->pdo))->updateProfile(7, ['avatar' => 'uploads/avatars/7-old.webp']);

        $app = $this->engine(data: ['avatar' => '']);
        (new ProfilesAdminController($app))->update('7');

        self::assertSame([[7, '']], $this->avatarSweeps);
        self::assertSame(['uploads/avatars/7-old.webp'], $this->legacyDeletes);

        $profile = (new Profile($this->pdo))->findByUserId(7);
        self::assertNotNull($profile);
        self::assertNull($profile->avatar);
    }

    public function testIndexOffersProfileAndSecurityTabs(): void
    {
        (new ProfilesAdminController($this->engine()))->index();

        $data = $this->fetches[0]['data'];
        self::assertSame(['profile', 'security'], array_column($data['tabs'], 'id'));
        self::assertTrue($data['tabs'][0]['active']);
        self::assertFalse($data['tabs'][1]['active']);
        self::assertSame('ada@example.com', $data['security']['email']);
    }

    public function testRequestedSecurityTabIsActive(): void
    {
        (new ProfilesAdminController($this->engine([], ['tab' => 'security'])))->index();

        $data = $this->fetches[0]['data'];
        self::assertFalse($data['tabs'][0]['active']);
        self::assertTrue($data['tabs'][1]['active']);
    }

    public function testShowOtherUserHasNoSecurityTab(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (9, 'grace', 1)");
        $this->canEditAny = true;

        (new ProfilesAdminController($this->engine()))->show('9');

        $data = $this->fetches[0]['data'];
        self::assertSame(['profile'], array_column($data['tabs'], 'id'));
        self::assertNull($data['security']);
    }

    public function testUpdatePasswordPassesFieldsToService(): void
    {
        $controller = new ProfilesAdminController($this->engine([
            'current_password' => 'old-pass',
            'password'         => 'new-pass',
            'password_confirm' => 'new-pass',
            'return_url'       => '/admin/profile?tab=security',
        ]));

        $controller->updatePassword('7');

        self::assertSame([['old-pass', 'new-pass', 'new-pass']], $this->securityCalls['password']);
        self::assertSame('Password changed. Other sessions were signed out.', $this->flashes['success'][0]);
    }

    public function testUpdatePasswordFailureFlashesReason(): void
    {
        $this->securityResult = (new Result())->setSuccess(false)->setReason('Your current password is incorrect.');

        (new ProfilesAdminController($this->engine([
            'current_password' => 'wrong',
            'password'         => 'new-pass',
            'password_confirm' => 'new-pass',
        ])))->updatePassword('7');

        self::assertSame('Your current password is incorrect.', $this->flashes['error'][0]);
        self::assertCount(1, $this->redirects);
    }

    public function testUpdateCredentialsPassesOnlyTheEmail(): void
    {
        (new ProfilesAdminController($this->engine([
            '_csrf_token' => 'token',
            'email'       => 'ada@example.com',
            'return_url'  => '/admin/profile?tab=security',
        ])))->updateCredentials('7');

        self::assertSame([['email' => 'ada@example.com']], $this->securityCalls['credentials']);
        self::assertSame('Account updated.', $this->flashes['success'][0]);
    }

    public function testSecurityActionRequiresOwner(): void
    {
        (new ProfilesAdminController($this->engine()))->updatePassword('9');

        self::assertSame('You can only change your own security settings.', $this->flashes['error'][0]);
        self::assertSame(['/admin/profile'], $this->redirects);
        self::assertSame([], $this->securityCalls);
    }

    public function testRevokeSessionsSignsOutOtherDevices(): void
    {
        $this->revokedOthers = 2;

        (new ProfilesAdminController($this->engine()))->revokeSessions('7');

        self::assertSame('Signed out 2 other sessions.', $this->flashes['success'][0]);
    }

    public function testRevokeSingleSessionReportsRefusal(): void
    {
        $this->sessionRevoked = false;

        (new ProfilesAdminController($this->engine(['session_id' => '5'])))->revokeSessions('7');

        self::assertSame([5], $this->securityCalls['revoke']);
        self::assertSame('That session is already gone or is the one you are using.', $this->flashes['error'][0]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $query
     */
    private function engine(array $data = [], array $query = []): Engine
    {
        $test = $this;
        $pdo = $this->pdo;
        $app = $this->app([
            // Settings-store stand-in: resolves CMS.* the way production does.
            'settings' => static fn(): object => new class {
                public function get(string $key, mixed $default = null): mixed
                {
                    return \Flight::app()->get($key) ?? $default;
                }
            },
            'request' => static fn(): object => new class($data, $query) {
                public Collection $data;
                public Collection $query;
                /**
                 * @param array<string, mixed> $d
                 * @param array<string, mixed> $q
                 */
                public function __construct(array $d, array $q)
                {
                    $this->data = new Collection($d);
                    $this->query = new Collection($q);
                }
            },
            'profiles' => static fn(): Profile => new Profile($pdo),
            'db' => static fn(): PDO => $pdo,
            'url' => static fn(): UrlService => new UrlService($test->bareEngine()),
            'auth' => static fn(): object => new class($test) {
                public function __construct(private ProfilesAdminControllerTest $t)
                {
                }

                public function user(): object
                {
                    $t = $this->t;

                    return new class($t) {
                        public function __construct(private ProfilesAdminControllerTest $t)
                        {
                        }

                        public function __get(string $name): mixed
                        {
                            if ($name === 'id') {
                                return $this->t->currentUserId;
                            }

                            return null;
                        }

                        public function can(string $permission): bool
                        {
                            return $this->t->canEditAny;
                        }

                        /** @return list<string> */
                        public function getGroups(): array
                        {
                            return ['admin'];
                        }
                    };
                }

                public function users(): object
                {
                    return new class {
                        public function getEmail(object $user): ?string
                        {
                            return 'ada@example.com';
                        }
                    };
                }
            },
            'media' => static fn(): object => new class($test) {
                public function __construct(private ProfilesAdminControllerTest $t)
                {
                }

                public function avatarPicker(string $field, string $value): string
                {
                    return 'AVATAR';
                }

                public function sweepAvatars(int $userId, string $keep = ''): void
                {
                    $this->t->avatarSweeps[] = [$userId, $keep];
                }

                public function deleteLegacyAvatar(int $userId, string $oldPath): void
                {
                    $this->t->legacyDeletes[] = $oldPath;
                }
            },
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/profile';
                }
            },
            'session' => static fn(): object => new class($test) {
                public function __construct(private ProfilesAdminControllerTest $t)
                {
                }

                public function id(): ?string
                {
                    return 'current-session';
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
                    public function __construct(private ProfilesAdminControllerTest $t)
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
        \Flight::setEngine($app);

        return $app;
    }

    private function bareEngine(): Engine
    {
        $app = new Engine();
        $app->init();

        return $app;
    }
}
