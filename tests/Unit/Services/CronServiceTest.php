<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\CronService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Tests\Support\TestCase;
use RuntimeException;

/**
 * CronService runs adext-registered cron tasks for an interval. A real
 * ExtensionRegistry behind a mapped adext service drives the whole
 * surface; log file and lock directory point at a temp dir so tests
 * never touch writable/.
 */
#[CoversClass(CronService::class)]
final class CronServiceTest extends TestCase
{
    /** Temporary directory holding the test log file and lock files. */
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/pubvana-cron-test-' . uniqid();
        mkdir($this->tmpDir . '/logs', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/logs/*') ?: [] as $file) {
            @unlink($file);
        }
        foreach (glob($this->tmpDir . '/cron-*.lock') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir . '/logs');
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    /**
     * Build a CronService backed by a real registry, with the log file
     * and lock directory redirected into the temp dir.
     */
    private function makeService(ExtensionRegistry $registry): CronService
    {
        $app = $this->app([
            'adext' => fn (): ExtensionRegistry => $registry,
        ]);

        return new CronService(
            $app,
            $this->tmpDir . '/logs/cron.log',
            $this->tmpDir
        );
    }

    /**
     * Simple mutable holder so closures can record what happened.
     */
    private function holder(): \stdClass
    {
        $holder = new \stdClass();
        $holder->calls = [];

        return $holder;
    }

    public function testUnknownIntervalReturnsError(): void
    {
        $service = $this->makeService(new ExtensionRegistry());

        self::assertSame(CronService::EXIT_ERROR, $service->run('12h'));
    }

    public function testRunsOnlyTasksForTheRequestedInterval(): void
    {
        $registry = new ExtensionRegistry();
        $holder = $this->holder();
        $registry->register('cron', '1m', 'pubvana.minute', [
            'callable' => fn () => $holder->calls[] = 'minute',
        ]);
        $registry->register('cron', '24h', 'pubvana.daily', [
            'callable' => fn () => $holder->calls[] = 'daily',
        ]);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_OK, $service->run('1m'));
        self::assertSame(['minute'], $holder->calls);
    }

    public function testRunsZeroTasksWhenIntervalHasNoRegistrations(): void
    {
        $service = $this->makeService(new ExtensionRegistry());

        self::assertSame(CronService::EXIT_OK, $service->run('4h'));
        self::assertFileDoesNotExist($this->tmpDir . '/logs/cron.log');
    }

    public function testRunsTasksInPriorityOrder(): void
    {
        $registry = new ExtensionRegistry();
        $holder = $this->holder();
        $registry->register('cron', '24h', 'pubvana.last', [
            'priority' => 90,
            'callable' => fn () => $holder->calls[] = 'last',
        ]);
        $registry->register('cron', '24h', 'pubvana.first', [
            'priority' => 10,
            'callable' => fn () => $holder->calls[] = 'first',
        ]);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_OK, $service->run('24h'));
        self::assertSame(['first', 'last'], $holder->calls);
    }

    public function testFailingTaskDoesNotBlockLaterTasks(): void
    {
        $registry = new ExtensionRegistry();
        $holder = $this->holder();
        $registry->register('cron', '1m', 'pubvana.broken', [
            'priority' => 10,
            'callable' => fn (): never => throw new RuntimeException('boom'),
        ]);
        $registry->register('cron', '1m', 'pubvana.works', [
            'priority' => 20,
            'callable' => fn () => $holder->calls[] = 'works',
        ]);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_TASK_FAILED, $service->run('1m'));
        self::assertSame(['works'], $holder->calls);
    }

    public function testNonCallableRegistrationCountsAsFailure(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('cron', '4h', 'pubvana.bad', [
            'callable' => 'no-such-function-anywhere',
        ]);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_TASK_FAILED, $service->run('4h'));
    }

    public function testHeldLockSkipsTheRun(): void
    {
        $registry = new ExtensionRegistry();
        $holder = $this->holder();
        $registry->register('cron', '1m', 'pubvana.minute', [
            'callable' => fn () => $holder->calls[] = 'minute',
        ]);

        // Simulate a still-running previous process holding the lock.
        $handle = fopen($this->tmpDir . '/cron-1m.lock', 'c');
        self::assertNotFalse($handle);
        self::assertTrue(flock($handle, LOCK_EX | LOCK_NB));

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_SKIPPED, $service->run('1m'));
        self::assertSame([], $holder->calls);

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    public function testLockIsReleasedAfterRun(): void
    {
        $service = $this->makeService(new ExtensionRegistry());

        self::assertSame(CronService::EXIT_OK, $service->run('1m'));

        // A second immediate run must not be skipped: the first run's
        // lock was released.
        self::assertSame(CronService::EXIT_OK, $service->run('1m'));
    }

    public function testLogRecordsTaskOutcomes(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('cron', '24h', 'pubvana.good', [
            'callable' => fn (): null => null,
        ]);
        $registry->register('cron', '24h', 'pubvana.bad', [
            'priority' => 20,
            'callable' => fn (): never => throw new RuntimeException('boom'),
        ]);

        $service = $this->makeService($registry);
        $service->run('24h');

        $log = (string) file_get_contents($this->tmpDir . '/logs/cron.log');
        self::assertStringContainsString('[24h] task pubvana.good ok', $log);
        self::assertStringContainsString('[24h] task pubvana.bad FAILED: RuntimeException: boom', $log);
    }

    public function testSkippedRunIsLogged(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('cron', '1m', 'pubvana.minute', [
            'callable' => fn (): null => null,
        ]);

        $handle = fopen($this->tmpDir . '/cron-1m.lock', 'c');
        self::assertNotFalse($handle);
        flock($handle, LOCK_EX | LOCK_NB);

        $service = $this->makeService($registry);
        $service->run('1m');

        $log = (string) file_get_contents($this->tmpDir . '/logs/cron.log');
        self::assertStringContainsString('[1m] run skipped: previous run still holds the lock', $log);

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    public function testRunResultReceivesOkOutcome(): void
    {
        $registry = new ExtensionRegistry();
        $captured = [];
        $registry->register('cron', '1m', 'pubvana.minute', [
            'callable'   => fn (): null => null,
            'run_result' => function (array $result) use (&$captured): void {
                $captured = $result;
            },
        ]);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_OK, $service->run('1m'));
        self::assertSame('1m', $captured['interval']);
        self::assertSame('pubvana.minute', $captured['task']);
        self::assertSame(CronService::EXIT_OK, $captured['exit_code']);
        self::assertSame('ok', $captured['status']);
        self::assertNull($captured['error']);
        self::assertIsFloat($captured['duration']);
    }

    public function testRunResultReceivesFailureWithThrowable(): void
    {
        $registry = new ExtensionRegistry();
        $captured = [];
        $boom = new RuntimeException('boom');
        $registry->register('cron', '1m', 'pubvana.broken', [
            'callable'   => fn (): never => throw $boom,
            'run_result' => function (array $result) use (&$captured): void {
                $captured = $result;
            },
        ]);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_TASK_FAILED, $service->run('1m'));
        self::assertSame(CronService::EXIT_TASK_FAILED, $captured['exit_code']);
        self::assertSame('failed', $captured['status']);
        self::assertSame($boom, $captured['error']);
    }

    public function testRunResultFiresWhenRunSkips(): void
    {
        $registry = new ExtensionRegistry();
        $captured = [];
        $registry->register('cron', '1m', 'pubvana.minute', [
            'callable'   => fn (): null => null,
            'run_result' => function (array $result) use (&$captured): void {
                $captured = $result;
            },
        ]);

        $handle = fopen($this->tmpDir . '/cron-1m.lock', 'c');
        self::assertNotFalse($handle);
        flock($handle, LOCK_EX | LOCK_NB);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_SKIPPED, $service->run('1m'));
        self::assertSame(CronService::EXIT_SKIPPED, $captured['exit_code']);
        self::assertSame('skipped', $captured['status']);
        self::assertNull($captured['error']);

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    public function testRunResultIsOptionalAndIgnoredWhenMissing(): void
    {
        $service = $this->makeService(new ExtensionRegistry());

        self::assertSame(CronService::EXIT_OK, $service->run('1m'));
    }

    public function testThrowingRunResultDoesNotChangeExitCode(): void
    {
        $registry = new ExtensionRegistry();
        $registry->register('cron', '1m', 'pubvana.minute', [
            'callable'   => fn (): null => null,
            'run_result' => fn (array $result): never => throw new RuntimeException('result handler boom'),
        ]);

        $service = $this->makeService($registry);

        self::assertSame(CronService::EXIT_OK, $service->run('1m'));
    }
}
