<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightSessions\Crypto\PayloadCipher;
use PDO;
use RuntimeException;
use Throwable;

/**
 * PageCacheService - Flat-file cache for public GET responses.
 *
 * Calling this class sends a stored page without starting a session, loading
 * a plugin, running a route, or rendering a template. Each stored page has
 * one file in writable/cache/pages/, two levels deep, named by a hash of
 * host and path.
 *
 * PDO is the only dependency, and only for the session check. services.php
 * includes page-cache.php before the plugin loader, so the sessions plugin
 * does not start when a page comes from the cache.
 *
 * A page comes from the cache only when all four hold:
 *   - the method is GET or HEAD
 *   - the request has no query string
 *   - the path is not excluded
 *   - user_id is null and the session stores nothing but the CSRF token.
 *     A pending flash message adds extra state to the session, so that
 *     request renders normally and the visitor sees the message.
 *
 * @package Pubvana\Services
 */
final class PageCacheService
{
    /** Placeholder that page-cache.php replaces with the CSRF token before storing a page. */
    public const TOKEN_PLACEHOLDER = '<!--pv-csrf-->';

    /** File that stores the version number of the current cache. */
    public const VERSION_FILE = 'version';

    /** Append-only log. The Analytics 1m cron task processes it and empties it. */
    public const HIT_LOG = 'page-hits.log';

    /** Hit log maximum size in bytes. logHit() checks it, processPageCacheHits() does not. */
    public const HIT_LOG_MAX_BYTES = 5_242_880;

    /** Session cookie name, matching the sessions package default. */
    private const DEFAULT_SESSION_COOKIE = 'flight_session';

    /** Response headers to store with each page. */
    private const STORED_HEADERS = [
        'content-type',
        'content-security-policy',
        'x-frame-options',
        'x-content-type-options',
        'referrer-policy',
        'permissions-policy',
        'cache-control',
        'vary',
    ];

    /** Path prefixes to leave out of the cache. */
    private const EXCLUDED_PREFIXES = [
        '/admin',
        '/api',
        '/auth',
        '/assets',
        '/uploads',
        '/search',
    ];

    private string $dir;

    private bool $enabled;

    private int $ttl;

    private int $edgeTtl;

    private ?string $cloudflareZoneId;

    private ?string $cloudflareToken;

    private string $sessionCookieName;

    private ?string $sessionEncryptionKey;

    private ?PDO $pdo;

    private string $host;

    /**
     * @param array{
     *     dir?: string,
     *     enabled?: bool,
     *     ttl?: int,
     *     edge_ttl?: int,
     *     cloudflare_zone_id?: string|null,
     *     cloudflare_token?: string|null,
     *     session_cookie?: string,
     *     session_key?: string|null,
     *     host?: string
     * } $config
     * @param PDO|null $pdo Connection for the session check only
     */
    public function __construct(array $config = [], ?PDO $pdo = null)
    {
        $root = defined('PROJECT_ROOT') ? (string) PROJECT_ROOT : dirname(__DIR__, 2);

        $this->dir = rtrim((string) ($config['dir'] ?? $root . '/writable/cache/pages'), '/');
        $this->enabled = (bool) ($config['enabled'] ?? false);
        $this->ttl = max(0, (int) ($config['ttl'] ?? 600));
        $this->edgeTtl = max(0, (int) ($config['edge_ttl'] ?? 0));

        $zone = trim((string) ($config['cloudflare_zone_id'] ?? ''));
        $token = trim((string) ($config['cloudflare_token'] ?? ''));
        $this->cloudflareZoneId = $zone === '' ? null : $zone;
        $this->cloudflareToken = $token === '' ? null : $token;

        $this->sessionCookieName = (string) ($config['session_cookie'] ?? self::DEFAULT_SESSION_COOKIE);

        $key = trim((string) ($config['session_key'] ?? ''));
        $this->sessionEncryptionKey = $key === '' ? null : $key;

        $this->pdo = $pdo;

        $host = (string) ($config['host'] ?? '');
        if ($host === '') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'default');
        }
        $this->host = strtolower($host);
    }

    /**
     * Build a service from the values bootstrap already resolved from .env.
     *
     * @param array<string, mixed> $env
     */
    public static function fromEnv(array $env, ?PDO $pdo = null): self
    {
        return new self([
            'enabled'               => (bool) ($env['enabled'] ?? false),
            'ttl'                   => (int) ($env['ttl'] ?? 600),
            'edge_ttl'              => (int) ($env['edge_ttl'] ?? 0),
            'cloudflare_zone_id'    => $env['cloudflare_zone_id'] ?? null,
            'cloudflare_token'      => $env['cloudflare_token'] ?? null,
            'session_cookie'        => $env['session_cookie'] ?? null,
            'session_key'           => $env['session_key'] ?? null,
        ], $pdo);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }

    public function edgeTtl(): int
    {
        return $this->edgeTtl;
    }

    public function directory(): string
    {
        return $this->dir;
    }

    // -----------------------------------------------------------------
    // Eligibility
    // -----------------------------------------------------------------

    /**
     * Whether this request may come from the cache or be stored.
     *
     * Checks the method, the query string and the path prefixes, and never
     * reads session data, so the caller can test the cheap conditions first.
     */
    public function isEligibleRequest(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (php_sapi_name() === 'cli') {
            return false;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method !== 'GET' && $method !== 'HEAD') {
            return false;
        }

        // A request with a query string never comes from the cache. Pagination
        // uses paths (/blog/page/3) and search uses ?q=, so every content URL
        // stays cacheable.
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if ($query !== '') {
            return false;
        }

        return $this->isEligiblePath($this->requestPath());
    }

    /**
     * Whether a path is outside every excluded prefix.
     */
    public function isEligiblePath(string $path): bool
    {
        if ($path === '' || $path[0] !== '/') {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        return true;
    }

    /**
     * The request path with no query string and no trailing slash.
     */
    public function requestPath(): string
    {
        $parsed = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = is_string($parsed) && $parsed !== '' ? $parsed : '/';

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
            if ($path === '') {
                $path = '/';
            }
        }

        return $path;
    }

    // -----------------------------------------------------------------
    // Session cleanliness
    // -----------------------------------------------------------------

    /**
     * Whether the visitor's session may be ignored for caching purposes.
     *
     * Clean means: no session row at all, or a row whose user_id is null and
     * whose payload contains nothing but the CSRF token. Anything else (a
     * logged-in user, a pending flash, extra session state) makes the session
     * dirty, so that request renders normally.
     *
     * Any doubt makes the session dirty. A failed decrypt or an unreadable row
     * gives no cached page, and the cache never serves another visitor's page.
     */
    public function sessionIsClean(): bool
    {
        $sessionId = (string) ($_COOKIE[$this->sessionCookieName] ?? '');
        if ($sessionId === '') {
            return true;
        }

        if ($this->pdo === null) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT user_id, payload FROM sessions WHERE session_id = :sid LIMIT 1'
            );
            $stmt->execute([':sid' => $sessionId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return false;
        }

        if ($row === false) {
            // Cookie with no row: an expired or cleared session.
            return true;
        }

        if (($row['user_id'] ?? null) !== null) {
            return false;
        }

        $payload = (string) ($row['payload'] ?? '');
        if ($payload === '') {
            return true;
        }

        try {
            $plain = PayloadCipher::decrypt($payload, $this->sessionEncryptionKey);
        } catch (Throwable) {
            return false;
        }

        return $this->payloadIsCsrfOnly($plain);
    }

    /**
     * Whether a decrypted session payload holds only the CSRF token.
     *
     * session.serialize_handler is "php", so a payload is a sequence of
     * name|serialized-value; pairs. The pattern below matches the exact shape
     * of a token on its own. A flash message, a shield key, or any other entry
     * will not match, and that request gets no cached page.
     */
    private function payloadIsCsrfOnly(string $payload): bool
    {
        return preg_match(
            '/^_csrf_token\|a:2:\{s:5:"token";s:64:"[0-9a-f]{64}";s:7:"created";i:\d+;\}$/',
            $payload
        ) === 1;
    }

    // -----------------------------------------------------------------
    // Read and write
    // -----------------------------------------------------------------

    /**
     * Stable cache key for a host and path.
     */
    public function keyFor(?string $path = null, ?string $host = null): string
    {
        $path = $path ?? $this->requestPath();
        $host = strtolower($host ?? $this->host);

        return hash('sha256', $host . '|' . $path);
    }

    /**
     * Absolute file path for a cache key, split into two levels of subdirectories.
     */
    public function pathFor(string $key): string
    {
        return $this->dir . '/' . substr($key, 0, 2) . '/' . substr($key, 2, 2) . '/' . $key . '.cache';
    }

    /**
     * Read a stored entry.
     *
     * @return array{status: int, headers: array<string, string>, body: string, created: int}|null
     *         Null on a miss, an old version number, or an unreadable file.
     */
    public function read(string $key): ?array
    {
        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return null;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        $line = fgets($handle);
        $body = stream_get_contents($handle);
        fclose($handle);

        if ($line === false || $body === false) {
            return null;
        }

        $meta = json_decode(rtrim($line, "\n"), true);
        if (!is_array($meta)) {
            return null;
        }

        if (($meta['version'] ?? '') !== $this->version()) {
            return null;
        }

        $created = (int) ($meta['created'] ?? 0);
        if ($this->ttl > 0 && (time() - $created) > $this->ttl) {
            return null;
        }

        $headers = [];
        foreach ((array) ($meta['headers'] ?? []) as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $headers[$name] = $value;
            }
        }

        return [
            'status'  => (int) ($meta['status'] ?? 200),
            'headers' => $headers,
            'body'    => $body,
            'created' => $created,
        ];
    }

    /**
     * Store a rendered page.
     *
     * @param array<string, string> $headers Headers from the current response
     */
    public function store(string $key, int $status, array $headers, string $body): bool
    {
        if (!$this->enabled || $status !== 200 || $body === '') {
            return false;
        }

        $this->assertWritable();

        $kept = [];
        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), self::STORED_HEADERS, true)) {
                $kept[$name] = $value;
            }
        }

        $meta = [
            'version' => $this->version(),
            'status'  => $status,
            'created' => time(),
            'headers' => $kept,
        ];

        $encoded = json_encode($meta);
        if (!is_string($encoded)) {
            return false;
        }

        $path = $this->pathFor($key);
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->fail('cannot create cache directory ' . $dir);
        }

        $tmp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($tmp, $encoded . "\n" . $body) === false) {
            @unlink($tmp);
            return false;
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            return false;
        }

        return true;
    }

    /**
     * Apply cache headers to the current response and store the body on a miss.
     *
     * Flight calls this from a response-body callback before it sends headers,
     * so the browser and any CDN receive these headers. The method returns the
     * body for the next callback in the chain.
     *
     * The code writes only 200 responses to disk. Flight sends the cache
     * headers on every response, because they set the edge lifetime and the
     * permission for a CDN to store the URL.
     */
    public function captureResponse(\flight\net\Response $response, string $body): string
    {
        if (!$this->enabled) {
            return $body;
        }

        $statusValue = $response->status();
        $status = is_int($statusValue) ? $statusValue : 200;

        // A 404 or a 500 must not carry cache headers, or a CDN stores the
        // error page. Leaving the response untouched keeps the behaviour the
        // site had before the cache existed.
        if ($status !== 200 || $body === '') {
            return $body;
        }

        $cacheControl = 'public, max-age=' . $this->ttl;
        if ($this->edgeTtl > 0) {
            $cacheControl .= ', s-maxage=' . $this->edgeTtl;
        }

        $response->header('Cache-Control', $cacheControl);
        $response->header('X-Pubvana-Cache', 'miss');

        $vary = (string) ($response->getHeader('Vary') ?? '');
        if (!str_contains(strtolower($vary), 'accept-encoding')) {
            $response->header('Vary', $vary === '' ? 'Accept-Encoding' : $vary . ', Accept-Encoding');
        }

        // A CDN will not store a response that sets a session cookie. The
        // code removes the cookie only when edge caching is on. With the
        // cookie gone, each miss creates one unused session row until the
        // sessions package starts them lazily.
        if ($this->edgeTtl > 0 && !headers_sent()) {
            header_remove('Set-Cookie');
        }

        $token = $_SESSION['_csrf_token']['token'] ?? null;
        $storable = $body;
        if (is_string($token) && strlen($token) === 64) {
            $storable = str_replace($token, self::TOKEN_PLACEHOLDER, $body);
        }

        $headers = [];
        foreach ($response->headers() as $name => $value) {
            if (is_string($value) || is_int($value)) {
                $headers[$name] = (string) $value;
            }
        }

        $this->store($this->keyFor(), $status, $headers, $storable);

        return $body;
    }

    // -----------------------------------------------------------------
    // Invalidation
    // -----------------------------------------------------------------
    /**
     * Returns the current version number, and creates the file when it does
     * not exist.
     */
    public function version(): string
    {
        $path = $this->dir . '/' . self::VERSION_FILE;

        $value = @file_get_contents($path);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $this->writeVersion();
    }

    /**
     * Expire the whole cache by writing a new version number.
     *
     * The code writes the current version number into every stored page, so
     * one small write expires all of them. The next time a visitor asks for
     * that page there is no match, and the page renders again.
     */
    public function invalidate(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->writeVersion();
        $this->purgeEdge();
    }

    /**
     * Second expiry trigger: any successful write outside auth and search.
     *
     * The content services call invalidate() directly. This catches a write
     * nobody has connected yet, so an expired page cannot be served for a full
     * TTL. Login and search are left out, so a bot hammering the sign-in form
     * cannot expire the cache repeatedly.
     */
    public function shouldInvalidateAfterRequest(int $status): bool
    {
        if (!$this->enabled || $status >= 400) {
            return false;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $path = $this->requestPath();

        foreach (['/auth', '/search'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        return true;
    }

    private function writeVersion(): string
    {
        $this->assertWritable();

        $value = bin2hex(random_bytes(8));
        $path = $this->dir . '/' . self::VERSION_FILE;

        if (@file_put_contents($path, $value) === false) {
            $this->fail('cannot write cache version file ' . $path);
        }

        return $value;
    }

    /**
     * Send the purge request for the whole Cloudflare zone.
     *
     * One call deletes every cache entry in the zone, which matches what the
     * code does on disk. The code ignores a failure: the edge copy expires at
     * its s-maxage and a purge error cannot break a publish.
     */
    private function purgeEdge(): void
    {
        if ($this->cloudflareZoneId === null || $this->cloudflareToken === null) {
            return;
        }

        $url = 'https://api.cloudflare.com/client/v4/zones/'
            . rawurlencode($this->cloudflareZoneId) . '/purge_cache';

        $payload = json_encode(['purge_everything' => true]);
        if ($payload === false) {
            return;
        }

        $headers = [
            'Authorization: Bearer ' . $this->cloudflareToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        try {
            if (function_exists('curl_init')) {
                $handle = curl_init($url);
                if ($handle === false) {
                    return;
                }
                curl_setopt_array($handle, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 3,
                ]);
                curl_exec($handle);
                curl_close($handle);
                return;
            }

            $context = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => implode("\r\n", $headers),
                    'content'       => $payload,
                    'timeout'       => 3,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents($url, false, $context);
        } catch (Throwable) {
            // A purge failure only delays expiry. It cannot break a publish.
        }
    }

    // -----------------------------------------------------------------
    // Analytics
    // -----------------------------------------------------------------

    /**
     * Absolute path of the hit log.
     *
     * The Analytics cron task reads this path, so it must come from here and
     * not be rebuilt elsewhere.
     */
    public function hitLogPath(): string
    {
        return $this->dir . '/' . self::HIT_LOG;
    }

    /**
     * Record a hit for the Analytics cron task to process.
     */
    public function logHit(?string $path = null, ?string $referrer = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $path = $path ?? $this->requestPath();
        $referrer = $referrer ?? (string) ($_SERVER['HTTP_REFERER'] ?? '');

        $line = json_encode([
            'path'     => $path,
            'referrer' => $referrer,
            'agent'    => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'time'     => time(),
        ]);
        if ($line === false) {
            return;
        }

        $file = $this->hitLogPath();

        if (is_file($file) && (int) @filesize($file) >= self::HIT_LOG_MAX_BYTES) {
            @rename($file, $file . '.1');
        }

        @file_put_contents($file, $line . "\n", FILE_APPEND);
    }

    // -----------------------------------------------------------------
    // Housekeeping
    // -----------------------------------------------------------------

    /**
     * Delete page files older than twice the TTL.
     *
     * @return int Number of files removed
     */
    public function prune(): int
    {
        if (!is_dir($this->dir)) {
            return 0;
        }

        $cutoff = time() - max(60, $this->ttl * 2);
        $removed = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'cache') {
                continue;
            }

            $modified = $file->getMTime();
            if ($modified < $cutoff && @unlink($file->getPathname())) {
                $removed++;
            }
        }

        return $removed;
    }

    // -----------------------------------------------------------------
    // Guards
    // -----------------------------------------------------------------

    /**
     * Throw when the cache directory cannot take a write.
     *
     * A silent slowdown on a busy site takes hours to trace. The code throws
     * here, and the global error handler renders the 500.
     */
    private function assertWritable(): void
    {
        if (!is_dir($this->dir)) {
            if (!@mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
                $this->fail('cannot create cache directory ' . $this->dir);
            }
        }

        if (!is_writable($this->dir)) {
            $this->fail('cache directory is not writable: ' . $this->dir);
        }
    }

    private function fail(string $message): never
    {
        error_log('page-cache: ' . $message);

        throw new RuntimeException('Page cache unavailable. ' . $message);
    }
}
