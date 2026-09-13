<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController;
use Pubvana\Tests\Support\TestCase;

/**
 * ProfilesAdminController return_url redirect hardening.
 *
 * update() redirects to a visitor-posted return_url. The value must go
 * through UrlService::sameSite() so a hostile return_url can never reach a
 * Location header, and the pre-existing admin-base fallback stays intact
 * when no value is posted.
 *
 * @package Pubvana\Tests\Unit\Plugins\Profiles
 */
#[CoversClass(ProfilesAdminController::class)]
final class ProfilesAdminReturnUrlTest extends TestCase
{
    public function testUpdateRoutesThePostedReturnUrlThroughUrlService(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Profiles/Controllers/ProfilesAdminController.php'
        );

        self::assertStringContainsString('$this->app->url()->sameSite($postedReturn, $this->adminBase())', $src);

        // The raw posted value must never be handed to redirect() directly.
        self::assertStringNotContainsString('$this->app->redirect($returnUrl)', $src);
    }
}