<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\AssetService;
use Pubvana\Tests\Support\TestCase;

/**
 * AssetService::resolve() and getMimeType() are near-pure and only touch
 * the filesystem, so they are fully unit-testable. serve() sends headers
 * and exits, which is left to feature tests.
 */
#[CoversClass(AssetService::class)]
final class AssetServiceTest extends TestCase
{
    private AssetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssetService($this->app());
    }

    public function testResolveReturnsRealPathForPluginAsset(): void
    {
        $result = $this->service->resolve('plugin', 'Blog', 'css/blog.css');

        self::assertNotNull($result);
        self::assertFileExists($result);
    }

    public function testResolveReturnsRealPathForThemeAsset(): void
    {
        $result = $this->service->resolve('theme', 'default', 'css/pubvana.css');

        self::assertNotNull($result);
        self::assertFileExists($result);
    }

    public function testResolveReturnsRealPathForVendorAsset(): void
    {
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3);
        $pkgDir = $root . '/vendor/pubvana-test-fix/fixture';
        $assetDir = $pkgDir . '/assets';
        @mkdir($assetDir, 0777, true);
        file_put_contents($assetDir . '/fix.css', 'body{}');

        try {
            $result = $this->service->resolve('vendor', 'pubvana-test-fix/fixture', 'fix.css');
            self::assertNotNull($result);
            self::assertFileExists($result);

            $result2 = $this->service->resolve('vendor', 'pubvana-test-fix/fixture', 'missing.css');
            self::assertNull($result2);
        } finally {
            @unlink($assetDir . '/fix.css');
            @unlink($assetDir . '/missing.css');
            @rmdir($assetDir);
            @rmdir($pkgDir);
            @rmdir($root . '/vendor/pubvana-test-fix');
        }
    }

    public function testResolveReturnsNullForInvalidType(): void
    {
        self::assertNull($this->service->resolve('widget', 'Blog', 'css/blog.css'));
    }

    public function testResolveReturnsNullForDisallowedExtension(): void
    {
        self::assertNull($this->service->resolve('plugin', 'Blog', 'css/blog.php'));
    }

    public function testResolveReturnsNullForNonExistentFile(): void
    {
        self::assertNull($this->service->resolve('plugin', 'Blog', 'css/does-not-exist.css'));
    }

    public function testResolveReturnsNullForMalformedVendorName(): void
    {
        self::assertNull($this->service->resolve('vendor', 'noscope', 'x.css'));
        self::assertNull($this->service->resolve('vendor', 'a/b/c', 'x.css'));
    }

    public function testResolveNeutralizesDirectoryTraversal(): void
    {
        // '../' is stripped, so the path collapses to css/blog.css which is
        // legitimately inside the plugin assets dir. It must never escape
        // the assets base directory.
        $result = $this->service->resolve('plugin', 'Blog', '../css/blog.css');
        self::assertNotNull($result);
        self::assertStringContainsString('/plugins/Blog/assets/', $result);
    }

    public function testResolveReturnsNullForTraversalOutsideTypeTree(): void
    {
        $result = $this->service->resolve('plugin', 'Blog', '../../../secrets.env');
        self::assertNull($result);
    }

    public function testResolveBasenamesNameToPreventTraversal(): void
    {
        $result = $this->service->resolve('plugin', '../../etc/passwd', 'css/blog.css');
        self::assertNull($result);
    }

    public function testGetMimeTypeMapsKnownExtension(): void
    {
        self::assertSame('text/css', $this->service->getMimeType('foo.css'));
        self::assertSame('image/png', $this->service->getMimeType('img/pic.png'));
        self::assertSame('image/jpeg', $this->service->getMimeType('photo.JPG'));
    }

    public function testGetMimeTypeFallsBackToOctetStream(): void
    {
        self::assertSame('application/octet-stream', $this->service->getMimeType('file.unknown'));
        self::assertSame('application/octet-stream', $this->service->getMimeType('noext'));
    }

    public function testServeHaltsForMissingFile(): void
    {
        $halted = null;
        $app = $this->app([
            'halt' => function (int $code = 200, string $message = '') use (&$halted): void {
                $halted = ['code' => $code, 'message' => $message];
            },
        ]);
        $service = new AssetService($app);

        $service->serve('/definitely/not/a/file.css');

        self::assertSame(['code' => 404, 'message' => 'Asset not found'], $halted);
    }
}
