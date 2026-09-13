<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\Plugin;
use Pubvana\Tests\Support\TestCase;

/**
 * M7: Updates admin routes carry the updates.manage permission gate.
 *
 * C1 already forces admin.access on every plugin admin route, but the
 * Updates routes shipped with a null middleware slot, leaving status()
 * (the one action with no in-controller guard) reachable by any admin
 * without the Updates grant. The plugin must attach
 * PermissionMiddleware('updates.manage') to every admin route itself.
 *
 * @package Pubvana\Tests\Unit\Plugins\Updates
 */
#[CoversClass(Plugin::class)]
final class UpdatesRouteGateTest extends TestCase
{
    private string $pluginSrc;
    private string $seedSrc;

    protected function setUp(): void
    {
        $this->pluginSrc = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Updates/Plugin.php'
        );
        $this->seedSrc = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Updates/Database/Seeds/Seed.php'
        );
    }

    public function testPluginBuildsTheUpdatesManageGate(): void
    {
        self::assertStringContainsString(
            "new PermissionMiddleware(\$app, 'updates.manage')",
            $this->pluginSrc
        );

        // The old null slot must not come back.
        self::assertStringNotContainsString('$authMiddleware = null', $this->pluginSrc);
    }

    public function testEveryAdminRouteCarriesTheGate(): void
    {
        // Each route row must end in [$authMiddleware]; count the matches so
        // a new route added without the gate fails here.
        self::assertSame(
            10,
            preg_match_all(
                '/\[UpdatesAdminController::class, \'[a-zA-Z]+\'\],\s*\[\$authMiddleware\]/',
                $this->pluginSrc
            )
        );
    }

    public function testPermissionAliasMatchesTheSeed(): void
    {
        self::assertStringContainsString("'alias' => 'updates.manage'", $this->seedSrc);
    }
}
