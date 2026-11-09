<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PublicCsrf;
use Pubvana\Tests\Support\TestCase;

/**
 * PublicCsrf token handling for cached pages.
 *
 * Covers the session-token path, the cookie-token path, rejection of a
 * missing or wrong token, and the narrowing that makes a cookie value safe
 * to reuse.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PublicCsrf::class)]
final class PublicCsrfTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['_csrf_token'], $_COOKIE[PublicCsrf::COOKIE]);
        parent::tearDown();
    }

    public function testSessionTokenMatches(): void
    {
        $token = str_repeat('b', 64);
        $_SESSION['_csrf_token'] = ['token' => $token, 'created' => time()];

        $csrf = new PublicCsrf($this->app());

        self::assertTrue($csrf->matches($token));
    }

    public function testCookieTokenMatches(): void
    {
        $token = str_repeat('c', 64);
        $_COOKIE[PublicCsrf::COOKIE] = $token;

        $csrf = new PublicCsrf($this->app());

        self::assertTrue($csrf->matches($token));
    }

    public function testWrongTokenIsRejected(): void
    {
        $_COOKIE[PublicCsrf::COOKIE] = str_repeat('d', 64);

        $csrf = new PublicCsrf($this->app());

        self::assertFalse($csrf->matches(str_repeat('e', 64)));
        self::assertFalse($csrf->matches(''));
    }

    public function testTokenForResponseReusesAWellFormedCookie(): void
    {
        $token = str_repeat('f', 64);
        $_COOKIE[PublicCsrf::COOKIE] = $token;

        $csrf = new PublicCsrf($this->app());

        self::assertSame($token, $csrf->tokenForResponse());
    }

    public function testTokenForResponseMintsWhenCookieIsMissingOrMalformed(): void
    {
        $csrf = new PublicCsrf($this->app());

        $minted = $csrf->tokenForResponse();
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $minted);

        $_COOKIE[PublicCsrf::COOKIE] = '<script>alert(1)</script>';

        $second = $csrf->tokenForResponse();
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $second);
        self::assertSame($second, $_COOKIE[PublicCsrf::COOKIE]);
    }

    public function testNormalizeTokenRejectsAnythingButHex(): void
    {
        self::assertNull(PublicCsrf::normalizeToken(null));
        self::assertNull(PublicCsrf::normalizeToken('short'));
        self::assertNull(PublicCsrf::normalizeToken(str_repeat('z', 64)));
        self::assertNull(PublicCsrf::normalizeToken(str_repeat('a', 63)));
        self::assertSame(str_repeat('a', 64), PublicCsrf::normalizeToken(str_repeat('a', 64)));
    }
}
