<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * PublicCsrf - CSRF checks for public forms on cached pages.
 *
 * A cached page contains a placeholder in place of a token, and the visitor
 * who receives it has no session. At serve time the code creates the token,
 * stores it in a host-only cookie, and writes it into the page. The submitted
 * form value has to match that cookie.
 *
 * Routes that use this register under the 'csrf.exempt' adext type, because
 * the core middleware validates session tokens only. This follows the
 * Profiles avatar precedent. The exemption covers one route, and the
 * controller runs the check.
 *
 * Validation order: the session token first (an uncached page or a signed-in
 * visitor), then the cookie token. Both compare with hash_equals.
 *
 * @package Pubvana\Services
 */
final class PublicCsrf
{
    /** Cookie that stores the token minted for a cached page. */
    public const COOKIE = 'pv_csrf';

    /** Hidden field name, matching flight-csrf's default. */
    public const FIELD = '_csrf_token';

    /** @var Engine<object> Flight application instance */
    private Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Reject the request unless it carries a valid token.
     *
     * @param array<string, mixed>|null $data Submitted data, defaults to $_POST
     */
    public function verify(?array $data = null): void
    {
        $data ??= $_POST;
        $submitted = $data[self::FIELD] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!is_string($submitted) || $submitted === '' || !$this->matches($submitted)) {
            $this->app->halt(403, '{"error":"CSRF token validation failed."}');
        }
    }

    /**
     * Whether a submitted token is valid for this request.
     */
    public function matches(string $submitted): bool
    {
        $sessionToken = $_SESSION[self::FIELD]['token'] ?? null;
        if (is_string($sessionToken) && $sessionToken !== ''
            && hash_equals($sessionToken, $submitted)) {
            return true;
        }

        $cookieToken = $_COOKIE[self::COOKIE] ?? null;

        return is_string($cookieToken) && $cookieToken !== ''
            && hash_equals($cookieToken, $submitted);
    }

    /**
     * The token to substitute into a cached page.
     *
     * Reuses the visitor's cookie when the cookie contains a well formed
     * token, so several cached pages opened in one visit all agree. Otherwise
     * it creates a new one.
     */
    public function tokenForResponse(): string
    {
        $existing = self::normalizeToken($_COOKIE[self::COOKIE] ?? null);
        if ($existing !== null) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $_COOKIE[self::COOKIE] = $token;

        return $token;
    }

    /**
     * Narrow a candidate token to 64 lowercase hex characters.
     *
     * Every other value returns null. With a fixed alphabet, a cookie value is
     * safe to echo into the page and to send back as a cookie, because the
     * value cannot contain markup or header syntax.
     *
     * @psalm-taint-escape cookie
     * @psalm-taint-escape html
     * @psalm-taint-escape text
     * @psalm-taint-escape header
     */
    public static function normalizeToken(mixed $value): ?string
    {
        if (!is_string($value) || preg_match('/^[0-9a-f]{64}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    /**
     * Send the token cookie. Host-only, HttpOnly, SameSite=Lax, Secure on TLS.
     */
    public function sendCookie(string $token): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE, $token, [
            'expires'  => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => UrlService::isHttpsRequest($_SERVER),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
