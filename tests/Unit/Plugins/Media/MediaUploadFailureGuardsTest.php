<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Media;

use PHPUnit\Framework\TestCase;

/**
 * Source guards for AUDIT M11: MediaService must check the return values of
 * move_uploaded_file()/copy(), so a failed write (disk full, permissions)
 * fails loudly instead of leaving half-written artifacts and a DB row.
 */
final class MediaUploadFailureGuardsTest extends TestCase
{
    private string $source = '';

    protected function setUp(): void
    {
        $file = dirname(__DIR__, 4) . '/plugins/Media/Services/MediaService.php';
        self::assertFileExists($file);
        $this->source = (string) file_get_contents($file);
    }

    public function testImageUploadChecksMoveUploadedFile(): void
    {
        self::assertMatchesRegularExpression(
            "/if \(!move_uploaded_file\(.+?\)\) \{\s*\n\s*throw new \\\\RuntimeException\('Failed to store the uploaded image\.'/s",
            $this->source,
        );
    }

    public function testImageUploadChecksCopy(): void
    {
        self::assertMatchesRegularExpression(
            "/if \(!copy\(.+?\)\) \{\s*\n\s*@unlink\(.+?\);\s*\n\s*throw new \\\\RuntimeException\('Failed to stage the uploaded image\.'/s",
            $this->source,
        );
    }

    public function testVideoUploadChecksMoveUploadedFile(): void
    {
        self::assertMatchesRegularExpression(
            "/if \(!move_uploaded_file\(.+?\)\) \{\s*\n\s*throw new \\\\RuntimeException\('Failed to store the uploaded video\.'/s",
            $this->source,
        );
    }

    public function testNoUncheckedMoveUploadedFileOrCopyRemains(): void
    {
        // Every remaining move_uploaded_file/copy call site must sit behind
        // a negating condition (nothing else is matched by these guards).
        self::assertSame(2, substr_count($this->source, '!move_uploaded_file'));
        self::assertSame(1, substr_count($this->source, '!copy('));
    }
}
