<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesPublicController;
use Pubvana\Tests\Support\TestCase;

/**
 * Public profile write rejection and render guard (AUDIT M5).
 *
 * update() flashes an error and returns the user to the edit form when the
 * model rejects the write; show() only hands the template a website value
 * that passed the scheme allowlist, so a legacy row holding javascript: or
 * similar never becomes a navigable href.
 *
 * @package Pubvana\Tests\Unit\Plugins\Profiles
 */
#[CoversClass(ProfilesPublicController::class)]
final class ProfilesPublicWebsiteGuardTest extends TestCase
{
    public function testUpdateFlashesErrorAndReturnsToEditFormOnRejection(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Profiles/Controllers/ProfilesPublicController.php'
        );

        self::assertStringContainsString(
            "flash('danger', 'Website must be a full http:// or https:// URL.')",
            $src
        );
        self::assertStringContainsString("updateProfile((int) \$user->id, \$post) === null", $src);
        self::assertStringNotContainsString("/' . \$username . '/update'", $src);
    }

    public function testShowGuardsWebsiteThroughTheSchemeAllowlist(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Profiles/Controllers/ProfilesPublicController.php'
        );

        self::assertStringContainsString('UrlService::isSafeExternalUrl($profile->website ?? null)', $src);
        self::assertStringContainsString("'safe_website' =>", $src);
    }

    public function testTemplateRendersOnlyTheGuardedWebsite(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/themes/default/Views/pubvana/profiles/profile.tpl'
        );

        self::assertStringContainsString('{% if safe_website %}', $src);
        self::assertStringContainsString('href="{{ safe_website }}"', $src);

        // The raw field must never land in an href again.
        self::assertStringNotContainsString('href="{{ profile.website }}"', $src);
    }
}
