<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use PHPUnit\Framework\TestCase;

/**
 * Source guards for AUDIT M19: the client must not send confirm_breaking
 * unconditionally. The first apply request goes out without it, the server
 * refuses with status confirm_breaking, and the resubmit carries it.
 */
final class BreakingConfirmGuardTest extends TestCase
{
    private string $source = '';

    protected function setUp(): void
    {
        $file = dirname(__DIR__, 4) . '/plugins/Updates/Views/admin/index.php';
        self::assertFileExists($file);
        $this->source = (string) file_get_contents($file);
    }

    public function testConfirmBreakingIsConditionalOnServerRefusal(): void
    {
        self::assertMatchesRegularExpression(
            "/if \(breakingConfirmed\) \{ body\.append\('confirm_breaking', '1'\); \}/",
            $this->source,
        );
        self::assertMatchesRegularExpression(
            "/data\.status === 'confirm_breaking'/",
            $this->source,
        );
        self::assertMatchesRegularExpression(
            "/breakingConfirmed = true;/",
            $this->source,
        );
    }

    public function testNoUnconditionalConfirmBreakingAppend(): void
    {
        self::assertDoesNotMatchRegularExpression(
            "/body\.append\('confirm_breaking', '1'\);\s*\n\s*if \(forceTrustRequested\)/",
            $this->source,
            'confirm_breaking must only ride the confirmed resubmit, never the first request',
        );
    }

    public function testServerGateStaysInPlace(): void
    {
        $controller = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Updates/Controllers/UpdatesAdminController.php'
        );

        self::assertStringContainsString(
            '!$confirm',
            $controller,
            'the server-side breaking-changes auto-refuse must stay'
        );
        self::assertStringContainsString("'confirm_breaking'", $controller);
    }
}
