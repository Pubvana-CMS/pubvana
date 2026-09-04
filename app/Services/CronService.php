<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;
use Throwable;

/**
 * CronService - Runs plugin-registered tasks on fixed intervals.
 *
 * The root `cron` script boots the full application (autoloader plus
 * services.php, so plugins load and register their tasks through adext)
 * and hands one interval to run(). System crontabs call that script on
 * three schedules: every minute, every 4 hours, and every 24 hours.
 * There are no public routes and no runway commands; the script only
 * runs from the command line and is unreachable from the web (the
 * docroot is public/).
 *
 * Registration (from a plugin's Plugin.php register()):
 *   $app->adext()->register('cron', '1m', 'pubvana.blog', [
 *       'label'    => 'Ping feeds',
 *       'callable' => fn() => $this->pingFeeds(),
 *   ]);
 *
 * Each run:
 *   - Skips silently (exit 0) when a previous run of the same interval
 *     still holds its lock, so overlapping crontab hits never stack up.
 *   - Runs tasks in priority order (lowest first), each guarded by
 *     try/catch: one failing task never blocks the rest.
 *   - Appends one line per task to writable/logs/cron.log.
 *
 * Lock files live in writable/cache/ (gitignored, like the logs).
 *
 * Exit codes: 0 ran fine (or skipped via lock), 1 bad interval, 2 the
 * run happened but at least one task failed. The `cron` script exits
 * with whatever run() returns.
 *
 * @package Pubvana\Services
 */
class CronService
{
    /**
     * Valid intervals, matching the adext 'cron' slots.
     *
     * @var array<int, string>
     */
    public const INTERVALS = ['1m', '4h', '24h'];

    /** Everything ran, or the run was skipped because the lock was held. */
    public const EXIT_OK = 0;

    /** Bad interval, nothing ran. */
    public const EXIT_ERROR = 1;

    /** The run happened but at least one task failed. */
    public const EXIT_TASK_FAILED = 2;

    /** @var Engine<object> */
    private Engine $app;

    /** Path of the append-only run log. */
    private string $logFile;

    /** Directory holding the per-interval lock files. */
    private string $lockDir;

    /**
     * @param Engine<object> $app
     * @param string|null    $logFile Override the run log path (tests)
     * @param string|null    $lockDir Override the lock directory (tests)
     */
    public function __construct(Engine $app, ?string $logFile = null, ?string $lockDir = null)
    {
        $this->app = $app;
        $writable = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'writable';
        $this->logFile = $logFile ?? $writable . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'cron.log';
        $this->lockDir = $lockDir ?? $writable . DIRECTORY_SEPARATOR . 'cache';
    }

    /**
     * Run every task registered for an interval.
     *
     * @param string $interval One of CronService::INTERVALS
     * @return int Exit code: EXIT_OK, EXIT_ERROR, or EXIT_TASK_FAILED
     */
    public function run(string $interval): int
    {
        if (!in_array($interval, self::INTERVALS, true)) {
            error_log(
                'CronService: unknown interval "' . $interval . '". Allowed: '
                . implode(', ', self::INTERVALS)
            );
            return self::EXIT_ERROR;
        }

        $lock = $this->acquireLock($interval);
        if ($lock === null) {
            $this->log($interval, 'run skipped: previous run still holds the lock');
            return self::EXIT_OK;
        }

        try {
            return $this->runTasks($interval);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Execute the interval's tasks, isolating failures per task.
     *
     * @param string $interval The validated interval
     * @return int EXIT_OK or EXIT_TASK_FAILED
     */
    private function runTasks(string $interval): int
    {
        $tasks = $this->app->adext()->get('cron', $interval);
        $failed = 0;

        foreach ($tasks as $key => $task) {
            $callable = $task['callable'] ?? null;
            if (!is_callable($callable)) {
                $failed++;
                $this->log($interval, 'task ' . $key . ' FAILED: registered callable is not callable');
                continue;
            }

            $started = hrtime(true);
            try {
                call_user_func($callable);
                $elapsed = number_format((hrtime(true) - $started) / 1e9, 3, '.', '');
                $this->log($interval, 'task ' . $key . ' ok (' . $elapsed . 's)');
            } catch (Throwable $e) {
                $failed++;
                $this->log(
                    $interval,
                    'task ' . $key . ' FAILED: ' . get_class($e) . ': ' . $e->getMessage()
                    . ' in ' . $e->getFile() . ':' . $e->getLine()
                );
            }
        }

        return $failed > 0 ? self::EXIT_TASK_FAILED : self::EXIT_OK;
    }

    /**
     * Take the per-interval lock without blocking.
     *
     * Returns the open file handle (still holding the lock) or null when
     * the lock is held elsewhere or cannot be opened. Null means the run
     * should skip rather than risk stacked executions.
     *
     * @param string $interval The validated interval
     * @return resource|null
     */
    private function acquireLock(string $interval)
    {
        if (!is_dir($this->lockDir)) {
            @mkdir($this->lockDir, 0775, true);
        }

        $handle = @fopen($this->lockDir . DIRECTORY_SEPARATOR . 'cron-' . $interval . '.lock', 'c');
        if ($handle === false) {
            $this->log($interval, 'run skipped: cannot open lock file');
            return null;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }

        return $handle;
    }

    /**
     * Append one line to the run log.
     *
     * @param string $interval The interval being run
     * @param string $message  Task or run detail
     * @return void
     */
    private function log(string $interval, string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] [' . $interval . '] ' . $message . PHP_EOL;
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
