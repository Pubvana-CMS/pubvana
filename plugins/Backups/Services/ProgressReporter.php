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
     */
    public function acquireLock(): bool
    {
        if (is_file($this->lockFile)) {
            $raw = file_get_contents($this->lockFile);
            $lock = is_string($raw) ? json_decode($raw, true) : null;

            // Stale lock (> 30 minutes)
            if (is_array($lock) && isset($lock['started_at'])) {
                $age = time() - strtotime($lock['started_at']);
                if ($age > 1800) {
                    @unlink($this->lockFile);
                } else {
                    return false;
                }
            }

            if (is_file($this->lockFile)) {
                return false;
            }
        }

        $data = [
            'operation'  => $this->operation,
            'started_at' => date('c'),
            'pid'        => getmypid() ?: null,
        ];

        file_put_contents($this->lockFile, json_encode($data));
        return true;
    }

    /**
     * Release the lock.
     */
    public function releaseLock(): void
    {
        @unlink($this->lockFile);
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
     * Check if a lock is currently held.
     */
    public static function isLocked(string $storageDir): bool
    {
        return is_file(rtrim($storageDir, '/') . '/operation.lock');
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