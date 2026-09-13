<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Tests\Support\TestCase;

/**
 * BackupsAdminController download streaming guard.
 *
 * The download path must stream in chunks; a whole-file read would pull a
 * multi-gigabyte zip into memory (M15). Content-Length is still set.
 *
 * @package Pubvana\Tests\Unit\Plugins\Backups
 */
#[CoversClass(\Pubvana\Plugins\Backups\Controllers\BackupsAdminController::class)]
final class BackupDownloadStreamGuardTest extends TestCase
{
    public function testDownloadStreamsInChunks(): void
    {
        $source = (string) file_get_contents(PROJECT_ROOT . '/plugins/Backups/Controllers/BackupsAdminController.php');

        self::assertStringContainsString("fopen(\$path, 'rb')", $source);
        self::assertStringContainsString('fread($handle, 65536)', $source);
        self::assertStringNotContainsString('file_get_contents($path)', $source);
        self::assertStringContainsString("header('Content-Length'", $source);
    }
}
