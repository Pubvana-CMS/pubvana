<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Services\ProfileTabService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * ProfileTabService - core tabs and plugin tabs.
 */
#[CoversClass(ProfileTabService::class)]
final class ProfileTabServiceTest extends TestCase
{
    private PDO $pdo;
    private ExtensionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        ProfilesSchema::create($this->pdo);
        $this->registry = new ExtensionRegistry();
    }

    public function testOwnerGetsProfileAndSecurityTabs(): void
    {
        $user = $this->user(7);
        $tabs = $this->service()->tabs($user, $user, '');

        self::assertSame(['profile', 'security'], array_column($tabs, 'id'));
        self::assertTrue($tabs[0]['active']);
        self::assertSame('', $tabs[0]['output']);
    }

    public function testOtherUserGetsNoSecurityTab(): void
    {
        $owner = $this->user(7);
        $viewer = $this->user(1);

        $tabs = $this->service()->tabs($owner, $viewer, '');

        self::assertSame(['profile'], array_column($tabs, 'id'));
    }

    public function testPluginTabIsAdded(): void
    {
        $this->registry->register('profile.tab', 'default', 'pubvana/addresses', [
            'label'    => 'Addresses',
            'callable' => static fn(object $user): string => '<input name="street">',
        ]);

        $user = $this->user(7);
        $tabs = $this->service()->tabs($user, $user, 'addresses');

        self::assertSame(['profile', 'security', 'addresses'], array_column($tabs, 'id'));
        self::assertSame('Addresses', $tabs[2]['label']);
        self::assertSame('<input name="street">', $tabs[2]['output']);
        self::assertTrue($tabs[2]['active']);
    }

    public function testOnlyOpenTabCallableRuns(): void
    {
        $calls = [];

        $this->registry->register('profile.tab', 'default', 'pubvana/addresses', [
            'label'    => 'Addresses',
            'callable' => function () use (&$calls): string {
                $calls[] = 'addresses';

                return 'addresses body';
            },
        ]);

        $this->registry->register('profile.tab', 'default', 'pubvana/phone', [
            'label'    => 'Phone',
            'callable' => function () use (&$calls): string {
                $calls[] = 'phone';

                return 'phone body';
            },
        ]);

        $user = $this->user(7);
        $tabs = $this->service()->tabs($user, $user, 'phone');

        self::assertSame(['phone'], $calls);
        self::assertSame('phone body', $tabs[3]['output']);
        self::assertSame('', $tabs[2]['output']);
    }

    public function testCallableReceivesTheProfileOwner(): void
    {
        $seen = null;

        $this->registry->register('profile.tab', 'default', 'pubvana/addresses', [
            'label'    => 'Addresses',
            'callable' => function (object $user) use (&$seen): string {
                $seen = $user;

                return '';
            },
        ]);

        $owner = $this->user(7);
        $viewer = $this->user(1);

        $this->service()->tabs($owner, $viewer, 'addresses');

        self::assertSame($owner, $seen);
    }

    public function testContributionWithoutCallableRendersEmpty(): void
    {
        $this->registry->register('profile.tab', 'default', 'pubvana/blank', [
            'label'    => 'Blank',
            'callable' => 'not-a-callable',
        ]);

        $user = $this->user(7);
        $tabs = $this->service()->tabs($user, $user, 'blank');

        self::assertSame('', $tabs[2]['output']);
    }

    public function testIdComesFromKey(): void
    {
        $this->registry->register('profile.tab', 'default', 'pubvana/store', [
            'label'    => 'Store',
            'callable' => static fn(): string => 'body',
        ]);

        $user = $this->user(7);
        $tabs = $this->service()->tabs($user, $user, '');

        self::assertSame(['profile', 'security', 'store'], array_column($tabs, 'id'));
    }

    public function testUnknownRequestedTabFallsBackToFirst(): void
    {
        $user = $this->user(7);
        $tabs = $this->service()->tabs($user, $user, 'missing');

        self::assertTrue($tabs[0]['active']);
    }

    /**
     * @param list<string> $permissions
     */
    private function user(int $id, array $permissions = []): object
    {
        return new class ($id, $permissions) {
            /** @param list<string> $permissions */
            public function __construct(private int $id, private array $permissions)
            {
            }

            public function __get(string $name): mixed
            {
                return $name === 'id' ? $this->id : null;
            }

            public function can(string $permission): bool
            {
                return in_array($permission, $this->permissions, true);
            }
        };
    }

    private function service(): ProfileTabService
    {
        $app = $this->app([
            'adext' => fn(): ExtensionRegistry => $this->registry,
        ]);

        return new ProfileTabService($app);
    }
}
