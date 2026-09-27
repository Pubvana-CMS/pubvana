<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use DateTimeImmutable;
use Enlivenapp\FlightShield\Models\AuthGroup;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\GroupAdminService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * GroupAdminService (the superadmin group is not deletable) against an
 * in-memory database.
 *
 * The guard is proven against Shield's real Groups API, because the damage
 * it prevents happens inside Groups::delete(): members left with no other
 * group are reassigned to the hardcoded 'user' fallback. The refusal tests
 * therefore read auth_groups and auth_groups_users back to prove nothing
 * was touched, and the allowed test proves a normal group still goes.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(GroupAdminService::class)]
final class GroupAdminServiceTest extends TestCase
{
    private PDO $pdo;

    private GroupAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();

        $groups = new \Enlivenapp\FlightShield\Authorization\Groups($this->pdo);

        \Flight::setEngine($this->app([
            'db'   => fn(): PDO => $this->pdo,
            'auth' => static fn(): object => new class ($groups) {
                public function __construct(private \Enlivenapp\FlightShield\Authorization\Groups $groups)
                {
                }
                public function groups(): \Enlivenapp\FlightShield\Authorization\Groups
                {
                    return $this->groups;
                }
            },
        ]));

        $this->service = new GroupAdminService(\Flight::app());
    }

    public function testDeleteRefusesSuperadminGroup(): void
    {
        $this->seedGroup('superadmin', 'Super Admin');
        $result = $this->service->deleteGroup($this->existingGroup('superadmin'));

        self::assertFalse($result->isOK());
        self::assertSame('The superadmin group cannot be deleted.', $result->reason());
        self::assertNotNull($this->freshGroup('superadmin'), 'the group must survive');
    }

    public function testDeleteRefusesSuperadminRegardlessOfCase(): void
    {
        $this->seedGroup('SuperAdmin', 'Super Admin');
        $result = $this->service->deleteGroup($this->existingGroup('SuperAdmin'));

        self::assertFalse($result->isOK());
        self::assertNotNull($this->freshGroup('SuperAdmin'));
    }

    public function testDeleteRefusalLeavesMembershipsIntact(): void
    {
        $this->seedGroup('superadmin', 'Super Admin');
        $this->pdo->exec("INSERT INTO users (username, active) VALUES ('boss', 1)");
        $userId = (int) $this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO auth_groups_users (user_id, group_alias) VALUES ({$userId}, 'superadmin')");

        $this->service->deleteGroup($this->existingGroup('superadmin'));

        self::assertSame(1, (int) $this->pdo->query(
            "SELECT COUNT(*) FROM auth_groups_users WHERE user_id = {$userId}"
        )->fetchColumn(), 'a refused delete must not demote members to the user group');
    }

    public function testDeleteRemovesOrdinaryGroup(): void
    {
        $this->seedGroup('editors', 'Editors');
        $result = $this->service->deleteGroup($this->existingGroup('editors'));

        self::assertTrue($result->isOK());
        self::assertNull($this->freshGroup('editors'));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function seedGroup(string $alias, string $title): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $group = new AuthGroup($this->pdo);
        $group->alias = $alias;
        $group->title = $title;
        $group->created_at = $now;
        $group->updated_at = $now;
        $group->insert();
    }

    private function existingGroup(string $alias): AuthGroup
    {
        $group = $this->freshGroup($alias);

        self::assertNotNull($group, "group {$alias} should exist before the call under test");

        return $group;
    }

    private function freshGroup(string $alias): ?AuthGroup
    {
        $group = (new AuthGroup($this->pdo))->eq('alias', $alias)->find();

        return $group->isHydrated() ? $group : null;
    }
}
