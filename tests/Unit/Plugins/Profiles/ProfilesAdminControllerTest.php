<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use Enlivenapp\FlightShield\Models\User;
use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Services\UrlService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ProfilesAdminController over the real Profile model.
 */
#[CoversClass(ProfilesAdminController::class)]
final class ProfilesAdminControllerTest extends TestCase
{
    private PDO $pdo;

    /** @var array<string, mixed> */
    public array $fetches = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    public int $currentUserId = 7;
    public bool $canEditAny = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ProfilesSchema::create($this->pdo);
        $this->fetches = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->currentUserId = 7;
        $this->canEditAny = false;
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
        $app = $this->engine(data: ['website' => 'javascript:alert(1)']);
        (new ProfilesAdminController($app))->update('7');

        self::assertSame('Website must be a full http:// or https:// URL.', $this->flashes['error'][0]);
        self::assertSame(['/admin/profile'], $this->redirects);
    }

    public function testUpdateHonorsPostedReturnUrl(): void
    {
        $app = $this->engine(data: ['display_name' => 'Ada', 'return_url' => '/admin/users/7/edit']);
        (new ProfilesAdminController($app))->update('7');

        self::assertSame(['/admin/users/7/edit'], $this->redirects);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function engine(array $data = []): Engine
    {
        $test = $this;
        $pdo = $this->pdo;
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
            },
            'media' => static fn(): object => new class {
                public function avatarPicker(string $field, string $value): string
                {
                    return 'AVATAR';
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
