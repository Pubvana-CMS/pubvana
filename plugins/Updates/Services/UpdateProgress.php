<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\Services;

/**
 * File-based progress tracking with an exclusive lock for update operations.
 *
 * Writes one JSON progress file the admin UI polls via AJAX. Progress is
 * phase-based (a fixed checklist for one update run) with a free-form
 * detail line underneath the active phase for granular sub-status, such
 * as download bytes or per-directory copy counts.
 *
 * The lock lives in the same directory as the progress file and uses the
 * same 30-minute stale recovery as the Backups plugin.
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
final class UpdateProgress
{
    private const STALE_SECONDS = 1800;

    private const STATUS_PENDING = 'pending';
    private const STATUS_ACTIVE  = 'active';
    private const STATUS_DONE    = 'done';

    private string $lockFile;
    private string $progressFile;

    /** @var callable|null fn(string $label, string $detail): void */
    private $echo = null;

    /** @var list<array{name: string, label: string, status: string}> */
    private array $phases = [];

    /** Zero-based index of the active phase; -1 before start. */
    private int $activeIndex = -1;

    public function __construct(string $storageDir)
    {
        $dir = rtrim($storageDir, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
            @chmod($dir, 0775);
        }
        $this->lockFile     = $dir . '/operation.lock';
        $this->progressFile = $dir . '/update_progress.json';
    }

    /**
     * Register an echo callback invoked after every progress write.
     *
     * Used by the CLI commands to mirror file progress to the console:
     * callback receives (string $label, string $detail).
     *
     * @param callable|null $callback
     */
    public function onWrite(?callable $callback): void
    {
        $this->echo = $callback;
    }

    /**
     * Is an update operation currently locked in the given directory?
     */
    public static function isLockedInDir(string $storageDir): bool
    {
        return is_file(rtrim($storageDir, '/') . '/operation.lock');
    }

    /**
     * Acquire the exclusive lock. False when another operation holds it.
     */
    public function acquireLock(): bool
    {
        if (is_file($this->lockFile)) {
            $raw  = file_get_contents($this->lockFile);
            $lock = is_string($raw) ? json_decode($raw, true) : null;

            if (is_array($lock) && isset($lock['started_at']) && is_string($lock['started_at'])) {
                $started = strtotime($lock['started_at']);
                if ($started !== false && (time() - $started) > self::STALE_SECONDS) {
                    @unlink($this->lockFile);
                } else {
                    return false;
                }
            } else {
                // Unreadable lock file: treat as stale.
                @unlink($this->lockFile);
            }

            if (is_file($this->lockFile)) {
                return false;
            }
        }

        $payload = [
            'operation'  => 'update',
            'started_at' => date('c'),
            'pid'        => getmypid() ?: null,
        ];

        return file_put_contents($this->lockFile, json_encode($payload)) !== false;
    }

    /**
     * Release the lock.
     */
    public function releaseLock(): void
    {
        @unlink($this->lockFile);
    }

    /**
     * Begin a run with the full phase checklist.
     *
     * @param list<array{name: string, label: string}> $phases
     */
    public function start(array $phases): void
    {
        $this->phases      = array_map(
            static fn(array $phase): array => [
                'name'   => (string) $phase['name'],
                'label'  => (string) $phase['label'],
                'status' => self::STATUS_PENDING,
            ],
            $phases
        );
        $this->activeIndex = -1;

        $this->write('in_progress', 'Starting', '', null);
    }

    /**
     * Mark a phase active (1-based index).
     */
    public function beginPhase(int $index): void
    {
        if ($this->activeIndex >= 0 && isset($this->phases[$this->activeIndex])) {
            $this->phases[$this->activeIndex]['status'] = self::STATUS_DONE;
        }

        $key = $index - 1;
        if (!isset($this->phases[$key])) {
            return;
        }

        $this->activeIndex                     = $key;
        $this->phases[$key]['status']          = self::STATUS_ACTIVE;

        $this->write('in_progress', $this->phases[$key]['label'], '', null);
    }

    /**
     * Update the detail line under the active phase.
     *
     * Called repeatedly for granular sub-status (download bytes, copy
     * counts). Percent stays stable until the next phase begins.
     */
    public function detail(string $text): void
    {
        $label = $this->activeIndex >= 0
            ? $this->phases[$this->activeIndex]['label']
            : 'Working';

        $this->write('in_progress', $label, $text, null);
    }

    /**
     * Mark the whole run complete.
     *
     * @param array<string, mixed> $result Structured outcome for the UI
     */
    public function complete(array $result = []): void
    {
        if ($this->activeIndex >= 0 && isset($this->phases[$this->activeIndex])) {
            $this->phases[$this->activeIndex]['status'] = self::STATUS_DONE;
        }

        $this->activeIndex = count($this->phases);
        $this->write('completed', 'Update complete', '', null, $result);
    }

    /**
     * Mark the run failed.
     */
    public function error(string $message): void
    {
        $label = $this->activeIndex >= 0
            ? $this->phases[$this->activeIndex]['label']
            : 'Update';

        $this->write('error', $label, '', $message);
    }

    /**
     * Read the current progress payload.
     *
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        if (!is_file($this->progressFile)) {
            return null;
        }

        $raw  = file_get_contents($this->progressFile);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : null;
    }

    /**
     * Build and persist the progress payload.
     *
     * @param array<string, mixed>|null $result
     */
    private function write(string $status, string $label, string $detail, ?string $error, ?array $result = null): void
    {
        $total  = max(1, count($this->phases));
        $active = $this->phases[$this->activeIndex] ?? null;

        $percent = match (true) {
            $status === 'completed'                   => 100,
            $this->activeIndex < 0 || $active === null => 0,
            default                                    => min(99, (int) floor($this->activeIndex / $total * 100)),
        };

        $payload = [
            'operation'   => 'update',
            'status'      => $status,
            'phase'       => $active['name'] ?? null,
            'phase_label' => $label,
            'phase_index' => $this->activeIndex + 1,
            'phase_total' => count($this->phases),
            'phases'      => $this->phases,
            'detail'      => $detail,
            'percent'     => $percent,
            'started_at'  => $this->startedAt(),
            'error'       => $error,
            'result'      => $result,
        ];

        file_put_contents($this->progressFile, json_encode($payload), LOCK_EX);

        if ($this->echo !== null) {
            ($this->echo)($label, $detail);
        }
    }

    /**
     * Started-at timestamp from the lock file when present.
     */
    private function startedAt(): string
    {
        if (is_file($this->lockFile)) {
            $raw  = file_get_contents($this->lockFile);
            $lock = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($lock) && isset($lock['started_at']) && is_string($lock['started_at'])) {
                return $lock['started_at'];
            }
        }

        return date('c');
    }
}
