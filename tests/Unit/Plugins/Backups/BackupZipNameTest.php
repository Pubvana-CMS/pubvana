<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Tests\Support\TestCase;

/**
 * BackupService zip naming and archiving rules.
 *
 * Covers the second-precision collision guard (two backups in the same
 * second must never overwrite each other) and that symlinks inside the
 * backed-up trees are never archived.
 *
 * @package Pubvana\Tests\Unit\Plugins\Backups
 */
#[CoversClass(BackupService::class)]
final class BackupZipNameTest extends TestCase
{
    private string $backupDir;
    private string $linkTarget;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = sys_get_temp_dir() . '/pv-backups-' . uniqid('', true) . '/';
        $this->linkTarget = sys_get_temp_dir() . '/pv-backup-target-' . uniqid('', true) . '.txt';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->backupDir . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->backupDir);
        @unlink($this->linkTarget);
        parent::tearDown();
    }

    public function testTwoBackupsInTheSameSecondDoNotCollide(): void
    {
        $service = $this->service();

        $first  = $service->createBackup('manual', 'test');
        $second = $service->createBackup('manual', 'test');

        self::assertNotSame(basename($first), basename($second));
        self::assertFileExists($first);
        self::assertFileExists($second);
        foreach ([basename($first), basename($second)] as $name) {
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{6}-full\.zip$/', $name);
        }
    }

    public function testAnExistingFileIsNeverOverwritten(): void
    {
        $service = $this->service(); // constructor creates the backup dir
        $existing = $this->backupDir . date('Y-m-d_His') . '-full.zip';
        file_put_contents($existing, 'not-a-real-backup');

        $created = $service->createBackup('manual', 'test');

        self::assertSame('not-a-real-backup', (string) file_get_contents($existing), 'the colliding name must not be touched');
        self::assertNotSame($existing, $created);
        self::assertFileExists($created);
    }

    public function testSymlinksAreNeverArchived(): void
    {
        file_put_contents($this->linkTarget, 'outside');
        $contentDir = sys_get_temp_dir() . '/pv-backup-content-' . uniqid('', true) . '/';
        mkdir($contentDir, 0775, true);
        file_put_contents($contentDir . 'real.txt', 'content');
        self::assertTrue(@symlink($this->linkTarget, $contentDir . 'link.txt'));

        $zipPath = sys_get_temp_dir() . '/pv-backup-links-' . uniqid('', true) . '.zip';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        $method = new \ReflectionMethod(BackupService::class, 'zipDirectory');
        $method->setAccessible(true);
        $method->invoke($this->service(), $zip, $contentDir, 'content/');
        $zip->close();

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = (string) $zip->getNameIndex($i);
        }
        $zip->close();

        self::assertTrue(in_array('content/real.txt', $names, true), 'the real file is archived');
        self::assertFalse(in_array('content/link.txt', $names, true), 'links are not archived');
        @unlink($zipPath);
        $this->removeDir($contentDir);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * BackupService with the database dump stubbed out so no live MySQL
     * connection is needed; only zip naming/archiving is under test.
     */
    private function service(): BackupService
    {
        $service = new class($this->backupDir) extends BackupService {
            public function __construct(string $backupDir)
            {
                parent::__construct(
                    new \PDO('sqlite::memory:'),
                    ['backup_path' => $backupDir, 'backup_dirs' => [], 'max_backups' => 15],
                    ['host' => '', 'port' => 0, 'dbname' => '', 'user' => '', 'password' => ''],
                );
            }

            public function dumpDatabase(): string
            {
                return "SELECT 1;\n";
            }
        };

        return $service;
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
