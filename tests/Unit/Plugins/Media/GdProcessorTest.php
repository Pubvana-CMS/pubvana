<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Media;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Media\Services\GdProcessor;
use Pubvana\Tests\Support\TestCase;

/**
 * GdProcessor image operations against generated fixtures.
 *
 * Requires the GD extension (present in this environment).
 */
#[CoversClass(GdProcessor::class)]
final class GdProcessorTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('gd')) {
            self::markTestSkipped('GD extension not available.');
        }
        $dir = sys_get_temp_dir() . '/pv-gd-' . uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));
        $this->tmpDir = $dir;
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private function fixture(int $width = 100, int $height = 60): string
    {
        $path = $this->tmpDir . '/src.png';
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        $red = imagecolorallocate($image, 200, 30, 30);
        self::assertNotFalse($red);
        imagefill($image, 0, 0, $red);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    public function testRequireImageGuardsEditMethods(): void
    {
        $this->expectException(\LogicException::class);
        (new GdProcessor())->resize(50);
    }

    public function testLoadRejectsMissingAndUnsupported(): void
    {
        $caught = false;
        set_error_handler(static fn (): bool => true);
        try {
            (new GdProcessor())->load($this->tmpDir . '/missing.png');
        } catch (\InvalidArgumentException) {
            $caught = true;
        } finally {
            restore_error_handler();
        }
        self::assertTrue($caught);
    }

    public function testLoadRejectsNonImage(): void
    {
        $path = $this->tmpDir . '/note.txt';
        file_put_contents($path, 'hello');

        $caught = false;
        set_error_handler(static fn (): bool => true);
        try {
            (new GdProcessor())->load($path);
        } catch (\InvalidArgumentException) {
            $caught = true;
        } finally {
            restore_error_handler();
        }
        self::assertTrue($caught);
    }

    public function testResizeDownscalesOnly(): void
    {
        $src = $this->fixture(100, 60);

        $out = $this->tmpDir . '/resized.png';
        (new GdProcessor())->load($src)->resize(50)->save($out);
        $info = (new GdProcessor())->getInfo($out);
        self::assertSame(50, $info['width']);
        self::assertSame(30, $info['height']);

        // Source narrower than target: unchanged.
        $same = $this->tmpDir . '/same.png';
        (new GdProcessor())->load($src)->resize(500)->save($same);
        self::assertSame(100, (new GdProcessor())->getInfo($same)['width']);
    }

    public function testResizeRejectsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new GdProcessor())->load($this->fixture())->resize(0);
    }

    public function testCropRotateFlipSharpenBrightnessContrast(): void
    {
        $src = $this->fixture(100, 60);

        $out = $this->tmpDir . '/crop.png';
        (new GdProcessor())->load($src)->crop(10, 10, 40, 20)->save($out);
        $info = (new GdProcessor())->getInfo($out);
        self::assertSame(40, $info['width']);
        self::assertSame(20, $info['height']);

        $this->expectException(\InvalidArgumentException::class);
        (new GdProcessor())->load($src)->crop(0, 0, 0, 10);
    }

    public function testRotateFlipAndFilters(): void
    {
        $src = $this->fixture(100, 60);

        $rotated = $this->tmpDir . '/rot.png';
        (new GdProcessor())->load($src)->rotate(90)->save($rotated);
        $info = (new GdProcessor())->getInfo($rotated);
        self::assertSame(60, $info['width']);
        self::assertSame(100, $info['height']);

        $flipped = $this->tmpDir . '/flip.png';
        (new GdProcessor())->load($src)->flip('horizontal')->save($flipped);
        self::assertFileExists($flipped);

        $filtered = $this->tmpDir . '/filt.png';
        (new GdProcessor())->load($src)->sharpen()->brightness(10)->contrast(10)->save($filtered);
        self::assertFileExists($filtered);

        // Zero levels are no-ops but must not fail.
        $noop = $this->tmpDir . '/noop.png';
        (new GdProcessor())->load($src)->brightness(0)->contrast(0)->save($noop);
        self::assertFileExists($noop);
    }

    public function testAutoOrientAndStripExifAreSafe(): void
    {
        $src = $this->fixture();

        $out = $this->tmpDir . '/orient.png';
        (new GdProcessor())->load($src)->autoOrient()->stripExif()->save($out);
        self::assertFileExists($out);
    }

    public function testToWebpAndSaveFormats(): void
    {
        $src = $this->fixture(80, 40);

        $webp = $this->tmpDir . '/out.webp';
        (new GdProcessor())->load($src)->toWebp($webp, 80);
        self::assertFileExists($webp);
        self::assertSame('image/webp', (new GdProcessor())->getInfo($webp)['mime']);

        foreach (['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'] as $ext => $mime) {
            $out = $this->tmpDir . '/out.' . $ext;
            (new GdProcessor())->load($src)->save($out);
            self::assertSame($mime, (new GdProcessor())->getInfo($out)['mime']);
        }
    }

    public function testGetInfoRejectsMissing(): void
    {
        $caught = false;
        set_error_handler(static fn (): bool => true);
        try {
            (new GdProcessor())->getInfo($this->tmpDir . '/missing.png');
        } catch (\InvalidArgumentException) {
            $caught = true;
        } finally {
            restore_error_handler();
        }
        self::assertTrue($caught);
    }

    public function testGetExifEmptyForPng(): void
    {
        self::assertSame([], (new GdProcessor())->getExif($this->fixture()));
    }

    public function testCapabilities(): void
    {
        $caps = (new GdProcessor())->capabilities();

        foreach (['crop', 'rotate', 'flip', 'resize', 'sharpen', 'brightness', 'contrast', 'strip_exif'] as $op) {
            self::assertContains($op, $caps);
        }
    }
}
