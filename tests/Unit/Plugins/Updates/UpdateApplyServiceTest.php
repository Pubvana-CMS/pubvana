<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\Services\UpdateApplyService;
use Pubvana\Plugins\Updates\Services\UpdateProgress;
use Pubvana\Tests\Support\TestCase;
use Pubvana\Tests\Support\ZipFactory;

use function mkdir;
use function file_put_contents;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Trust client stand-in for the apply gate. One shape: a fixed cached
 * answer, a fixed live answer, and an optional outage mode.
 */
final class StubTrustClient
{
    /** @var array{status: string, warning: ?string, checked_at: string}|null */
    private ?array $cached;

    /** @var array{status: string, warning: ?string} */
    private array $live;

    private bool $outage;

    /**
     * @param array{status: string, warning: ?string, checked_at: string}|null $cached
     * @param array{status: string, warning: ?string} $live
     */
    public function __construct(?array $cached, array $live = ['status' => 'unknown', 'warning' => null], bool $outage = false)
    {
        $this->cached = $cached;
        $this->live = $live;
        $this->outage = $outage;
    }

    /**
     * @return array{type: string, slug: string, version: string, author: string, origin: string}
     */
    public function coreItem(string $version): array
    {
        return [
            'type'    => 'plugin',
            'slug'    => 'pubvana/pubvana',
            'version' => $version,
            'author'  => 'pubvana',
            'origin'  => 'composer',
        ];
    }

    /**
     * @return array{status: string, warning: ?string, checked_at: string}|null
     */
    public function getCachedStatus(string $type, string $slug, string $version, string $author): ?array
    {
        return $this->cached;
    }

    /**
     * @return array{status: string, warning: ?string}
     */
    public function checkAddon(string $type, string $slug, string $version, string $author, string $origin): array
    {
        if ($this->outage) {
            throw new \RuntimeException('network down');
        }
        return $this->live;
    }
}

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
    // zipEntriesAreFreeOfSymlinks
    // ------------------------------------------------------------------

    public function testSymlinkZipEntriesAreRejected(): void
    {
        $zipPath = sys_get_temp_dir() . '/pv-sym-' . uniqid() . '.zip';
        ZipFactory::write($zipPath, [
            ['name' => 'link', 'content' => '../victim', 'mode' => 0120777],
            ['name' => 'file.txt', 'content' => 'data', 'mode' => 0100644],
        ]);

        // The name-only check passes these entries; the mode check is the
        // active rejector.
        self::assertTrue(UpdateApplyService::zipEntriesAreSafe(['link', 'file.txt']));

        $zip = new \ZipArchive();
        try {
            if ($zip->open($zipPath) !== true) {
                self::fail('test zip could not be opened');
            }

            self::assertFalse(UpdateApplyService::zipEntriesAreFreeOfSymlinks($zip));
            $zip->close();
        } finally {
            @unlink($zipPath);
        }
    }

    public function testPlainZipHasNoSymlinkEntries(): void
    {
        $zipPath = sys_get_temp_dir() . '/pv-plain-' . uniqid() . '.zip';
        ZipFactory::write($zipPath, [
            ['name' => 'file.txt', 'content' => 'data', 'mode' => 0100644],
            ['name' => 'dir/', 'content' => '', 'mode' => 040755],
        ]);

        $zip = new \ZipArchive();
        try {
            if ($zip->open($zipPath) !== true) {
                self::fail('test zip could not be opened');
            }

            self::assertTrue(UpdateApplyService::zipEntriesAreFreeOfSymlinks($zip));
            $zip->close();
        } finally {
            @unlink($zipPath);
        }
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

    public function testCopyDirectoryNeverFollowsSymlinks(): void
    {
        $root    = sys_get_temp_dir() . '/pv-copy-' . uniqid();
        $outside = $root . '-outside';
        $source  = $root . '/src';
        $dest    = $root . '/dst';

        @mkdir($source, 0775, true);
        @mkdir($outside, 0775, true);
        file_put_contents($outside . '/precious.txt', 'precious');
        file_put_contents($source . '/real.txt', 'real');

        if (!@symlink($outside, $source . '/linkdir')) {
            self::markTestSkipped('The filesystem does not support symlinks.');
        }

        try {
            $count = UpdateApplyService::copyDirectory($source, $dest);

            // real.txt plus one link, never a copy of the linked dir.
            self::assertSame(2, $count);
            self::assertFileExists($dest . '/real.txt');
            self::assertTrue(is_link($dest . '/linkdir'));
            self::assertSame($outside, readlink($dest . '/linkdir'));
            self::assertFileDoesNotExist($dest . '/linkdir-real');
            self::assertFileExists($outside . '/precious.txt');
        } finally {
            UpdateApplyService::removeDirectory($source);
            UpdateApplyService::removeDirectory($dest);
            UpdateApplyService::removeDirectory($outside);
        }
    }

    public function testRemoveDirectoryUnlinksSymlinksInsteadOfDeletingTargets(): void
    {
        $root    = sys_get_temp_dir() . '/pv-rm-' . uniqid();
        $outside = $root . '-outside';
        $dir     = $root . '/dir';

        @mkdir($dir, 0775, true);
        @mkdir($outside, 0775, true);
        file_put_contents($outside . '/precious.txt', 'precious');

        if (!@symlink($outside, $dir . '/link')) {
            self::markTestSkipped('The filesystem does not support symlinks.');
        }

        try {
            UpdateApplyService::removeDirectory($dir);

            // is_dir() follows a link; without the is_link() guard this
            // removal would recurse into $outside and delete precious.txt.
            self::assertDirectoryDoesNotExist($dir);
            self::assertDirectoryExists($outside);
            self::assertFileExists($outside . '/precious.txt');
        } finally {
            UpdateApplyService::removeDirectory($outside);
        }
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

    // ------------------------------------------------------------------
    // Trust gate (applyRefusal): same posture as the activation gates
    // ------------------------------------------------------------------

    private function gateDir(): string
    {
        $dir = sys_get_temp_dir() . '/pv-updates-gate-' . uniqid();
        @mkdir($dir, 0775, true);
        return $dir;
    }

    private function makeService(StubTrustClient $client, string $dir): UpdateApplyService
    {
        $app = $this->app([
            'trustClient' => fn (): StubTrustClient => $client,
        ]);

        return new UpdateApplyService($app, ['updates_path' => $dir]);
    }

    public function testMaliciousReleaseIsRefused(): void
    {
        $dir = $this->gateDir();
        try {
            $service = $this->makeService(new StubTrustClient([
                'status'     => 'malicious',
                'warning'    => 'backdoor found',
                'checked_at' => date('Y-m-d H:i:s'),
            ]), $dir);

            $result = $service->apply('9.9.9', 'cli', true);

            self::assertFalse($result);
            $progress = (new UpdateProgress($dir))->read();
            $error = (string) ($progress['error'] ?? '');
            self::assertStringContainsString('malicious', $error);
            self::assertStringContainsString('backdoor found', $error);
        } finally {
            UpdateApplyService::removeDirectory($dir);
        }
    }

    public function testUnEvaluatedReleaseRefusedForAutomaticRuns(): void
    {
        $dir = $this->gateDir();
        try {
            $service = $this->makeService(new StubTrustClient([
                'status'     => 'unknown',
                'warning'    => null,
                'checked_at' => date('Y-m-d H:i:s'),
            ]), $dir);

            $result = $service->apply('9.9.9', 'cron', false);

            self::assertFalse($result);
            $progress = (new UpdateProgress($dir))->read();
            $error = (string) ($progress['error'] ?? '');
            self::assertStringContainsString('not evaluated', $error);
        } finally {
            UpdateApplyService::removeDirectory($dir);
        }
    }

    public function testUnEvaluatedReleaseAllowedForManualRuns(): void
    {
        $dir = $this->gateDir();
        try {
            $service = $this->makeService(new StubTrustClient([
                'status'     => 'unknown',
                'warning'    => null,
                'checked_at' => date('Y-m-d H:i:s'),
            ]), $dir);

            $service->apply('9.9.9', 'cli', true);

            // The run gets past the trust gate and dies in preflight (no
            // Backups plugin in the stand-in app). The error must not be a
            // trust refusal.
            $progress = (new UpdateProgress($dir))->read();
            $error = (string) ($progress['error'] ?? '');
            self::assertStringNotContainsString('trust service', $error);
            self::assertStringNotContainsString('malicious', $error);
        } finally {
            UpdateApplyService::removeDirectory($dir);
        }
    }

    public function testTrustOutageDoesNotBlockManualUpdate(): void
    {
        $dir = $this->gateDir();
        try {
            $service = $this->makeService(new StubTrustClient(null, [], true), $dir);

            $service->apply('9.9.9', 'cli', true);

            $progress = (new UpdateProgress($dir))->read();
            $error = (string) ($progress['error'] ?? '');
            self::assertStringNotContainsString('trust service', $error);
        } finally {
            UpdateApplyService::removeDirectory($dir);
        }
    }
}
