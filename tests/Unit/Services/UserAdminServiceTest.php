<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use DateTimeImmutable;
use Enlivenapp\FlightShield\Models\AuthGroup;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use Enlivenapp\FlightShield\Services\UserManagement;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\UserAdminService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * UserAdminService (ban/unban/force reset, superadmin safeguards) against
 * an in-memory database.
 *
 * Persistence follows Shield's UserManagement::setActive() dirty() pattern;
 * these tests prove the writes actually land and read back through a fresh
 * model instance, mirroring Shield's login-time checks (isBanned()).
 *
 * The superadmin guard tests exercise the actor via a minimal auth double
 * that supplies the current user and a real UserManagement for the calls
 * that actually persist.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(UserAdminService::class)]
final class UserAdminServiceTest extends TestCase
{
    private PDO $pdo;

    private UserAdminService $service;

    private object $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();

        $this->auth = new class ($this->pdo) {
            public ?User $actor = null;

            public function __construct(private PDO $pdo)
            {
            }

            public function user(): ?User
            {
                return $this->actor;
            }

            public function id(): int|string|null
            {
                return $this->actor?->id;
            }

            public function users(): UserManagement
            {
                return new UserManagement($this->pdo);
            }
        };

        \Flight::setEngine($this->app([
            'db'   => fn(): PDO => $this->pdo,
            'auth' => fn(): object => $this->auth,
        ]));

        $this->service = new UserAdminService(\Flight::app());
    }

    public function testBanPersistsStatusAndMessage(): void
    {
        $user = $this->seedUser('barnaby');

        $this->service->ban($user, 'Spamming the contact form');

        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertTrue($fresh->isBanned());
        self::assertSame('banned', $fresh->status);
        self::assertSame('Spamming the contact form', $fresh->status_message);
        self::assertSame(1, (int) $fresh->active, 'banning must not deactivate the account');
    }

    public function testBanWithoutMessageStoresNullMessage(): void
    {
        $user = $this->seedUser('quietban');

        $this->service->ban($user, null);

        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertTrue($fresh->isBanned());
        self::assertNull($fresh->status_message);
    }

    public function testUnBanClearsStatus(): void
    {
        $user = $this->seedUser('pardoned');
        $this->service->ban($user, 'oops');

        $this->service->unBan($user);

        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertFalse($fresh->isBanned());
        self::assertNull($fresh->status);
        self::assertNull($fresh->status_message);
    }

    public function testForceResetTogglesIdentityFlag(): void
    {
        $user = $this->seedUser('resetme');

        $this->service->forceReset($user, true);
        self::assertTrue($this->identityForceReset((int) $user->id));

        // The flag lives on the email identity; requiresPasswordReset() reads it.
        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertTrue($fresh->requiresPasswordReset());

        $this->service->forceReset($user, false);
        self::assertFalse($this->identityForceReset((int) $user->id));
    }

    public function testForceResetWithNoEmailIdentityIsSilentNoOp(): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $user = new User($this->pdo);
        $user->username = 'no-identity';
        $user->active = true;
        $user->created_at = $now;
        $user->updated_at = $now;
        $user->insert();

        $this->service->forceReset($user, true);

        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) c FROM auth_identities')->fetch()['c']);
    }

    // -----------------------------------------------------------------
    // createUser
    // -----------------------------------------------------------------

    public function testCreateByNonSuperadminCannotAssignSuperadmin(): void
    {
        $this->seedGroup('superadmin');
        $this->auth->actor = $this->seedUser('novice', ['admin']);

        $result = $this->service->createUser('newbie', 'newbie@example.com', 'Strong-Pass-123', 'superadmin');

        self::assertFalse($result->isOK());
        self::assertStringContainsString('superadmin', (string) $result->reason());
        self::assertNull((new User($this->pdo))->findByCredentials(['username' => 'newbie']));
    }

    public function testCreateBySuperadminCanAssignSuperadmin(): void
    {
        $this->seedGroup('superadmin');
        $this->auth->actor = $this->seedUser('owner', ['superadmin']);

        $result = $this->service->createUser('newbie', 'newbie@example.com', 'Strong-Pass-123', 'superadmin');

        self::assertTrue($result->isOK());
        $created = $result->extraInfo();
        self::assertInstanceOf(User::class, $created);
        self::assertTrue($created->inGroup('superadmin'));
    }

    public function testCreateByNonSuperadminCanAssignRegularGroup(): void
    {
        $this->seedGroup('admin');
        $this->auth->actor = $this->seedUser('novice', ['admin']);

        $result = $this->service->createUser('recruit', 'recruit@example.com', 'Strong-Pass-123', 'admin');

        self::assertTrue($result->isOK());
    }

    // -----------------------------------------------------------------
    // syncGroups
    // -----------------------------------------------------------------

    public function testSyncGroupsByNonSuperadminCannotGrantSuperadmin(): void
    {
        $this->auth->actor = $this->seedUser('novice', ['admin']);
        $target = $this->seedUser('target', ['user']);

        $result = $this->service->syncGroups($target, ['superadmin']);

        self::assertFalse($result->isOK());
        self::assertSame(['user'], $target->getGroups());
    }

    public function testSyncGroupsBySuperadminCanGrantSuperadmin(): void
    {
        $this->auth->actor = $this->seedUser('owner', ['superadmin']);
        $target = $this->seedUser('target', ['user']);

        $result = $this->service->syncGroups($target, ['superadmin']);

        self::assertTrue($result->isOK());
        self::assertTrue($target->inGroup('superadmin'));
    }

    public function testSyncGroupsCannotRemoveLastSuperadmin(): void
    {
        $last = $this->seedUser('last-super', ['superadmin']);
        $this->auth->actor = $last;

        $result = $this->service->syncGroups($last, ['user']);

        self::assertFalse($result->isOK());
        self::assertTrue($last->inGroup('superadmin'));
    }

    public function testSyncGroupsCanDemoteWhenAnotherSuperadminRemains(): void
    {
        $this->seedUser('keeper', ['superadmin']);
        $steppingDown = $this->seedUser('stepping-down', ['superadmin']);
        $this->auth->actor = $this->seedUser('owner', ['superadmin']);

        $result = $this->service->syncGroups($steppingDown, ['user']);

        self::assertTrue($result->isOK());
        self::assertFalse($steppingDown->inGroup('superadmin'));
    }

    // -----------------------------------------------------------------
    // deleteUser
    // -----------------------------------------------------------------

    public function testDeleteOwnAccountDenied(): void
    {
        $self = $this->seedUser('self');
        $this->auth->actor = $self;

        $result = $this->service->deleteUser($self);

        self::assertFalse($result->isOK());
        self::assertNotNull($this->freshUser((int) $self->id));
    }

    public function testDeleteLastSuperadminDenied(): void
    {
        $last = $this->seedUser('last-super', ['superadmin']);
        $this->auth->actor = $this->seedUser('admin', ['admin']);

        $result = $this->service->deleteUser($last);

        self::assertFalse($result->isOK());
        self::assertNotNull($this->freshUser((int) $last->id));
    }

    public function testDeleteSuperadminAllowedWhenAnotherRemains(): void
    {
        $this->seedUser('keeper', ['superadmin']);
        $doomed = $this->seedUser('doomed', ['superadmin']);
        $this->auth->actor = $this->seedUser('owner', ['superadmin']);

        $result = $this->service->deleteUser($doomed);

        self::assertTrue($result->isOK());
        self::assertNull($this->freshUser((int) $doomed->id));
    }

    public function testDeleteRegularUserAllowed(): void
    {
        $this->auth->actor = $this->seedUser('admin', ['admin']);
        $target = $this->seedUser('victim');

        $result = $this->service->deleteUser($target);

        self::assertTrue($result->isOK());
        self::assertNull($this->freshUser((int) $target->id));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function seedUser(string $username, array $groups = []): User
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $user = new User($this->pdo);
        $user->username = $username;
        $user->active = true;
        $user->created_at = $now;
        $user->updated_at = $now;
        $user->insert();

        (new UserIdentity($this->pdo))->createEmailIdentity($user, [
            'email'         => $username . '@example.com',
            'password_hash' => password_hash('Seed-Only-123', PASSWORD_DEFAULT),
        ]);

        foreach ($groups as $group) {
            $user->addGroup($group);
        }

        return $user;
    }

    private function seedGroup(string $alias): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $group = new AuthGroup($this->pdo);
        $group->alias = $alias;
        $group->title = ucfirst($alias);
        $group->created_at = $now;
        $group->updated_at = $now;
        $group->insert();
    }

    private function freshUser(int $id): ?User
    {
        return (new User($this->pdo))->findById($id);
    }

    private function identityForceReset(int $userId): bool
    {
        $identity = new UserIdentity($this->pdo);
        $identity->eq('user_id', $userId)
                 ->eq('type', UserIdentity::TYPE_EMAIL_PASSWORD)
                 ->find();

        return $identity->isHydrated() && (bool) $identity->force_reset;
    }
}
