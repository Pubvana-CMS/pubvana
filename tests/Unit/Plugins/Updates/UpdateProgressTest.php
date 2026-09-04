<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Updates;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Updates\Services\UpdateProgress;
use Pubvana\Tests\Support\TestCase;

use function sys_get_temp_dir;
use function uniqid;
use function file_put_contents;
use function json_encode;
use function time;

#[CoversClass(UpdateProgress::class)]
final class UpdateProgressTest extends TestCase
{
    // ------------------------------------------------------------------
    // Phase state machine
    // ------------------------------------------------------------------

    public function testPhaseProgressionProducesGranularPayload(): void
    {
        $reporter = $this->reporter();

        $reporter->start(PhasesStub::phases());
        $reporter->beginPhase(1);
        $reporter->detail('PHP version - ok');

        $data = $reporter->read();

        self::assertSame('in_progress', $data['status']);
        self::assertSame('preflight', $data['phase']);
        self::assertSame('PHP version - ok', $data['detail']);
        self::assertSame(1, $data['phase_index']);
        self::assertSame(8, $data['phase_total']);
        self::assertSame(0, $data['percent']);
        self::assertCount(8, $data['phases']);
        self::assertSame('active', $data['phases'][0]['status']);

        // Walking sequentially marks every finished phase done.
        $reporter->beginPhase(2);
        $reporter->beginPhase(3);
        $reporter->beginPhase(4);
        $reporter->beginPhase(5);
        $data = $reporter->read();

        self::assertSame('extract', $data['phase']);
        self::assertSame(50, $data['percent']);
        self::assertSame('done', $data['phases'][0]['status']);
        self::assertSame('done', $data['phases'][3]['status']);
        self::assertSame('active', $data['phases'][4]['status']);
        self::assertSame('pending', $data['phases'][5]['status']);

        $reporter->complete(['new_version' => '3.0.1']);
        $data = $reporter->read();

        self::assertSame('completed', $data['status']);
        self::assertSame(100, $data['percent']);
        self::assertSame('3.0.1', $data['result']['new_version']);
        self::assertNull($data['error']);
    }

    public function testErrorRecordsMessageAndPhase(): void
    {
        $reporter = $this->reporter();

        $reporter->start(PhasesStub::phases());
        $reporter->beginPhase(3);
        $reporter->error('Download failed');

        $data = $reporter->read();

        self::assertSame('error', $data['status']);
        self::assertSame('download', $data['phase']);
        self::assertSame('Download failed', $data['error']);
    }

    public function testEchoCallbackMirrorsWrites(): void
    {
        $reporter = $this->reporter();
        $seen     = [];

        $reporter->onWrite(function (string $label, string $detail) use (&$seen): void {
            $seen[] = [$label, $detail];
        });

        $reporter->start(PhasesStub::phases());
        $reporter->beginPhase(2);
        $reporter->detail('[1/4] Backing up app/ directory');

        self::assertNotEmpty($seen);
        self::assertSame('Creating pre-update backup', $seen[1][0]);
        self::assertSame('[1/4] Backing up app/ directory', $seen[2][1]);
    }

    // ------------------------------------------------------------------
    // Locking
    // ------------------------------------------------------------------

    public function testLockIsExclusiveAndReleasable(): void
    {
        $reporter = $this->reporter();

        self::assertTrue($reporter->acquireLock());
        self::assertTrue(UpdateProgress::isLockedInDir($this->dir));

        $second = new UpdateProgress($this->dir);
        self::assertFalse($second->acquireLock());

        $reporter->releaseLock();

        self::assertFalse(UpdateProgress::isLockedInDir($this->dir));
        self::assertTrue($second->acquireLock());
        $second->releaseLock();
    }

    public function testStaleLockIsRecovered(): void
    {
        $dir = sys_get_temp_dir() . '/pv-updates-' . uniqid();
        @mkdir($dir, 0775, true);

        file_put_contents($dir . '/operation.lock', json_encode([
            'operation'  => 'update',
            'started_at' => date('c', time() - 3600),
            'pid'        => 1,
        ]));

        $reporter = new UpdateProgress($dir);

        self::assertTrue($reporter->acquireLock());
        $reporter->releaseLock();
    }

    public function testReadReturnsNullWhenNoProgressFileExists(): void
    {
        self::assertNull($this->reporter()->read());
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private string $dir;

    private function reporter(): UpdateProgress
    {
        $this->dir = sys_get_temp_dir() . '/pv-updates-' . uniqid();

        return new UpdateProgress($this->dir);
    }
}

/**
 * Test-local stand-in for the real phase list, keeping the progress test
 * independent of the apply service.
 */
final class PhasesStub
{
    /**
     * @return list<array{name: string, label: string}>
     */
    public static function phases(): array
    {
        return [
            ['name' => 'preflight', 'label' => 'Running preflight checks'],
            ['name' => 'backup',    'label' => 'Creating pre-update backup'],
            ['name' => 'download',  'label' => 'Downloading update'],
            ['name' => 'validate',  'label' => 'Validating downloaded zip'],
            ['name' => 'extract',   'label' => 'Extracting release'],
            ['name' => 'copy',      'label' => 'Copying release files'],
            ['name' => 'migrate',   'label' => 'Running database migrations'],
            ['name' => 'cleanup',   'label' => 'Cleaning up'],
        ];
    }
}
