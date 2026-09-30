<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;

/**
 * The CSRF exemption rule the core gate reads.
 *
 * A registration carries a 'prefix' (path prefix) or a 'pattern' (PCRE, for
 * routes whose variable sits mid-path). The profile avatar upload is the
 * reason pattern exists: /profile/{id}/avatar cannot be isolated by a prefix
 * without also exempting /profile/{id}/update.
 */
#[CoversClass(ExtensionRegistry::class)]
final class CsrfExemptRuleTest extends TestCase
{
    public function testPrefixMatchesByPathStart(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('csrf.exempt', 'default', 'pubvana.mystore', [
            'prefix' => '/store/webhook/',
        ]);

        self::assertTrue($registry->isCsrfExempt('/store/webhook/paid'));
        self::assertFalse($registry->isCsrfExempt('/store/webhook'));
        self::assertFalse($registry->isCsrfExempt('/store/other'));
    }

    public function testPatternMatchesOneRouteAndLeavesItsSiblingsProtected(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('csrf.exempt', 'default', 'pubvana.profiles.avatar', [
            'pattern' => '#^/profile/\d+/avatar$#',
        ]);

        self::assertTrue($registry->isCsrfExempt('/profile/7/avatar'));
        self::assertFalse($registry->isCsrfExempt('/profile/7/update'), 'the update route stays protected');
        self::assertFalse($registry->isCsrfExempt('/profile/7'));
        self::assertFalse($registry->isCsrfExempt('/profile/7/avatar/extra'));
        self::assertFalse($registry->isCsrfExempt('/profile/abc/avatar'), 'the id is numeric');
    }

    public function testPatternWinsOverPrefixOnTheSameRegistration(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('csrf.exempt', 'default', 'pubvana.mixed', [
            'prefix' => '/profile/',
            'pattern' => '#^/profile/\d+/avatar$#',
        ]);

        self::assertTrue($registry->isCsrfExempt('/profile/7/avatar'));
        self::assertFalse(
            $registry->isCsrfExempt('/profile/7/update'),
            'the broad prefix must not apply when a pattern is given'
        );
    }

    public function testNoRegistrationsMeansNothingIsExempt(): void
    {
        $registry = new ExtensionRegistry();

        self::assertFalse($registry->isCsrfExempt('/profile/7/avatar'));
        self::assertFalse($registry->isCsrfExempt('/admin/users'));
    }

    public function testARegistrationWithNeitherKeyIsIgnored(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('csrf.exempt', 'default', 'pubvana.empty', [
            'label' => 'Nothing to match',
        ]);

        self::assertFalse(
            $registry->isCsrfExempt('/anything'),
            'a keyless registration must not exempt every path'
        );
    }

    public function testAPatternKeyMustBeAString(): void
    {
        $registry = new ExtensionRegistry();

        self::assertFalse($registry->register('csrf.exempt', 'default', 'pubvana.bad', [
            'pattern' => ['not', 'a', 'string'],
        ]));
        self::assertFalse($registry->isCsrfExempt('/profile/7/avatar'));
    }
}
