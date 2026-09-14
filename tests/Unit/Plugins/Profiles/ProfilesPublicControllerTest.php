<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesPublicController;
use Pubvana\Plugins\Profiles\Models\Profile;
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
    }

    public function testShowRendersProfile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        (new Profile($this->pdo))->updateProfile(7, [
            'display_name' => 'Ada Lovelace',
            'bio' => 'Mathematician',
            'website' => 'https://example.com',
            'twitter' => 'ada',
        ]);

        $this->controller($this->engine())->show('ada');

        self::assertSame('pubvana/profiles/profile', $this->renders[0]['template']);
        $data = $this->renders[0]['data'];
        self::assertSame("Ada Lovelace's Profile", $data['title']);
        self::assertFalse($data['isOwner']);
        self::assertSame('', $data['avatar_url']);
        self::assertSame('https://example.com', $data['safe_website']);
        self::assertSame('https://twitter.com/ada', $data['twitter_url']);
    }

    public function testShowHaltsOnMissingUser(): void
    {
        $this->controller($this->engine())->show('nobody');
        self::assertSame([['code' => 404, 'msg' => 'User not found']], $this->halts);
    }

    public function testShowMarksOwnerAndAvatar(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        (new Profile($this->pdo))->updateProfile(7, ['avatar' => 'uploads/ada.png']);
        $this->currentUserId = 7;

        $this->controller($this->engine())->show('ada');

        self::assertTrue($this->renders[0]['data']['isOwner']);
        self::assertSame('/uploads/ada.png', $this->renders[0]['data']['avatar_url']);
    }

    public function testShowFallsBackToUsernameTitle(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");

        $this->controller($this->engine())->show('ada');

        self::assertSame("ada's Profile", $this->renders[0]['data']['title']);
    }

    public function testShowGuardsUnsafeWebsite(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->pdo->exec("INSERT INTO profiles (user_id, website) VALUES (7, 'javascript:alert(1)')");

        $this->controller($this->engine())->show('ada');

        self::assertNull($this->renders[0]['data']['safe_website']);
    }

    public function testEditRendersForOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $this->controller($this->engine())->edit('ada');

        self::assertSame('pubvana/profiles/profile_edit', $this->renders[0]['template']);
        self::assertSame('Edit Profile', $this->renders[0]['data']['title']);
    }

    public function testEditHaltsOnMissingUser(): void
    {
        $this->controller($this->engine())->edit('nobody');
        self::assertSame([['code' => 404, 'msg' => 'User not found']], $this->halts);
    }

    public function testEditRefusesNonOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;

        $this->controller($this->engine())->edit('ada');

        self::assertSame('You can only edit your own profile.', $this->flashes['danger'][0]);
        self::assertSame(['/'], $this->redirects);
    }

    public function testUpdateHappyPath(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $app = $this->engine(data: ['display_name' => 'Ada L']);
        $this->controller($app)->update('ada');

        self::assertSame(['/profile/ada'], $this->redirects);
        $profile = (new Profile($this->pdo))->findByUserId(7);
        self::assertNotNull($profile);
        self::assertSame('Ada L', $profile->display_name);
    }

    public function testUpdateHaltsOnMissingUser(): void
    {
        $this->currentUserId = 7;
        $this->controller($this->engine())->update('nobody');
        self::assertSame([['code' => 404, 'msg' => 'User not found']], $this->halts);
    }

    public function testUpdateRefusesNonOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;

        $this->controller($this->engine(data: []))->update('ada');

        self::assertSame('You can only edit your own profile.', $this->flashes['danger'][0]);
        self::assertSame(['/profile/ada'], $this->redirects);
    }

    public function testUpdateRejectsBadWebsite(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;

        $app = $this->engine(data: ['website' => 'javascript:alert(1)']);
        $this->controller($app)->update('ada');

        self::assertSame('Website must be a full http:// or https:// URL.', $this->flashes['danger'][0]);
        self::assertSame(['/profile/ada/edit'], $this->redirects);
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
        };
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
            'db' => static fn(): PDO => $pdo,
            'profiles' => static fn(): Profile => new Profile($pdo),
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
            'adext' => static fn(): object => new class {
                /** @return array<string, mixed> */
                public function get(string $t, string $s, array $c = []): array
                {
                    return [];
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
