<?php

namespace Tests\Unit\Services;

use App\Services\ProgressReporter;
use CodeIgniter\Test\CIUnitTestCase;

class ProgressReporterTest extends CIUnitTestCase
{
    protected string $updatesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updatesDir = WRITEPATH . 'updates/';
        if (! is_dir($this->updatesDir)) {
            mkdir($this->updatesDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up progress/lock files
        foreach (glob($this->updatesDir . '*.json') as $f) @unlink($f);
        foreach (glob($this->updatesDir . '*.lock') as $f) @unlink($f);
        parent::tearDown();
    }

    public function testAcquireLockCreatesLockFile(): void
    {
        $reporter = new ProgressReporter('backup');
        $this->assertTrue($reporter->acquireLock());
        $this->assertFileExists($this->updatesDir . 'operation.lock');
    }

    public function testAcquireLockFailsWhenAlreadyLocked(): void
    {
        $reporter1 = new ProgressReporter('backup');
        $reporter1->acquireLock();

        $reporter2 = new ProgressReporter('update');
        $this->assertFalse($reporter2->acquireLock());
    }

    public function testReleaseLockRemovesLockFile(): void
    {
        $reporter = new ProgressReporter('backup');
        $reporter->acquireLock();
        $reporter->releaseLock();
        $this->assertFileDoesNotExist($this->updatesDir . 'operation.lock');
    }

    public function testUpdateWritesProgressFile(): void
    {
        $reporter = new ProgressReporter('backup');
        $reporter->update(1, 6, 'Backing up app/', 'Processing...');

        $file = $this->updatesDir . 'backup_progress.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertSame('backup', $data['operation']);
        $this->assertSame('in_progress', $data['status']);
        $this->assertSame(1, $data['steps_completed']);
        $this->assertSame(6, $data['steps_total']);
    }

    public function testCompleteWritesFinalStatus(): void
    {
        $reporter = new ProgressReporter('update');
        $reporter->complete();

        $file = $this->updatesDir . 'update_progress.json';
        $data = json_decode(file_get_contents($file), true);
        $this->assertSame('completed', $data['status']);
    }

    public function testErrorWritesErrorStatus(): void
    {
        $reporter = new ProgressReporter('update');
        $reporter->error('migrations', 'Migration failed');

        $file = $this->updatesDir . 'update_progress.json';
        $data = json_decode(file_get_contents($file), true);
        $this->assertSame('error', $data['status']);
        $this->assertSame('Migration failed', $data['error']);
    }
}
