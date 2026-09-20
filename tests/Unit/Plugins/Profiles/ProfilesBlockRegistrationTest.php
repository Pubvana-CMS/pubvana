<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Services\ProfileBlockService;
use Pubvana\Tests\Support\TestCase;

/**
 * Author Card block registration and wiring.
 *
 * Verifies the block is registered in Plugin.php with the expected options
 * and template, that the regions.php block modal supports the toggle field
 * type, and that the Vision template gates its output on the author payload.
 */
#[CoversClass(ProfileBlockService::class)]
final class ProfilesBlockRegistrationTest extends TestCase
{
    private static function projectRoot(): string
    {
        return dirname(__DIR__, 4);
    }

    // -----------------------------------------------------------------
    // Block registration
    // -----------------------------------------------------------------

    public function testPluginRegistersAuthorCardBlock(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Plugin.php');

        self::assertStringContainsString("'block', 'available', 'pubvana.profiles.author-card'", $src);
        self::assertStringContainsString("'Author Card'", $src);
        self::assertStringContainsString("'template'    => 'author-card.tpl'", $src);
    }

    public function testBlockRegistrationHasAllToggles(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Plugin.php');

        self::assertStringContainsString("'show_on_blog'", $src);
        self::assertStringContainsString("'show_on_pages'", $src);
        self::assertStringContainsString("'show_avatar'", $src);
        self::assertStringContainsString("'show_socials'", $src);
        self::assertStringContainsString("'type' => 'toggle'", $src);
    }

    public function testBlockRegistrationHasTitleOption(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Plugin.php');

        self::assertStringContainsString("'title'", $src);
        self::assertStringContainsString("'About the Author'", $src);
    }

    public function testPluginMapsProfileBlockService(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Plugin.php');

        self::assertStringContainsString("'profileBlock'", $src);
        self::assertStringContainsString('ProfileBlockService', $src);
    }

    // -----------------------------------------------------------------
    // Template wiring
    // -----------------------------------------------------------------

    public function testTemplateGatesOnAuthor(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Views/public/blocks/author-card.tpl');

        self::assertStringContainsString('{% if author %}', $src);
    }

    public function testTemplateRespectsShowAvatarToggle(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Views/public/blocks/author-card.tpl');

        self::assertStringContainsString('show_avatar', $src);
        self::assertStringContainsString('author.avatar_url', $src);
    }

    public function testTemplateRespectsShowSocialsToggle(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Views/public/blocks/author-card.tpl');

        self::assertStringContainsString('show_socials', $src);
        self::assertStringContainsString('author.safe_website', $src);
    }

    public function testTemplateLinksSocialHandlesWithNofollow(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Views/public/blocks/author-card.tpl');

        self::assertStringContainsString('rel="nofollow noopener"', $src);
        self::assertStringContainsString('author.twitter_url', $src);
        self::assertStringContainsString('author.facebook_url', $src);
        self::assertStringContainsString('author.linkedin_url', $src);
    }

    public function testTemplateSocialLinksHaveCssHooks(): void
    {
        $src = file_get_contents(self::projectRoot() . '/plugins/Profiles/Views/public/blocks/author-card.tpl');

        self::assertStringContainsString('class="pv-profile-link-item"', $src);
        self::assertStringContainsString('class="pv-profile-link-website"', $src);
        self::assertStringContainsString('class="pv-profile-link-twitter"', $src);
        self::assertStringContainsString('class="pv-profile-link-facebook"', $src);
        self::assertStringContainsString('class="pv-profile-link-linkedin"', $src);
    }

    // -----------------------------------------------------------------
    // Regions.php toggle support
    // -----------------------------------------------------------------

    public function testBlockOptionsModalSupportsToggleType(): void
    {
        $src = file_get_contents(self::projectRoot() . '/app/Views/admin/themes/regions.php');

        self::assertStringContainsString("elseif (\$fieldType === 'toggle')", $src);
        self::assertStringContainsString('form-switch', $src);
        self::assertStringContainsString('form-check-input', $src);
    }
}