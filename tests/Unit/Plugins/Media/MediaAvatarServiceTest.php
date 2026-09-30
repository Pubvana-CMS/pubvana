<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Media;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Media\Models\Media;
use Pubvana\Plugins\Media\Services\MediaService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * Avatar storage: one square webp per user, no media rows, and cleanup of
 * the previous file.
 *
 * move_uploaded_file() cannot run under CLI, so these tests exercise
 * storeAvatar() with a real temp image passed through the $_FILES-shaped
 * array (the method reads tmp_name directly).
 */
#[CoversClass(MediaService::class)]
final class MediaAvatarServiceTest extends TestCase
{
    private PDO $pdo;
    private string $publicPath = '';

    /** @var list<string> */
    private array $tempFiles = [];

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
        $dir = sys_get_temp_dir() . '/pv-avatar-' . uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));
        $this->publicPath = $dir;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
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
            'avatar_path' => 'uploads/avatars',
            'avatar_size' => 64,
        ], $config), $this->publicPath);
    }

    /**
     * A real PNG on disk, shaped like a $_FILES entry.
     *
     * @param int<1, max> $width
     * @param int<1, max> $height
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}
     */
    private function upload(int $width = 200, int $height = 100): array
    {
        $tmp = sys_get_temp_dir() . '/' . uniqid('pv-avatar-up-', true) . '.png';
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        $fill = imagecolorallocate($image, 10, 120, 200);
        self::assertNotFalse($fill);
        imagefill($image, 0, 0, $fill);
        imagepng($image, $tmp);
        imagedestroy($image);
        $this->tempFiles[] = $tmp;

        return [
            'name' => 'me.png',
            'type' => 'image/png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ];
    }

    public function testStoreAvatarWritesOneSquareWebpNamedForTheUser(): void
    {
        $service = $this->service();

        $path = $service->storeAvatar(7, $this->upload(200, 100));

        self::assertSame('uploads/avatars/7.webp', $path);
        self::assertFileExists($this->publicPath . '/' . $path);

        $info = getimagesize($this->publicPath . '/' . $path);
        self::assertNotFalse($info);
        self::assertSame(64, $info[0], 'the avatar is resized to avatar_size');
        self::assertSame(64, $info[1], 'the avatar is cropped square');
    }

    public function testStoreAvatarReplacesThePreviousFile(): void
    {
        $service = $this->service();

        $first = $service->storeAvatar(7, $this->upload());
        $second = $service->storeAvatar(7, $this->upload());

        self::assertSame($first, $second, 'the filename is the user id, so it overwrites');
        self::assertFileExists($this->publicPath . '/' . $second);
    }

    public function testStoreAvatarWritesNoMediaRow(): void
    {
        $service = $this->service();
        $service->storeAvatar(7, $this->upload());

        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM media');
        self::assertNotFalse($stmt);
        self::assertSame(0, (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? -1));
    }

    public function testStoreAvatarRejectsANonImage(): void
    {
        $service = $this->service();

        $tmp = sys_get_temp_dir() . '/' . uniqid('pv-avatar-txt-', true) . '.png';
        file_put_contents($tmp, 'not an image');
        $this->tempFiles[] = $tmp;

        $this->expectException(\InvalidArgumentException::class);
        $service->storeAvatar(7, [
            'name' => 'me.png',
            'type' => 'image/png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 12,
        ]);
    }

    public function testStoreAvatarRejectsAMissingUser(): void
    {
        $service = $this->service();

        $this->expectException(\InvalidArgumentException::class);
        $service->storeAvatar(0, $this->upload());
    }

    public function testDeleteAvatarFileRemovesOnlyAvatarPaths(): void
    {
        $service = $this->service();
        $path = $service->storeAvatar(7, $this->upload());

        self::assertTrue($service->deleteAvatarFile($path));
        self::assertFileDoesNotExist($this->publicPath . '/' . $path);

        // Already gone.
        self::assertFalse($service->deleteAvatarFile($path));
    }

    public function testDeleteAvatarFileRefusesPathsOutsideTheAvatarDirectory(): void
    {
        $service = $this->service();

        // A library file must survive a stray avatar value.
        $library = 'uploads/2026/01/abc.png';
        self::assertTrue(mkdir($this->publicPath . '/uploads/2026/01', 0777, true));
        file_put_contents($this->publicPath . '/' . $library, 'x');

        self::assertFalse($service->deleteAvatarFile($library));
        self::assertFileExists($this->publicPath . '/' . $library);

        self::assertFalse($service->deleteAvatarFile(''));
    }

    public function testDeleteLegacyAvatarRemovesTheLibraryRowItOwns(): void
    {
        $service = $this->service();
        $media = (new Media($this->pdo))->createRecord([
            'type' => 'image',
            'filename' => 'old.png',
            'path' => 'uploads/2026/01/old.png',
            'mime_type' => 'image/png',
            'size' => 10,
            'uploaded_by' => 7,
        ]);

        $service->deleteLegacyAvatar(7, 'uploads/2026/01/old.png');

        self::assertNull((new Media($this->pdo))->findById((int) $media->id));
    }

    public function testDeleteLegacyAvatarLeavesAnotherUsersFile(): void
    {
        $service = $this->service();
        $media = (new Media($this->pdo))->createRecord([
            'type' => 'image',
            'filename' => 'theirs.png',
            'path' => 'uploads/2026/01/theirs.png',
            'mime_type' => 'image/png',
            'size' => 10,
            'uploaded_by' => 9,
        ]);

        $service->deleteLegacyAvatar(7, 'uploads/2026/01/theirs.png');

        self::assertNotNull((new Media($this->pdo))->findById((int) $media->id));
    }

    public function testDeleteLegacyAvatarIgnoresAvatarPathsAndUnknownRows(): void
    {
        $service = $this->service();

        // Already an avatar path: deleteAvatarFile() owns it.
        $service->deleteLegacyAvatar(7, 'uploads/avatars/7.webp');

        // No media row at all.
        $service->deleteLegacyAvatar(7, 'uploads/2026/01/missing.png');

        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM media');
        self::assertNotFalse($stmt);
        self::assertSame(0, (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? -1));
    }

    public function testAvatarPickerPostsToTheGivenUploadUrl(): void
    {
        $service = $this->service();

        $html = $service->avatarPicker('avatar', 'uploads/avatars/7.webp', '/profile/7/avatar');

        self::assertStringContainsString('avatar-picker-1', $html);
        self::assertStringContainsString("var uploadUrl = '/profile/7/avatar'", $html);
        self::assertStringContainsString('name="avatar"', $html);
    }

    public function testAvatarPickerDefaultsToTheAdminUploadEndpoint(): void
    {
        $service = $this->service();

        $html = $service->avatarPicker('avatar');

        self::assertStringContainsString("var uploadUrl = '/admin/media/upload/image'", $html);
    }

    public function testPublicAvatarPickerUsesThePublicRoute(): void
    {
        $service = $this->service();

        $html = $service->publicAvatarPicker('avatar', '', '/profile/7/avatar');

        self::assertStringContainsString("var uploadUrl = '/profile/7/avatar'", $html);
    }
}
