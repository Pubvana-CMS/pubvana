<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Plugins\Backups\Services\RestoreService;
use Pubvana\Tests\Support\TestCase;
use Pubvana\Tests\Support\ZipFactory;

/**
 * RestoreService zip safety and link-safe recursion.
 *
 * Covers the M15 hardening that H1 deferred here: traversal, absolute,
 * drive-letter, and NUL entry names rejected; symlink entries rejected
 * through the Unix mode bit; a validated zip extracts; the copy walk
 * replicates links instead of traversing them; cleanup unlinks links
 * instead of following them out of the tree.
 *
 * @package Pubvana\Tests\Unit\Plugins\Backups
 */
#[CoversClass(RestoreService::class)]
final class RestoreServiceZipSafetyTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Entry-name validation
    // ------------------------------------------------------------------

    public function testTraversalNamesAreRejected(): void
    {
        self::assertFalse(RestoreService::zipEntriesAreSafe(['app/../../etc/passwd']));
        self::assertFalse(RestoreService::zipEntriesAreSafe(['app/../..']));
    }

    public function testBackslashTraversalIsRejected(): void
    {
        self::assertFalse(RestoreService::zipEntriesAreSafe(['app\\..\\..\\evil.txt']));
    }

    public function testAbsolutePathsDriveLettersAndNulAreRejected(): void
    {
        self::assertFalse(RestoreService::zipEntriesAreSafe(['/etc/passwd']));
        self::assertFalse(RestoreService::zipEntriesAreSafe(['C:/windows/system32']));
        self::assertFalse(RestoreService::zipEntriesAreSafe(["app\0/evil.txt"]));
        self::assertFalse(RestoreService::zipEntriesAreSafe(['']));
    }

    public function testCleanNamesPass(): void
    {
        self::assertTrue(RestoreService::zipEntriesAreSafe([
            'app/config/routes.php',
            'public/index.php',
            'themes/default/Views/x.tpl',
        ]));
    }

    // ------------------------------------------------------------------
    // Symlink entries
    // ------------------------------------------------------------------

    public function testASymlinkEntryIsRejected(): void
    {
        $path = sys_get_temp_dir() . '/pv-restore-sym-' . uniqid('', true) . '.zip';
        ZipFactory::write($path, [
            ['name' => 'app/index.php', 'content' => '<?php echo 1;', 'mode' => 0100644],
            ['name' => 'app/link', 'content' => '/etc/passwd', 'mode' => 0120777],
        ]);

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path));
        self::assertFalse(RestoreService::zipEntriesAreFreeOfSymlinks($zip));
        $zip->close();
        @unlink($path);
    }

    public function testPlainEntriesPassTheSymlinkCheck(): void
    {
        $path = sys_get_temp_dir() . '/pv-restore-plain-' . uniqid('', true) . '.zip';
        ZipFactory::write($path, [
            ['name' => 'app/index.php', 'content' => '<?php echo 1;', 'mode' => 0100644],
        ]);

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path));
        self::assertTrue(RestoreService::zipEntriesAreFreeOfSymlinks($zip));
        $zip->close();
        @unlink($path);
    }

    // ------------------------------------------------------------------
    // extract() end to end
    // ------------------------------------------------------------------

    public function testAHostileZipIsNotExtracted(): void
    {
        $path = sys_get_temp_dir() . '/pv-restore-hostile-' . uniqid('', true) . '.zip';
        ZipFactory::write($path, [
            ['name' => 'app/link', 'content' => '/etc', 'mode' => 0120777],
        ]);

        $dest = sys_get_temp_dir() . '/pv-restore-dest-' . uniqid('', true) . '/';
        self::assertFalse($this->extractViaReflection($path, $dest));
        self::assertFileDoesNotExist($dest . 'app/link');
        @unlink($path);
        $this->removeDir($dest);
    }

    public function testAValidZipExtracts(): void
    {
        $path = sys_get_temp_dir() . '/pv-restore-ok-' . uniqid('', true) . '.zip';
        ZipFactory::write($path, [
            ['name' => 'app/config/routes.php', 'content' => 'route', 'mode' => 0100644],
        ]);

        $dest = sys_get_temp_dir() . '/pv-restore-dest-' . uniqid('', true) . '/';
        self::assertTrue($this->extractViaReflection($path, $dest));
        self::assertSame('route', (string) file_get_contents($dest . 'app/config/routes.php'));
        @unlink($path);
        $this->removeDir($dest);
    }

    // ------------------------------------------------------------------
    // copyDirectory / removeDirectory link handling
    // ------------------------------------------------------------------

    public function testCopyReplicatesALinkInsteadOfTraversingIt(): void
    {
        $outside = sys_get_temp_dir() . '/pv-restore-outside-' . uniqid('', true);
        mkdir($outside, 0775, true);
        file_put_contents($outside . '/secret.txt', 'outside');

        $src = sys_get_temp_dir() . '/pv-restore-src-' . uniqid('', true) . '/';
        mkdir($src, 0775, true);
        file_put_contents($src . 'keep.txt', 'keep');
        @symlink($outside, $src . 'evil');

        $dest = sys_get_temp_dir() . '/pv-restore-copydest-' . uniqid('', true) . '/';

        $service = $this->service();
        $method = new \ReflectionMethod($service, 'copyDirectory');
        $method->setAccessible(true);
        $method->invoke($service, $src, $dest);

        self::assertSame('keep', (string) file_get_contents($dest . 'keep.txt'));
        self::assertTrue(is_link($dest . 'evil'), 'the link is replicated, not followed');
        self::assertSame('outside', (string) file_get_contents($dest . 'evil/secret.txt'));

        @unlink($src . 'evil');
        $this->removeDir($src);
        $this->removeDir($dest);
        $this->removeDir($outside);
    }

    public function testRemoveUnlinksALinkInsteadOfDeletingItsTarget(): void
    {
        $outside = sys_get_temp_dir() . '/pv-remove-outside-' . uniqid('', true);
        mkdir($outside, 0775, true);
        file_put_contents($outside . 'important.txt', 'important');

        $tree = sys_get_temp_dir() . '/pv-remove-tree-' . uniqid('', true) . '/';
        mkdir($tree, 0775, true);
        @symlink($outside, $tree . 'evil');

        $service = $this->service();
        $method = new \ReflectionMethod($service, 'removeDirectory');
        $method->setAccessible(true);
        $method->invoke($service, $tree);

        self::assertFileDoesNotExist($tree . 'evil');
        self::assertFileExists($outside . 'important.txt', 'the link target survives cleanup');

        $this->removeDir($tree);
        $this->removeDir($outside);
    }

    public function testRemoveUnlinksATopLevelLink(): void
    {
        $target = sys_get_temp_dir() . '/pv-remove-tld-' . uniqid('', true);
        mkdir($target, 0775, true);
        file_put_contents($target . '/x.txt', 'x');

        $link = sys_get_temp_dir() . '/pv-remove-tl-' . uniqid('', true);

        $service = $this->service();
        $method = new \ReflectionMethod($service, 'removeDirectory');
        $method->setAccessible(true);
        $method->invoke($service, $link);

        self::assertFalse(is_link($link));
        self::assertFileExists($target . '/x.txt', 'the link target survives cleanup');
        $this->removeDir($target);
    }

    // ------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * RestoreService with a stubbed BackupService (temp backup dir, no DB).
     */
    private function service(): RestoreService
    {
        $dir = sys_get_temp_dir() . '/pv-restore-svc-' . uniqid('', true) . '/';
        $backup = new class($dir) extends BackupService {
            public function __construct(string $backupDir)
            {
                parent::__construct(
                    new \PDO('sqlite::memory:'),
                    ['backup_path' => $backupDir, 'backup_dirs' => [], 'max_backups' => 1],
                    ['host' => '', 'port' => 0, 'dbname' => '', 'user' => '', 'password' => ''],
                );
            }
        };

        return new RestoreService($backup, []);
    }

    private function extractViaReflection(string $zipPath, string $destDir): bool
    {
        $service = $this->service();
        $method = new \ReflectionMethod($service, 'extract');
        $method->setAccessible(true);

        return (bool) $method->invoke($service, $zipPath, $destDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            is_link($path) ? @unlink($path) : (is_dir($path) ? $this->removeDir($path) : @unlink($path));
        }
        @rmdir($dir);
    }
}
