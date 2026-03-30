<?php

namespace App\Services;

class ProgressReporter
{
    protected string $operation;
    protected string $updatesDir;
    protected string $lockFile;
    protected string $progressFile;

    public function __construct(string $operation)
    {
        $this->operation    = $operation;
        $this->updatesDir   = WRITEPATH . 'updates/';
        $this->lockFile     = $this->updatesDir . 'operation.lock';
        $this->progressFile = $this->updatesDir . $operation . '_progress.json';

        if (! is_dir($this->updatesDir)) {
            mkdir($this->updatesDir, 0755, true);
        }
    }

    /**
     * Acquire an exclusive lock. Returns false if another operation is running.
     */
    public function acquireLock(): bool
    {
        if (is_file($this->lockFile)) {
            $lock = json_decode(file_get_contents($this->lockFile), true);

            // Check for stale lock (> 30 minutes)
            if (isset($lock['started_at'])) {
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
     */
    public function read(): ?array
    {
        if (! is_file($this->progressFile)) {
            return null;
        }
        return json_decode(file_get_contents($this->progressFile), true);
    }

    /**
     * Get the progress file path (for SSE endpoints).
     */
    public function getProgressFile(): string
    {
        return $this->progressFile;
    }

    /**
     * Check if a lock is currently held.
     */
    public static function isLocked(): bool
    {
        return is_file(WRITEPATH . 'updates/operation.lock');
    }

    private function getStartedAt(): string
    {
        if (is_file($this->lockFile)) {
            $lock = json_decode(file_get_contents($this->lockFile), true);
            return $lock['started_at'] ?? date('c');
        }
        return date('c');
    }
}
