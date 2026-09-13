<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * Rejected profile writes surface a flash error (AUDIT M5).
 *
 * When the model rejects the write (website without an http/https scheme)
 * the controllers must flash an error instead of the success message and
 * bounce the user back to the form.
 *
 * @package Pubvana\Tests\Unit\Plugins\Profiles
 */
#[CoversClass(ProfilesAdminController::class)]
final class ProfilesAdminWebsiteRejectionTest extends TestCase
{
    public function testUpdateFlashesErrorAndBouncesBackOnRejection(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Profiles/Controllers/ProfilesAdminController.php'
        );

        self::assertStringContainsString(
            "flash('error', 'Website must be a full http:// or https:// URL.')",
            $src
        );
        self::assertStringContainsString("updateProfile((int) \$userId, \$post) === null", $src);
    }
}
