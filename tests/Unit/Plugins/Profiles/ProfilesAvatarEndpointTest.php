<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use flight\Engine;
use flight\util\Collection;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController;
use Pubvana\Plugins\Profiles\Controllers\ProfilesPublicController;
use Pubvana\Plugins\Profiles\Models\Profile;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * The avatar upload endpoints on both profile controllers.
 *
 * The Media service is a stub here: these tests cover the controller's own
 * decisions (ownership, missing file, error mapping, old-file cleanup), not
 * image processing. MediaAvatarServiceTest covers the storage itself.
 */
#[CoversClass(ProfilesAdminController::class)]
#[CoversClass(ProfilesPublicController::class)]
final class ProfilesAvatarEndpointTest extends TestCase
{
    private PDO $pdo;

    /** @var list<array{data: mixed, code: int}> */
    public array $jsons = [];
    /** @var list<array{code: int, msg: string}> */
    public array $halts = [];
    /** @var list<string> */
    public array $redirects = [];
    /** @var array<string, list<string>> */
    public array $flashes = [];
    /** @var list<array{userId: int, path: string}> */
    public array $legacyDeletes = [];
    /** @var list<string> */
    public array $fileDeletes = [];
    public ?int $currentUserId = null;
    public bool $canEditAny = false;
    /** @var array<string, mixed>|null */
    public ?array $uploadedFile = null;
    public string $storedPath = 'uploads/avatars/7.webp';
    public bool $storeThrows = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ProfilesSchema::create($this->pdo);
        $this->jsons = [];
        $this->halts = [];
        $this->redirects = [];
        $this->flashes = [];
        $this->legacyDeletes = [];
        $this->fileDeletes = [];
        $this->currentUserId = null;
        $this->canEditAny = false;
        $this->uploadedFile = null;
        $this->storedPath = 'uploads/avatars/7.webp';
        $this->storeThrows = false;
    }

    // -----------------------------------------------------------------
    // Admin endpoint
    // -----------------------------------------------------------------

    public function testAdminAvatarStoresAndReturnsThePath(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame([[
            'data' => ['success' => true, 'url' => '/uploads/avatars/7.webp', 'path' => 'uploads/avatars/7.webp'],
            'code' => 200,
        ]], $this->jsons);
    }

    public function testAdminAvatarRejectsAnotherUserWithoutPermission(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;
        $this->canEditAny = false;
        $this->uploadedFile = $this->validUpload();

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame([['code' => 403, 'msg' => 'You do not have permission to edit this profile.']], $this->halts);
        self::assertSame([], $this->jsons);
    }

    public function testAdminAvatarAllowsAnotherUserWithPermission(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;
        $this->canEditAny = true;
        $this->uploadedFile = $this->validUpload();

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame([], $this->halts);
        self::assertSame(200, $this->jsons[0]['code']);
    }

    public function testAdminAvatarRejectsAMissingFile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = null;

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame([[
            'data' => ['error' => 'No file uploaded or upload error.'],
            'code' => 400,
        ]], $this->jsons);
    }

    public function testAdminAvatarRejectsAMalformedFileEntry(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        // Missing tmp_name and size: storeAvatar() reads both directly.
        $this->uploadedFile = ['name' => 'me.png', 'error' => UPLOAD_ERR_OK];

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame(400, $this->jsons[0]['code']);
        self::assertSame([], $this->fileDeletes, 'nothing is stored or cleaned up');
    }

    public function testAdminAvatarRejectsAFailedUploadCode(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = [
            'name' => 'me.png',
            'tmp_name' => '/tmp/whatever',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 10,
        ];

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame(400, $this->jsons[0]['code']);
    }

    public function testAdminAvatarMapsAValidationFailureTo422(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();
        $this->storeThrows = true;

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame(422, $this->jsons[0]['code']);
        self::assertArrayHasKey('error', $this->jsons[0]['data']);
    }

    public function testAdminAvatarDeletesThePreviousAvatarFile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();
        (new Profile($this->pdo))->updateProfile(7, ['avatar' => 'uploads/avatars/7-old.webp']);

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        self::assertSame(['uploads/avatars/7-old.webp'], $this->fileDeletes);
        self::assertSame([], $this->legacyDeletes);
    }

    public function testAdminAvatarFallsBackToLegacyCleanupForALibraryPath(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();
        (new Profile($this->pdo))->updateProfile(7, ['avatar' => 'uploads/2026/01/old.png']);

        (new ProfilesAdminController($this->adminEngine()))->avatar('7');

        // deleteAvatarFile() refuses a non-avatar path, so the legacy sweep runs.
        self::assertSame(['uploads/2026/01/old.png'], $this->fileDeletes);
        self::assertSame([['userId' => 7, 'path' => 'uploads/2026/01/old.png']], $this->legacyDeletes);
    }

    // -----------------------------------------------------------------
    // Public endpoint
    // -----------------------------------------------------------------

    public function testPublicAvatarStoresForTheOwner(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();

        (new ProfilesPublicController($this->publicEngine()))->avatar('7');

        self::assertSame([[
            'data' => ['success' => true, 'url' => '/uploads/avatars/7.webp', 'path' => 'uploads/avatars/7.webp'],
            'code' => 200,
        ]], $this->jsons);
    }

    public function testPublicAvatarHaltsOnAMissingUser(): void
    {
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();

        (new ProfilesPublicController($this->publicEngine()))->avatar('999');

        self::assertSame([['code' => 404, 'msg' => 'User not found']], $this->halts);
        self::assertSame([], $this->jsons);
    }

    public function testPublicAvatarHaltsForAnotherUser(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 9;
        $this->uploadedFile = $this->validUpload();

        (new ProfilesPublicController($this->publicEngine()))->avatar('7');

        self::assertSame([['code' => 403, 'msg' => 'You can only change your own avatar.']], $this->halts);
        self::assertSame([], $this->jsons);
    }

    public function testPublicAvatarRejectsAMissingFile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = null;

        (new ProfilesPublicController($this->publicEngine()))->avatar('7');

        self::assertSame([[
            'data' => ['error' => 'No file uploaded or upload error.'],
            'code' => 400,
        ]], $this->jsons);
    }

    public function testPublicAvatarMapsAValidationFailureTo422(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();
        $this->storeThrows = true;

        (new ProfilesPublicController($this->publicEngine()))->avatar('7');

        self::assertSame(422, $this->jsons[0]['code']);
    }

    public function testPublicAvatarDeletesThePreviousAvatarFile(): void
    {
        $this->pdo->exec("INSERT INTO users (id, username, active) VALUES (7, 'ada', 1)");
        $this->currentUserId = 7;
        $this->uploadedFile = $this->validUpload();
        (new Profile($this->pdo))->updateProfile(7, ['avatar' => 'uploads/avatars/7-old.webp']);

        (new ProfilesPublicController($this->publicEngine()))->avatar('7');

        self::assertSame(['uploads/avatars/7-old.webp'], $this->fileDeletes);
    }

    // -----------------------------------------------------------------
    // Harnesses
    // -----------------------------------------------------------------

    /**
     * A complete $_FILES entry. The controllers validate the shape before
     * handing it to storeAvatar(), so every key has to be present.
     *
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}
     */
    private function validUpload(): array
    {
        return [
            'name' => 'me.png',
            'type' => 'image/png',
            'tmp_name' => '/tmp/pv-avatar-test.png',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
        ];
    }

    private function mediaStub(): object
    {
        $test = $this;

        return new class($test) {
            public function __construct(private ProfilesAvatarEndpointTest $t)
            {
            }

            public function avatarPicker(string $field, string $value, string $uploadUrl = ''): string
            {
                return 'AVATAR';
            }

            public function publicAvatarPicker(string $field, string $value, string $uploadUrl): string
            {
                return 'AVATAR';
            }

            /** @param array<string, mixed> $file */
            public function storeAvatar(int $userId, array $file): string
            {
                if ($this->t->storeThrows) {
                    throw new \InvalidArgumentException('Not a valid image.');
                }

                return $this->t->storedPath;
            }

            public function deleteAvatarFile(string $relativePath): bool
            {
                $this->t->fileDeletes[] = $relativePath;

                return str_starts_with($relativePath, 'uploads/avatars/');
            }

            public function deleteLegacyAvatar(int $userId, string $oldPath): void
            {
                $this->t->legacyDeletes[] = ['userId' => $userId, 'path' => $oldPath];
            }
        };
    }

    private function requestStub(): object
    {
        $test = $this;

        return new class($test) {
            public Collection $data;
            public Collection $query;
            public object $files;

            public function __construct(private ProfilesAvatarEndpointTest $t)
            {
                $this->data = new Collection([]);
                $this->query = new Collection([]);
                $file = $this->t->uploadedFile;
                $this->files = new class($file) {
                    public mixed $file;
                    public function __construct(mixed $file)
                    {
                        $this->file = $file;
                    }
                };
            }
        };
    }

    private function authStub(bool $withPermission): object
    {
        $test = $this;

        return new class($test, $withPermission) {
            public function __construct(
                private ProfilesAvatarEndpointTest $t,
                private bool $withPermission
            ) {
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
                $can = $this->withPermission && $this->t->canEditAny;

                return new class($id, $can) {
                    public function __construct(private int $id, private bool $can)
                    {
                    }

                    public function __get(string $name): mixed
                    {
                        return $name === 'id' ? $this->id : null;
                    }

                    public function can(string $permission): bool
                    {
                        return $this->can;
                    }

                    /** @return list<string> */
                    public function getGroups(): array
                    {
                        return ['admin'];
                    }
                };
            }
        };
    }

    private function sessionStub(): object
    {
        $test = $this;

        return new class($test) {
            public function __construct(private ProfilesAvatarEndpointTest $t)
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
        };
    }

    private function adminEngine(): Engine
    {
        $test = $this;
        $pdo = $this->pdo;
        $app = $this->app([
            'request' => fn(): object => $this->requestStub(),
            'profiles' => static fn(): Profile => new Profile($pdo),
            'db' => static fn(): PDO => $pdo,
            'media' => fn(): object => $this->mediaStub(),
            'auth' => fn(): object => $this->authStub(true),
            'session' => fn(): object => $this->sessionStub(),
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/profile';
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
        $app->set('admin.topNav', []);
        $app->map('json', function (mixed $data, int $code = 200) use ($test): void {
            $test->jsons[] = ['data' => $data, 'code' => $code];
        });
        $app->map('halt', function (int $code, string $msg) use ($test): void {
            $test->halts[] = ['code' => $code, 'msg' => $msg];
        });
        $app->map('redirect', function (string $u) use ($test): void {
            $test->redirects[] = $u;
        });
        $app->map('render', function (string $t, array $d) use ($test): void {
            $test->redirects[] = 'render:' . $t;
        });
        \Flight::setEngine($app);

        return $app;
    }

    private function publicEngine(): Engine
    {
        $test = $this;
        $pdo = $this->pdo;
        $app = $this->app([
            'request' => fn(): object => $this->requestStub(),
            'db' => static fn(): PDO => $pdo,
            'profiles' => static fn(): Profile => new Profile($pdo),
            'media' => fn(): object => $this->mediaStub(),
            'auth' => fn(): object => $this->authStub(false),
            'session' => fn(): object => $this->sessionStub(),
            'pluginLoader' => static fn(): object => new class {
                public function routePrefix(string $id): string
                {
                    return '/profile';
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
        $app->map('json', function (mixed $data, int $code = 200) use ($test): void {
            $test->jsons[] = ['data' => $data, 'code' => $code];
        });
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
