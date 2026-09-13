<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Backups\Services\ProgressReporter;
use Pubvana\Tests\Support\TestCase;

/**
 * ProgressReporter flock-based locking.
 *
 * Covers the TOCTOU fix: the lock is held on an open file descriptor, so a
 * second reporter is refused while the first holds it and admitted after
 * release; a crashed holder (free flock, leftover file) is not treated as
 * a live lock, and its stale payload is overwritten by the next acquire.
 *
 * @package Pubvana\Tests\Unit\Plugins\Backups
 */
#[CoversClass(ProgressReporter::class)]
final class ProgressReporterTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/pv-progress-' . uniqid('', true) . '/';
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function testASecondAcquireIsRefusedWhileTheLockIsHeld(): void
    {
        $first = new ProgressReporter('backup', $this->dir);
        self::assertTrue($first->acquireLock());

        $second = new ProgressReporter('restore', $this->dir);
        self::assertFalse($second->acquireLock());

        self::assertTrue(ProgressReporter::isLocked($this->dir));
    }

    public function testReleaseAdmitsTheNextAcquire(): void
    {
        $first = new ProgressReporter('backup', $this->dir);
        $first->acquireLock();
        $first->releaseLock();

        $second = new ProgressReporter('backup', $this->dir);
        self::assertTrue($second->acquireLock());
        $second->releaseLock();
    }

    public function testAStaleLeftoverFileDoesNotBlockANewRun(): void
    {
        // Simulate a crashed run: the lock file exists but nobody holds it.
        file_put_contents($this->dir . 'operation.lock', json_encode([
            'operation'  => 'backup',
            'started_at' => date('c', time() - 4000),
            'pid'        => 12345,
        ]));

        self::assertFalse(ProgressReporter::isLocked($this->dir), 'a free flock is not a live lock');

        $reporter = new ProgressReporter('backup', $this->dir);
        self::assertTrue($reporter->acquireLock());

        $raw = (string) file_get_contents($this->dir . 'operation.lock');
        $payload = json_decode($raw, true);
        self::assertIsArray($payload);
        self::assertNotSame(date('c', time() - 4000), $payload['started_at'], 'the stale payload is replaced');
        $reporter->releaseLock();
    }

    public function testProgressIsWrittenAndReadable(): void
    {
        $reporter = new ProgressReporter('backup', $this->dir);
        self::assertTrue($reporter->acquireLock());

        $reporter->update(2, 5, 'Dumping database', '3 tables left');
        $data = $reporter->read();

        self::assertIsArray($data);
        self::assertSame('in_progress', $data['status']);
        self::assertSame('Dumping database', $data['step_label']);
        self::assertSame(2, $data['steps_completed']);
        self::assertSame(5, $data['steps_total']);
        self::assertSame('3 tables left', $data['detail']);

        $reporter->complete(['detail' => '2026-01-01_120000-full.zip']);
        $data = $reporter->read();
        self::assertSame('completed', $data['status']);

        $reporter->releaseLock();
    }

    public function testAReporterCannotAcquireTwice(): void
    {
        $reporter = new ProgressReporter('backup', $this->dir);
        self::assertTrue($reporter->acquireLock());
        self::assertFalse($reporter->acquireLock());
        $reporter->releaseLock();
    }
}
