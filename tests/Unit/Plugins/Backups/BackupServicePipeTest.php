<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Tests\Support\TestCase;

use function chmod;
use function file_put_contents;
use function getenv;
use function mkdir;
use function putenv;
use function sys_get_temp_dir;
use function uniqid;

/**
 * BackupService reads a child process's stdout and stderr together.
 *
 * The fake mysqldump fills stderr past the 64KB pipe buffer, which deadlocks
 * a reader that drains stdout to EOF first. A hang here means the concurrent
 * drain regressed.
 *
 * @package Pubvana\Tests\Unit\Plugins\Backups
 */
#[CoversClass(BackupService::class)]
final class BackupServicePipeTest extends TestCase
{
    private string $binDir = '';

    /** @var string|false */
    private string|false $pathBackup = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->binDir = sys_get_temp_dir() . '/pv-bin-' . uniqid('', true);
        @mkdir($this->binDir, 0775, true);

        $script = "#!/bin/sh\n"
            . "printf 'SELECT 1;\\n'\n"
            . "yes x | head -c 150000 >&2\n"
            . "exit 0\n";
        file_put_contents($this->binDir . '/mysqldump', $script);
        @chmod($this->binDir . '/mysqldump', 0755);

        $this->pathBackup = getenv('PATH');
        putenv('PATH=' . $this->binDir . ':' . ($this->pathBackup === false ? '' : $this->pathBackup));
    }

    protected function tearDown(): void
    {
        putenv('PATH=' . ($this->pathBackup === false ? '' : $this->pathBackup));
        @unlink($this->binDir . '/mysqldump');
        @rmdir($this->binDir . '/backups');
        @rmdir($this->binDir);
        parent::tearDown();
    }

    public function testAChildFillingStderrDoesNotDeadlockTheRead(): void
    {
        $service = new BackupService(
            new \PDO('sqlite::memory:'),
            ['backup_path' => $this->binDir . '/backups'],
            ['host' => 'localhost', 'port' => 3306, 'dbname' => 'db', 'user' => 'u', 'password' => 'p'],
        );

        $sql = $service->dumpDatabase();

        self::assertStringContainsString('SELECT 1;', $sql);
    }
}
