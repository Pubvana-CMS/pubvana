<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use Enlivenapp\FlightShield\Result;
use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesPublicController;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Plugins\Profiles\Services\ProfileSecurityService;
use Pubvana\Services\UrlService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ProfilesPublicController show/edit/update over the real Profile model.
 */
#[CoversClass(ProfilesPublicController::class)]
final class ProfilesPublicControllerTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    public array $renders = [];
    /** @var list<array{code: int, msg: string}> */
    public array $halts = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    public ?int $currentUserId = null;
    /** @var array<string, array<string, array<string, array<string, mixed>>>> */
    public array $tabContributions = [];
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
        $this->renders = [];
        $this->halts = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->currentUserId = null;
        $this->securityResult = (new Result())->setSuccess(true);
        $this->twoFactor = false;
        $this->securitySessions = [];
        $this->sessionRevoked = true;
        $this->revokedOthers = 0;
        $this->securityCalls = [];
    }

    public function testShowRendersProfile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        (new Profile($this->pdo))->updateProfile(7, [
            'display_name' => 'Ada Lovelace',
            'bio' => 'Mathematician',
            'website' => 'https://example.com',
            'twitter' => 'https://x.com/ada',
        ]);

        $this->controller($this->engine())->show('7');

        self::assertSame('pubvana/profiles/profile', $this->renders[0]['template']);
        $data = $this->renders[0]['data'];
        self::assertSame("Ada Lovelace's Profile", $data['title']);
        self::assertFalse($data['isOwner']);
        self::assertSame('', $data['avatar_url']);
        self::assertSame('https://example.com', $data['safe_website']);
        self::assertSame('https://x.com/ada', $data['twitter_url']);
    }

    public function testShowHaltsOnMissingUser(): void
    {
        $this->controller($this->engine())->show('999');
        self::assertSame([['code' => 404, 'msg' => 'User not found']], $this->halts);
    }

    public function testShowMarksOwnerAndAvatar(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        (new Profile($this->pdo))->updateProfile(7, ['avatar' => 'uploads/ada.png']);
        $this->currentUserId = 7;

        $this->controller($this->engine())->show('7');

        self::assertTrue($this->renders[0]['data']['isOwner']);
        self::assertSame('/uploads/ada.png', $this->renders[0]['data']['avatar_url']);
    }

    public function testShowFallsBackToUsernameTitle(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");

        $this->controller($this->engine())->show('7');

        self::assertSame("ada's Profile", $this->renders[0]['data']['title']);
    }

    public function testShowGuardsUnsafeWebsite(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->pdo->exec("INSERT INTO profiles (user_id, website) VALUES (7, 'javascript:alert(1)')");

        $this->controller($this->engine())->show('7');

        self::assertNull($this->renders[0]['data']['safe_website']);
    }

    public function testEditRendersForOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $this->controller($this->engine())->edit('7');

        self::assertSame('pubvana/profiles/profile_edit', $this->renders[0]['template']);
        self::assertSame('Edit Profile', $this->renders[0]['data']['title']);
    }

    public function testEditHaltsOnMissingUser(): void
    {
        $this->controller($this->engine())->edit('999');
        self::assertSame([['code' => 404, 'msg' => 'User not found']], $this->halts);
    }

    public function testEditRefusesNonOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;

        $this->controller($this->engine())->edit('7');

        self::assertSame('You can only edit your own profile.', $this->flashes['danger'][0]);
        self::assertSame(['/'], $this->redirects);
    }

    public function testUpdateHappyPath(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $app = $this->engine(data: ['display_name' => 'Ada L']);
        $this->controller($app)->update('7');

        self::assertSame(['/profile/7'], $this->redirects);
        $profile = (new Profile($this->pdo))->findByUserId(7);
        self::assertNotNull($profile);
        self::assertSame('Ada L', $profile->display_name);
    }

    public function testUpdateHaltsOnMissingUser(): void
    {
        $this->currentUserId = 7;
        $this->controller($this->engine())->update('999');
        self::assertSame([['code' => 404, 'msg' => 'User not found']], $this->halts);
    }

    public function testUpdateRefusesNonOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;

        $this->controller($this->engine(data: []))->update('7');

        self::assertSame('You can only edit your own profile.', $this->flashes['danger'][0]);
        self::assertSame(['/profile/7'], $this->redirects);
    }

    public function testUpdateRejectsBadWebsite(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $app = $this->engine(data: ['website' => 'javascript:alert(1)']);
        $this->controller($app)->update('7');

        self::assertSame('Website, Twitter, Facebook and LinkedIn must be full http:// or https:// URLs.', $this->flashes['danger'][0]);
        self::assertSame(['/profile/7/edit'], $this->redirects);
    }

    private function controller(Engine $app): ProfilesPublicController
    {
        $test = $this;

        return new class($app, $test) extends ProfilesPublicController {
            public function __construct(Engine $app, private ProfilesPublicControllerTest $t)
            {
                parent::__construct($app);
            }

            public function render(string $template, array $data = []): void
            {
                $this->t->renders[] = ['template' => $template, 'data' => $data];
            }

            protected function securityService(): ProfileSecurityService
            {
                return new class ($this->app, $this->t) extends ProfileSecurityService {
                    public function __construct(Engine $app, private ProfilesPublicControllerTest $t)
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
        };
    }

    public function testDisplayPageHasNoTabs(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $this->controller($this->engine())->show('7');

        $data = $this->renders[0]['data'];
        self::assertArrayNotHasKey('tabs', $data);
        self::assertArrayNotHasKey('security', $data);
    }

    public function testOwnerGetsSecurityTabOnEditPage(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $this->controller($this->engine())->edit('7');

        self::assertSame('pubvana/profiles/profile_edit', $this->renders[0]['template']);
        $data = $this->renders[0]['data'];
        self::assertSame(['profile', 'security'], array_column($data['tabs'], 'id'));
        self::assertSame('ada@example.com', $data['security']['email']);
        self::assertTrue($data['hasTabs']);
    }

    public function testEditRendersRegisteredTab(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $seen = null;

        $this->tabContributions['profile.tab']['default'] = [
            'pubvana/addresses' => [
                'label'    => 'Addresses',
                'callable' => function (object $user) use (&$seen): string {
                    $seen = $user;

                    return '<input name="street">';
                },
            ],
        ];

        $this->controller($this->engine([], ['tab' => 'addresses']))->edit('7');

        $data = $this->renders[0]['data'];
        self::assertSame(['profile', 'security', 'addresses'], array_column($data['tabs'], 'id'));
        self::assertSame('addresses', $data['activeTabId']);
        self::assertSame('<input name="street">', $data['activeTabOutput']);
        self::assertSame(7, (int) $seen->id);
    }

    public function testEditRendersRequestedSecurityTab(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $this->controller($this->engine([], ['tab' => 'security']))->edit('7');

        $data = $this->renders[0]['data'];
        self::assertSame('security', $data['activeTabId']);
        self::assertFalse($data['tabs'][0]['active']);
        self::assertTrue($data['tabs'][1]['active']);
    }

    public function testUpdatePasswordPassesFieldsToService(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $this->controller($this->engine([
            'current_password' => 'old-pass',
            'password'         => 'new-pass',
            'password_confirm' => 'new-pass',
            'return_url'       => '/profile/7?tab=security',
        ]))->updatePassword('7');

        self::assertSame([['old-pass', 'new-pass', 'new-pass']], $this->securityCalls['password']);
        self::assertSame('Password changed. Other sessions were signed out.', $this->flashes['success'][0]);
        self::assertSame(['/profile/7?tab=security'], $this->redirects);
    }

    public function testSecurityActionRefusesAnotherAccount(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;

        $this->controller($this->engine())->updateCredentials('7');

        self::assertSame('You can only change your own security settings.', $this->flashes['danger'][0]);
        self::assertSame(['/'], $this->redirects);
        self::assertSame([], $this->securityCalls);
    }

    public function testRevokeSessionsSignsOutOtherDevices(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->revokedOthers = 3;

        $this->controller($this->engine())->revokeSessions('7');

        self::assertSame('Signed out 3 other sessions.', $this->flashes['success'][0]);
    }

    public function testUnknownTabFallsBackToProfile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $this->controller($this->engine([], ['tab' => 'nope']))->edit('7');

        self::assertSame('profile', $this->renders[0]['data']['activeTabId']);
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
            'db' => static fn(): PDO => $pdo,
            'profiles' => static fn(): Profile => new Profile($pdo),
            'url' => static fn(): UrlService => new UrlService(\Flight::app()),
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/profile';
                }
            },
            'auth' => static fn(): object => new class($test) {
                public function __construct(private ProfilesPublicControllerTest $t)
                {
                }

                public function loggedIn(): bool
                {
                    return $this->t->currentUserId !== null;
                }

                public function user(): ?object
                {
                    if ($this->t->currentUserId === null) {
                        return null;
                    }
                    $id = $this->t->currentUserId;

                    return new class($id) {
                        public function __construct(private int $id)
                        {
                        }

                        public function __get(string $name): mixed
                        {
                            if ($name === 'id') {
                                return $this->id;
                            }

                            return null;
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
            'session' => static fn(): object => new class($test) {
                public function __construct(private ProfilesPublicControllerTest $t)
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
            'adext' => static fn(): object => new class($test) {
                public function __construct(private ProfilesPublicControllerTest $t)
                {
                }

                /**
                 * @param array<string, mixed> $c
                 * @return array<string, array<string, mixed>>
                 */
                public function get(string $t, string $s, array $c = []): array
                {
                    return $this->t->tabContributions[$t][$s] ?? [];
                }
            },
            'view' => static fn(): object => new class {
                public function fetch(string $v, ?array $d = null): string
                {
                    return 'C:' . $v;
                }
            },
        ]);
        $app->set('CMS.siteName', 'Test Site');
        $app->set('CMS.siteUrl', 'https://example.org');
        $app->set('flight.base_url', '/');
        $app->map('halt', function (int $code, string $msg) use ($test): void {
            $test->halts[] = ['code' => $code, 'msg' => $msg];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        \Flight::setEngine($app);

        return $app;
    }
}
