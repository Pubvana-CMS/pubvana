<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Backups\Services;

/**
 * File-based progress tracking with an exclusive lock for long-running operations.
 *
 * Writes JSON progress files so the admin UI can poll via AJAX.
 * A single lock file prevents concurrent backup/restore operations.
 */
class ProgressReporter
{
    protected string $operation;
    protected string $storageDir;
    protected string $lockFile;
    protected string $progressFile;

    /**
     * Open file descriptor holding the flock, null when unlocked.
     *
     * @var resource|null
     */
    protected $lockHandle = null;

    public function __construct(string $operation, string $storageDir)
    {
        $this->operation    = $operation;
        $this->storageDir   = rtrim($storageDir, '/') . '/';
        $this->lockFile     = $this->storageDir . 'operation.lock';
        $this->progressFile = $this->storageDir . $operation . '_progress.json';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0775, true);
        }
        @chmod($this->storageDir, 0775);
    }

    /**
     * Acquire an exclusive lock. Returns false if another operation is running.
     *
     * The flock is the authority: a crashed holder's OS lock is released
     * automatically, so the next acquire always succeeds (a stale
     * operation.lock payload on disk is simply overwritten). No
     * check-then-write race exists because the lock is taken on the open
     * file descriptor, not on file existence.
     */
    public function acquireLock(): bool
    {
        if ($this->lockHandle !== null) {
            return false; // this reporter already holds the lock
        }

        $handle = @fopen($this->lockFile, 'c+');
        if ($handle === false) {
            return false;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        $data = [
            'operation'  => $this->operation,
            'started_at' => date('c'),
            'pid'        => getmypid() ?: null,
        ];

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode($data));
        fflush($handle);

        $this->lockHandle = $handle;

        // Overwrite any progress left by an earlier run, so the admin poll
        // never reads a stale 'completed' as the current operation.
        $this->update(0, 0, 'Starting...');

        return true;
    }

    /**
     * Release the lock.
     *
     * The lock file itself is left on disk. Unlinking it after the flock is
     * released is a race: another process can open the file between the
     * unlock and the unlink, hold a lock on a deleted inode, and a third
     * process then locks a fresh file, leaving two holders.
     */
    public function releaseLock(): void
    {
        if ($this->lockHandle === null) {
            return;
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }

    /**
     * Write a progress update.
     */
    public function update(int $stepsCompleted, int $stepsTotal, string $stepLabel, string $detail = ''): void
    {
        $data = [
            'operation'       => $this->operation,
            'status'          => 'in_progress',
            'step_label'      => $stepLabel,
            'steps_completed' => $stepsCompleted,
            'steps_total'     => $stepsTotal,
            'detail'          => $detail,
            'started_at'      => $this->getStartedAt(),
            'error'           => null,
        ];

        file_put_contents($this->progressFile, json_encode($data));
    }

    /**
     * Mark operation as completed.
     *
     * @param array<string, mixed> $extra
     */
    public function complete(array $extra = []): void
    {
        $data = array_merge([
            'operation'       => $this->operation,
            'status'          => 'completed',
            'step_label'      => 'Complete',
            'steps_completed' => 0,
            'steps_total'     => 0,
            'detail'          => '',
            'started_at'      => $this->getStartedAt(),
            'error'           => null,
        ], $extra);

        file_put_contents($this->progressFile, json_encode($data));
    }

    /**
     * Mark operation as failed.
     */
    public function error(string $step, string $message): void
    {
        $data = [
            'operation'       => $this->operation,
            'status'          => 'error',
            'step_label'      => $step,
            'steps_completed' => 0,
            'steps_total'     => 0,
            'detail'          => '',
            'started_at'      => $this->getStartedAt(),
            'error'           => $message,
        ];

        file_put_contents($this->progressFile, json_encode($data));
    }

    /**
     * Read the current progress data.
     *
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        if (!is_file($this->progressFile)) {
            return null;
        }
        $raw = file_get_contents($this->progressFile);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($data) ? $data : null;
    }

    /**
     * The operation name of the live lock holder, or null when unlocked.
     *
     * acquireLock() resets the holder's progress file in the same breath it
     * takes the lock, so when this returns a name that file is already the
     * current run and never a stale record from a previous one.
     */
    public static function lockedOperation(string $storageDir): ?string
    {
        if (!self::isLocked($storageDir)) {
            return null;
        }

        $file = rtrim($storageDir, '/') . '/operation.lock';
        $raw  = @file_get_contents($file);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        if (is_array($data) && isset($data['operation']) && is_string($data['operation'])) {
            return $data['operation'];
        }

        return null;
    }

    /**
     * Read the most recently written progress file in a storage dir.
     *
     * The admin polls one endpoint for backup and restore alike; the newest
     * file is the operation that last reported.
     *
     * @return array<string, mixed>|null
     */
    public static function readLatest(string $storageDir): ?array
    {
        $files = glob(rtrim($storageDir, '/') . '/*_progress.json') ?: [];
        if ($files === []) {
            return null;
        }

        usort($files, static fn (string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));

        foreach ($files as $file) {
            $raw  = @file_get_contents($file);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Check if a lock is currently held by a live process.
     *
     * Existence alone would report a crashed run forever; a leftover file
     * whose flock is free (dead holder) counts as unlocked.
     */
    public static function isLocked(string $storageDir): bool
    {
        $file = rtrim($storageDir, '/') . '/operation.lock';
        if (!is_file($file)) {
            return false;
        }

        $handle = @fopen($file, 'r+');
        if ($handle === false) {
            return true;
        }
        $held = !flock($handle, LOCK_EX | LOCK_NB);
        flock($handle, LOCK_UN);
        fclose($handle);
        return $held;
    }

    public function __destruct()
    {
        if ($this->lockHandle !== null) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    private function getStartedAt(): string
    {
        if (is_file($this->lockFile)) {
            $raw = file_get_contents($this->lockFile);
            $lock = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($lock) && isset($lock['started_at']) && is_string($lock['started_at'])) {
                return $lock['started_at'];
            }
        }
        return date('c');
    }
}