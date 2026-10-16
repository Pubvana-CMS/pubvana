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

    public function testImageUploadChecksTheStoreResult(): void
    {
        self::assertMatchesRegularExpression(
            '/\$stored = \$fromUpload\s*\n\s*\? move_uploaded_file\(.+?\)\s*\n\s*: copy\(.+?\);\s*\n\s*if \(!\$stored\) \{\s*\n\s*throw new \\\\RuntimeException\(\'Failed to store the image\.\'/s',
            $this->source,
        );
    }

    public function testImageUploadChecksTheStagingCopy(): void
    {
        self::assertMatchesRegularExpression(
            '/if \(!copy\(.+?\)\) \{\s*\n\s*@unlink\(.+?\);\s*\n\s*throw new \\\\RuntimeException\(\'Failed to stage the image\.\'/s',
            $this->source,
        );
    }

    public function testVideoUploadChecksTheStoreResult(): void
    {
        self::assertMatchesRegularExpression(
            '/\$stored = \$fromUpload\s*\n\s*\? move_uploaded_file\(.+?\)\s*\n\s*: copy\(.+?\);\s*\n\s*if \(!\$stored\) \{\s*\n\s*throw new \\\\RuntimeException\(\'Failed to store the video\.\'/s',
            $this->source,
        );
    }

    public function testNoUncheckedUploadMoveOrCopyRemains(): void
    {
        // copy() sites: the image and video $stored ternaries (checked by
        // their !$stored guards), the negated image staging copy, and
        // revert()'s working copy of an existing original. Both
        // move_uploaded_file() calls ride the ternaries.
        self::assertSame(2, substr_count($this->source, 'move_uploaded_file'));
        self::assertSame(4, substr_count($this->source, 'copy('));
        self::assertSame(2, substr_count($this->source, 'if (!$stored)'));
        self::assertSame(1, substr_count($this->source, 'if (!copy('));
    }
}
