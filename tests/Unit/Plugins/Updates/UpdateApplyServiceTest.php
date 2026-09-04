<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\Services\UpdateApplyService;
use Pubvana\Tests\Support\TestCase;

use function mkdir;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(UpdateApplyService::class)]
final class UpdateApplyServiceTest extends TestCase
{
    // ------------------------------------------------------------------
    // zipEntriesAreSafe
    // ------------------------------------------------------------------

    public function testSafeZipEntriesPass(): void
    {
        self::assertTrue(UpdateApplyService::zipEntriesAreSafe([
            'pubvana.json',
            'app/',
            'app/Services/Foo.php',
            'vendor/composer/autoload.php',
        ]));
    }

    public function testTraversalEntriesAreRejected(): void
    {
        self::assertFalse(UpdateApplyService::zipEntriesAreSafe(['../evil.php']));
        self::assertFalse(UpdateApplyService::zipEntriesAreSafe(['app/../../evil.php']));
        self::assertFalse(UpdateApplyService::zipEntriesAreSafe(['app/..\\..\\evil.php']));
    }

    public function testAbsoluteAndWindowsPathsAreRejected(): void
    {
        self::assertFalse(UpdateApplyService::zipEntriesAreSafe(['/etc/passwd']));
        self::assertFalse(UpdateApplyService::zipEntriesAreSafe(['C:/windows/evil.php']));
        self::assertFalse(UpdateApplyService::zipEntriesAreSafe(["app\0evil.php"]));
        self::assertFalse(UpdateApplyService::zipEntriesAreSafe(['']));
    }

    // ------------------------------------------------------------------
    // detectInnerDir
    // ------------------------------------------------------------------

    public function testDetectsGithubStyleWrapperDirectory(): void
    {
        $names = [
            'pubvana-3.0.1/',
            'pubvana-3.0.1/pubvana.json',
            'pubvana-3.0.1/app/',
            'pubvana-3.0.1/app/Services/Foo.php',
        ];

        self::assertSame('pubvana-3.0.1', UpdateApplyService::detectInnerDir($names));
    }

    public function testFlatLayoutHasNoInnerDir(): void
    {
        $names = ['pubvana.json', 'app/', 'app/Services/Foo.php'];

        self::assertNull(UpdateApplyService::detectInnerDir($names));
    }

    public function testMultipleTopLevelDirsHaveNoInnerDir(): void
    {
        $names = ['docs/', 'docs/x.md', 'app/', 'app/Foo.php'];

        self::assertNull(UpdateApplyService::detectInnerDir($names));
    }

    // ------------------------------------------------------------------
    // isProtected
    // ------------------------------------------------------------------

    public function testProtectedPathsMatchExactlyAndByDirectory(): void
    {
        $protected = ['.env', 'app/config/shield.php', 'writable'];

        self::assertTrue(UpdateApplyService::isProtected('.env', $protected));
        self::assertTrue(UpdateApplyService::isProtected('app/config/shield.php', $protected));
        self::assertTrue(UpdateApplyService::isProtected('writable/logs/x.log', $protected));
        self::assertTrue(UpdateApplyService::isProtected('app\config\shield.php', $protected));
    }

    public function testNonProtectedPathsPass(): void
    {
        $protected = ['.env', 'app/config/shield.php', 'writable'];

        self::assertFalse(UpdateApplyService::isProtected('app/config/services.php', $protected));
        self::assertFalse(UpdateApplyService::isProtected('pubvana.json', $protected));
        self::assertFalse(UpdateApplyService::isProtected('writables/dir', $protected));
    }

    // ------------------------------------------------------------------
    // Filesystem helpers
    // ------------------------------------------------------------------

    public function testCopyDirectoryCopiesTreeAndCountsFiles(): void
    {
        $source = sys_get_temp_dir() . '/pv-src-' . uniqid();
        $dest   = sys_get_temp_dir() . '/pv-dst-' . uniqid();

        @mkdir($source . '/sub', 0775, true);
        @mkdir($source . '/empty', 0775, true);
        file_put_contents($source . '/a.txt', 'a');
        file_put_contents($source . '/sub/b.txt', 'b');

        $seen = [];
        $count = UpdateApplyService::copyDirectory($source, $dest, static function (int $files) use (&$seen): void {
            $seen[] = $files;
        });

        self::assertSame(2, $count);
        self::assertFileExists($dest . '/a.txt');
        self::assertFileExists($dest . '/sub/b.txt');
        self::assertDirectoryExists($dest . '/empty');

        UpdateApplyService::removeDirectory($source);
        UpdateApplyService::removeDirectory($dest);

        self::assertDirectoryDoesNotExist($source);
        self::assertDirectoryDoesNotExist($dest);
    }

    public function testPhasesListIsFixed(): void
    {
        $phases = UpdateApplyService::phases();

        self::assertCount(8, $phases);
        self::assertSame('preflight', $phases[0]['name']);
        self::assertSame('backup', $phases[1]['name']);
        self::assertSame('download', $phases[2]['name']);
        self::assertSame('validate', $phases[3]['name']);
        self::assertSame('extract', $phases[4]['name']);
        self::assertSame('copy', $phases[5]['name']);
        self::assertSame('migrate', $phases[6]['name']);
        self::assertSame('cleanup', $phases[7]['name']);
    }

    // ------------------------------------------------------------------
    // Download detail formatting
    // ------------------------------------------------------------------

    public function testDownloadDetailFormatsTotalAndPercent(): void
    {
        $detail = UpdateApplyService::downloadDetail(1000 * 1024, 250 * 1024);

        self::assertSame('250.0 KB of 1,000.0 KB (25%)', $detail);
    }

    public function testDownloadDetailHandlesUnknownTotal(): void
    {
        self::assertSame('2.5 MB downloaded', UpdateApplyService::downloadDetail(0, 2560 * 1024));
    }
}
