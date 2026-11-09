<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\net\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PageCacheService;
use Pubvana\Tests\Support\TestCase;

/**
 * PageCacheService behaviour over a temp cache directory.
 *
 * Covers path eligibility, request-key stability, the store and read round
 * trip, generation invalidation, TTL expiry, the token placeholder, and the
 * cache headers a miss puts on the response.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PageCacheService::class)]
final class PageCacheServiceTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheDir = sys_get_temp_dir() . '/pv-pagecache-' . uniqid('', true);
        mkdir($this->cacheDir, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->cacheDir);
        parent::tearDown();
    }

    private function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function service(array $config = []): PageCacheService
    {
        return new PageCacheService($config + [
            'dir'     => $this->cacheDir,
            'enabled' => true,
            'ttl'     => 600,
            'host'    => 'example.test',
        ]);
    }

    public function testExcludesAdminAndAuthPaths(): void
    {
        $service = $this->service();

        self::assertFalse($service->isEligiblePath('/admin'));
        self::assertFalse($service->isEligiblePath('/admin/blog'));
        self::assertFalse($service->isEligiblePath('/auth/login'));
        self::assertFalse($service->isEligiblePath('/search'));
        self::assertFalse($service->isEligiblePath('/assets/theme/default/css/x.css'));
        self::assertFalse($service->isEligiblePath('/uploads/2026/a.jpg'));
        self::assertFalse($service->isEligiblePath('/api/ai/help'));
    }

    public function testAllowsContentPaths(): void
    {
        $service = $this->service();

        self::assertTrue($service->isEligiblePath('/'));
        self::assertTrue($service->isEligiblePath('/blog'));
        self::assertTrue($service->isEligiblePath('/blog/page/3'));
        self::assertTrue($service->isEligiblePath('/pages/about'));
        self::assertTrue($service->isEligiblePath('/profile/12'));
        self::assertTrue($service->isEligiblePath('/searcher'));
        self::assertTrue($service->isEligiblePath('/administrator'));
    }

    public function testDisabledServiceIsNeverEligibleOrStored(): void
    {
        $service = $this->service(['enabled' => false]);

        self::assertFalse($service->store('abc', 200, [], 'body'));
        self::assertNull($service->read('abc'));
    }

    public function testRoundTripPreservesStatusHeadersAndBody(): void
    {
        $service = $this->service();
        $key = $service->keyFor('/blog', 'example.test');

        self::assertTrue($service->store($key, 200, [
            'Content-Type'     => 'text/html; charset=utf-8',
            'Content-Security-Policy' => "default-src 'self'",
            'Set-Cookie'       => 'leak=1',
            'X-Not-Whitelisted' => 'nope',
        ], '<html>cached</html>'));

        $entry = $service->read($key);

        self::assertNotNull($entry);
        self::assertSame(200, $entry['status']);
        self::assertSame('<html>cached</html>', $entry['body']);
        self::assertSame('text/html; charset=utf-8', $entry['headers']['Content-Type']);
        self::assertArrayHasKey('Content-Security-Policy', $entry['headers']);
        self::assertArrayNotHasKey('Set-Cookie', $entry['headers']);
        self::assertArrayNotHasKey('X-Not-Whitelisted', $entry['headers']);
    }

    public function testOnlyOkResponsesAreStored(): void
    {
        $service = $this->service();

        self::assertFalse($service->store('k', 404, [], 'not found'));
        self::assertFalse($service->store('k', 200, [], ''));
    }

    public function testInvalidateMakesStoredEntriesStale(): void
    {
        $service = $this->service();
        $key = $service->keyFor('/blog', 'example.test');

        self::assertTrue($service->store($key, 200, [], 'before'));
        self::assertNotNull($service->read($key));

        $service->invalidate();

        self::assertNull($service->read($key));
    }

    public function testExpiredEntryIsAMiss(): void
    {
        $service = $this->service(['ttl' => 1]);
        $key = $service->keyFor('/blog', 'example.test');

        self::assertTrue($service->store($key, 200, [], 'body'));

        $path = $service->pathFor($key);
        $contents = (string) file_get_contents($path);
        [$metaLine, $body] = explode("\n", $contents, 2);

        /** @var array<string, mixed> $meta */
        $meta = json_decode($metaLine, true);
        $meta['created'] = time() - 10;
        file_put_contents($path, json_encode($meta) . "\n" . $body);

        self::assertNull($service->read($key));
    }

    public function testKeyIsStablePerHostAndPath(): void
    {
        $service = $this->service();

        self::assertSame(
            $service->keyFor('/blog', 'example.test'),
            $service->keyFor('/blog', 'example.test')
        );

        self::assertNotSame(
            $service->keyFor('/blog', 'example.test'),
            $service->keyFor('/blog', 'other.test')
        );

        self::assertNotSame(
            $service->keyFor('/blog', 'example.test'),
            $service->keyFor('/pages', 'example.test')
        );
    }

    public function testCaptureResponseAddsHeadersAndStoresWithPlaceholder(): void
    {
        $service = $this->service(['edge_ttl' => 300]);

        $response = new Response();
        $response->status(200);
        $response->header('Content-Type', 'text/html; charset=utf-8');

        $token = str_repeat('a', 64);
        $_SESSION['_csrf_token'] = ['token' => $token, 'created' => time()];

        $body = '<form><input name="_csrf_token" value="' . $token . '"></form>';

        $returned = $service->captureResponse($response, $body);

        self::assertSame($body, $returned);
        self::assertStringContainsString('public, max-age=600', (string) $response->getHeader('Cache-Control'));
        self::assertStringContainsString('s-maxage=300', (string) $response->getHeader('Cache-Control'));
        self::assertSame('miss', $response->getHeader('X-Pubvana-Cache'));
        self::assertSame('Accept-Encoding', $response->getHeader('Vary'));

        $entry = $service->read($service->keyFor());
        self::assertNotNull($entry);
        self::assertStringContainsString(PageCacheService::TOKEN_PLACEHOLDER, $entry['body']);
        self::assertStringNotContainsString($token, $entry['body']);

        unset($_SESSION['_csrf_token']);
    }

    public function testCaptureResponseSkipsSmaxageWhenEdgeTtlIsZero(): void
    {
        $service = $this->service();
        $response = new Response();
        $response->status(200);

        $service->captureResponse($response, '<html></html>');

        self::assertStringNotContainsString('s-maxage', (string) $response->getHeader('Cache-Control'));
    }

    public function testNonOkResponseIsNotStored(): void
    {
        $service = $this->service();
        $response = new Response();
        $response->status(404);

        $service->captureResponse($response, 'missing');

        self::assertNull($service->read($service->keyFor()));

        // A CDN must not store an error page, so the response keeps the
        // headers it arrived with.
        self::assertNull($response->getHeader('Cache-Control'));
        self::assertNull($response->getHeader('X-Pubvana-Cache'));
    }

    public function testPruneRemovesOldFiles(): void
    {
        $service = $this->service(['ttl' => 1]);
        $key = $service->keyFor('/blog', 'example.test');

        self::assertTrue($service->store($key, 200, [], 'body'));

        $path = $service->pathFor($key);
        touch($path, time() - 600);

        self::assertGreaterThanOrEqual(1, $service->prune());
        self::assertFileDoesNotExist($path);
    }

    public function testInvalidBacklogBumpsBecauseWritesOutsideAuthFlush(): void
    {
        $service = $this->service();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/admin/blog/save';
        self::assertTrue($service->shouldInvalidateAfterRequest(200));

        $_SERVER['REQUEST_URI'] = '/auth/login';
        self::assertFalse($service->shouldInvalidateAfterRequest(200));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/blog';
        self::assertFalse($service->shouldInvalidateAfterRequest(200));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/blog';
        self::assertFalse($service->shouldInvalidateAfterRequest(500));

        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    }
}
