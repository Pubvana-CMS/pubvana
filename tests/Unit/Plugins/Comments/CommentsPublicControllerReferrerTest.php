<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Comments;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Comments\Controllers\CommentsPublicController;
use Pubvana\Tests\Support\TestCase;

/**
 * CommentsPublicController referrer redirect hardening.
 *
 * store() bounces back to the HTTP Referer. The referrer is normalized
 * through UrlService::sameSite() before any redirect, and the error
 * redirect builds on the same normalized value, so a hostile Referer can
 * never reach a Location header as an open redirect.
 *
 * @package Pubvana\Tests\Unit\Plugins\Comments
 */
#[CoversClass(CommentsPublicController::class)]
final class CommentsPublicControllerReferrerTest extends TestCase
{
    public function testStoreRoutesTheReferrerThroughUrlService(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 4) . '/plugins/Comments/Controllers/CommentsPublicController.php'
        );

        self::assertStringContainsString(
            '$referrer = $this->app->url()->sameSite($this->app->request()->referrer ?: \'/\')',
            $src
        );
    }
}