<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Media;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Media\Controllers\MediaAdminController;
use Pubvana\Plugins\Media\Models\Media;
use Pubvana\Plugins\Media\Services\MediaService;
use Pubvana\Plugins\Media\Services\VideoThumbnailService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * MediaService metadata flows plus controller serialization helpers.
 *
 * Uploads via move_uploaded_file cannot run under CLI, so upload paths
 * stay out; validateUpload and provider detection run through invoke().
 * Image edit paths use real GD files under a temp public path.
 */
#[CoversClass(MediaService::class)]
#[CoversClass(MediaAdminController::class)]
#[CoversClass(VideoThumbnailService::class)]
final class MediaServiceTest extends TestCase
{
    private PDO $pdo;
    private string $publicPath = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->pdo->exec(
            'CREATE TABLE media (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                type           TEXT NOT NULL,
                filename       TEXT NOT NULL,
                path           TEXT,
                mime_type      TEXT,
                size           INTEGER,
                alt_text       TEXT,
                title          TEXT,
                embed_url      TEXT,
                embed_provider TEXT,
                poster_path    TEXT,
                uploaded_by    INTEGER,
                created_at     TEXT,
                updated_at     TEXT
            )'
        );
        $dir = sys_get_temp_dir() . '/pv-media-' . uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));
        $this->publicPath = $dir;
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->publicPath);
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function service(array $config = []): MediaService
    {
        return new MediaService($this->pdo, array_merge([
            'routePrepend' => 'media',
            'upload_path' => 'uploads',
            'max_image_size' => 10 * 1024 * 1024,
            'max_video_size' => 100 * 1024 * 1024,
            'allowed_image_ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'allowed_video_ext' => ['mp4', 'webm', 'mov'],
            'webp_quality' => 80,
            'thumb_width' => 40,
            'medium_width' => 80,
        ], $config), $this->publicPath);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function makeImage(array $extra = []): Media
    {
        return (new Media($this->pdo))->createRecord(array_merge([
            'type' => 'image',
            'filename' => 'photo.png',
            'path' => 'uploads/2026/01/abc.png',
            'mime_type' => 'image/png',
            'size' => 100,
            'uploaded_by' => 1,
        ], $extra));
    }

    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private function writePng(string $relPath, int $width = 120, int $height = 80): string
    {
        $abs = $this->publicPath . '/' . $relPath;
        self::assertTrue(mkdir(dirname($abs), 0777, true) || is_dir(dirname($abs)));
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        $red = imagecolorallocate($image, 200, 30, 30);
        self::assertNotFalse($red);
        imagefill($image, 0, 0, $red);
        imagepng($image, $abs);
        imagedestroy($image);

        return $abs;
    }

    private function tmpUpload(string $name, string $contents): string
    {
        $path = sys_get_temp_dir() . '/' . uniqid('pv-up-', true) . '-' . $name;
        file_put_contents($path, $contents);

        return $path;
    }

    public function testStoreEmbedDetectsProvider(): void
    {
        $service = $this->service();

        $yt = $service->storeEmbed('https://www.youtube.com/watch?v=abc', 1);
        self::assertSame('youtube', (string) $yt->embed_provider);

        $vimeo = $service->storeEmbed('https://vimeo.com/123', 1);
        self::assertSame('vimeo', (string) $vimeo->embed_provider);

        $other = $service->storeEmbed('https://example.com/page', 1);
        self::assertNull($other->embed_provider);
    }

    public function testValidateUpload(): void
    {
        $service = $this->service();

        $png = $this->writePng('uploads/2026/01/valid.png', 10, 10);
        $service->storeEmbed('https://example.com', 1);

        // Error code rejected.
        try {
            $this->invoke($service, 'validateUpload', [[
                'name' => 'a.png', 'type' => 'image/png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_NO_FILE, 'size' => 10,
            ], 'image']);
            self::fail('error code must throw');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('error code', $e->getMessage());
        }

        // Extension rejected.
        $tmp = $this->tmpUpload('evil.php', '<?php echo 1;');
        try {
            $this->invoke($service, 'validateUpload', [[
                'name' => 'evil.php', 'type' => 'image/jpeg', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 10,
            ], 'image']);
            self::fail('bad extension must throw');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('not allowed', $e->getMessage());
        }

        // Oversize rejected.
        try {
            $this->invoke($service, 'validateUpload', [[
                'name' => 'big.png', 'type' => 'image/png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_OK, 'size' => 999 * 1024 * 1024,
            ], 'image']);
            self::fail('oversize must throw');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('maximum size', $e->getMessage());
        }

        // Content mismatch rejected (text file named .png).
        try {
            $this->invoke($service, 'validateUpload', [[
                'name' => 'fake.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 10,
            ], 'image']);
            self::fail('content mismatch must throw');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('content', $e->getMessage());
        }

        // Valid file passes without throwing.
        $this->invoke($service, 'validateUpload', [[
            'name' => 'ok.png', 'type' => 'image/png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_OK, 'size' => 100,
        ], 'image']);

        @unlink($tmp);
    }

    public function testQueries(): void
    {
        $service = $this->service();

        self::assertNull($service->find(99999));
        self::assertSame(0, $service->countAll());
        self::assertSame([], $service->recent(5));

        $a = $this->makeImage(['filename' => 'a.png']);
        $this->makeImage(['filename' => 'b.mp4', 'type' => 'video', 'path' => 'uploads/2026/01/b.mp4']);

        self::assertNotNull($service->find((int) $a->id));
        self::assertSame(2, $service->countAll());
        self::assertSame(1, $service->countAll('video'));

        $list = $service->list(1, 1);
        self::assertSame(2, $list['total']);
        self::assertCount(1, $list['items']);

        $recent = $service->recent(1, 'image');
        self::assertCount(1, $recent);
        self::assertSame('a.png', (string) $recent[0]->filename);
    }

    public function testUpdateMeta(): void
    {
        $service = $this->service();

        self::assertNull($service->updateMeta(99999, ['title' => 'x']));

        $media = $this->makeImage();
        $updated = $service->updateMeta((int) $media->id, ['title' => '  Nice  ']);
        self::assertNotNull($updated);
        self::assertSame('Nice', (string) $updated->title);
    }

    public function testApplyEditAndRevert(): void
    {
        if (!extension_loaded('gd')) {
            self::markTestSkipped('GD extension not available.');
        }
        $service = $this->service();

        self::assertNull($service->applyEdit(99999, 'rotate'));
        $video = $this->makeImage(['type' => 'video', 'path' => 'uploads/2026/01/v.mp4']);
        self::assertNull($service->applyEdit((int) $video->id, 'rotate'));

        $media = $this->makeImage(['path' => 'uploads/2026/01/work.png']);
        self::assertNull($service->applyEdit((int) $media->id, 'rotate'));

        $working = $this->writePng('uploads/2026/01/work.png', 120, 80);
        $originalDir = dirname($working) . '/originals';
        mkdir($originalDir, 0777, true);
        copy($working, $originalDir . '/work.png');
        $before = md5_file($working);
        self::assertNotFalse($before);

        $edited = $service->applyEdit((int) $media->id, 'rotate', ['degrees' => 90]);
        self::assertNotNull($edited);
        self::assertNotSame($before, md5_file($working));
        self::assertFileExists(dirname($working) . '/medium/work.webp');
        self::assertFileExists(dirname($working) . '/thumbs/work.webp');

        try {
            $service->applyEdit((int) $media->id, 'bogus-op');
            self::fail('unknown operation must throw');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('Unknown operation', $e->getMessage());
        }

        $reverted = $service->revert((int) $media->id);
        self::assertNotNull($reverted);
        self::assertSame($before, md5_file($working));

        self::assertNull($service->revert(99999));
        @unlink($originalDir . '/work.png');
        self::assertNull($service->revert((int) $media->id));
    }

    public function testGetImageInfoAndExifAndCapabilities(): void
    {
        if (!extension_loaded('gd')) {
            self::markTestSkipped('GD extension not available.');
        }
        $service = $this->service();

        self::assertNull($service->getImageInfo(99999));
        $video = $this->makeImage(['type' => 'video', 'path' => 'uploads/2026/01/v.mp4']);
        self::assertNull($service->getImageInfo((int) $video->id));

        $media = $this->makeImage(['path' => 'uploads/2026/01/info.png']);
        $this->writePng('uploads/2026/01/info.png', 50, 25);

        $info = $service->getImageInfo((int) $media->id);
        self::assertNotNull($info);
        self::assertSame(50, $info['width']);
        self::assertSame(25, $info['height']);

        self::assertSame([], $service->getExifData((int) $media->id));
        self::assertSame([], $service->getExifData(99999));
        self::assertContains('crop', $service->getCapabilities());
    }

    public function testDeleteRemovesFilesAndRow(): void
    {
        $service = $this->service();

        self::assertFalse($service->delete(99999));

        $media = $this->makeImage(['path' => 'uploads/2026/01/del.png']);
        $working = $this->writePng('uploads/2026/01/del.png', 60, 40);
        $dir = dirname($working);
        mkdir($dir . '/originals', 0777, true);
        mkdir($dir . '/medium', 0777, true);
        mkdir($dir . '/thumbs', 0777, true);
        copy($working, $dir . '/originals/del.png');
        file_put_contents($dir . '/medium/del.webp', 'm');
        file_put_contents($dir . '/thumbs/del.webp', 't');
        file_put_contents($dir . '/thumbs/del_poster.jpg', 'p');
        $media->updateMeta(['poster_path' => 'uploads/2026/01/thumbs/del_poster.jpg']);

        self::assertTrue($service->delete((int) $media->id));
        self::assertFileDoesNotExist($working);
        self::assertFileDoesNotExist($dir . '/originals/del.png');
        self::assertFileDoesNotExist($dir . '/medium/del.webp');
        self::assertFileDoesNotExist($dir . '/thumbs/del.webp');
        self::assertFileDoesNotExist($dir . '/thumbs/del_poster.jpg');
        self::assertNull($service->find((int) $media->id));
    }

    public function testPickerAvatarJoditRender(): void
    {
        $service = $this->service();

        $picker = $service->picker('image', '/uploads/x.png');
        self::assertStringContainsString('media-picker-1', $picker);
        self::assertStringContainsString('name="image"', $picker);

        $picker2 = $service->picker('image2');
        self::assertStringContainsString('media-picker-2', $picker2);

        $avatar = $service->avatarPicker('avatar');
        self::assertStringContainsString('avatar-picker-1', $avatar);

        $jodit = $service->joditInit('#body');
        self::assertStringContainsString('jodit-media-1', $jodit);
        self::assertStringContainsString('#body', $jodit);
    }

    public function testVideoThumbnailUnavailableWithoutFfmpeg(): void
    {
        $thumb = new VideoThumbnailService();
        if ($thumb->isAvailable()) {
            self::markTestSkipped('ffmpeg is available here.');
        }

        self::assertFalse($thumb->isAvailable());
        self::assertFalse($thumb->extract('/nonexistent.mp4', '/tmp/out.jpg'));
    }

    public function testControllerAdminBaseAndMediaToArray(): void
    {
        $app = $this->app([
            'pluginLoader' => static fn (): object => new class {
                public function routePrefix(string $plugin): string
                {
                    return '/media';
                }
            },
        ]);
        $controller = new MediaAdminController($app);

        self::assertSame('/admin/media', $this->invoke($controller, 'adminBase'));

        $media = $this->makeImage([
            'path' => 'uploads/2026/01/abc.png',
            'poster_path' => 'uploads/2026/01/thumbs/abc_poster.jpg',
        ]);

        /** @var array<string, mixed> $row */
        $row = $this->invoke($controller, 'mediaToArray', [$media]);
        self::assertSame('/uploads/2026/01/abc.png', $row['url']);
        self::assertSame('/uploads/2026/01/thumbs/abc.webp', $row['thumb_url']);
        self::assertSame('/uploads/2026/01/medium/abc.webp', $row['medium_url']);
        self::assertSame('/uploads/2026/01/thumbs/abc_poster.jpg', $row['poster_url']);

        $video = $this->makeImage(['type' => 'video', 'path' => 'uploads/2026/01/v.mp4', 'poster_path' => null]);
        /** @var array<string, mixed> $vrow */
        $vrow = $this->invoke($controller, 'mediaToArray', [$video]);
        self::assertSame('/uploads/2026/01/v.mp4', $vrow['url']);
        self::assertArrayNotHasKey('thumb_url', $vrow);
    }
}
