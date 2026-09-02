<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pubvana\Plugins\Forms\Services\FormsService;

/**
 * Smoke test proving the Composer autoloader and the test harness wire up
 * against the real application classes. This is the placeholder unit test for
 * the PHPUnit scaffolding; replace/extend with per-service tests per
 * CODING-STANDARDS.md "Tests".
 */
final class AutoloadSmokeTest extends TestCase
{
    public function testCorePluginServicesAutoload(): void
    {
        self::assertTrue(class_exists(FormsService::class));

        $reflection = new \ReflectionClass(FormsService::class);
        self::assertTrue($reflection->hasMethod('normalizeReturnUrl'));
        self::assertTrue($reflection->hasMethod('renderPublicForm'));
    }
}
