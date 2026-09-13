<?php

declare(strict_types=1);

namespace Pubvana\Services;

/**
 * RateLimiter - Small per-key sliding-window limiter backed by cache files.
 *
 * One owner for public-facing spam gates (Forms, Comments). Each key keeps
 * a list of attempt timestamps inside its window; every read-modify-write
 * runs under an exclusive flock so two requests cannot both record the
 * same slot. State lives in writable/cache/ratelimit/ (gitignored), so
 * limits survive across sessions and survive a server restart, and
 * deliberately do NOT survive a wipe of the cache directory.
 *
 * Client IP is supplied by the caller (REMOTE_ADDR per the codebase
 * pattern; proxy headers are client-controlled and never consulted here).
 *
 * @package Pubvana\Services
 */
final class RateLimiter
{
    private string $cacheDir;

    /**
     * @param string|null $cacheDir Override the storage directory (tests)
     */
    public function __construct(?string $cacheDir = null)
    {
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2);
        $this->cacheDir = rtrim($cacheDir ?? ($root . '/writable/cache/ratelimit'), '/');
    }

    /**
     * Whether another attempt is still allowed for the key.
     *
     * Records nothing; pair with hit() when the operation succeeds so a
     * failed attempt (validation error, captcha miss) does not burn a slot.
     *
     * @param string $key           Stable key, e.g. "forms:form_3:1.2.3.4"
     * @param int    $maxAttempts   Attempts allowed per window
     * @param int    $windowSeconds Window length in seconds
     */
    public function check(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        if ($maxAttempts <= 0 || $windowSeconds <= 0) {
            return true;
        }

        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return true;
        }

        return count($this->recentTimestamps($path, $windowSeconds)) < $maxAttempts;
    }

    /**
     * Record one attempt for the key and prune the window.
     *
     * @param string $key           Stable key
     * @param int    $windowSeconds Window length in seconds
     */
    public function hit(string $key, int $windowSeconds): void
    {
        if ($windowSeconds <= 0) {
            return;
        }

        $path = $this->pathFor($key);
        $this->withLockedFile($path, function (array $timestamps) use ($windowSeconds): array {
            $timestamps[] = time();
            $cutoff = time() - $windowSeconds;
            return array_values(array_filter(
                $timestamps,
                static fn(int $ts): bool => $ts >= $cutoff
            ));
        });
    }

    /**
     * Drop all recorded attempts for the key.
     */
    public function clear(string $key): void
    {
        $path = $this->pathFor($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Read the timestamps still inside the window for the key.
     *
     * @return list<int>
     */
    private function recentTimestamps(string $path, int $windowSeconds): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $cutoff = time() - $windowSeconds;
        $recent = [];
        foreach ($decoded as $entry) {
            if (is_int($entry) && $entry >= $cutoff) {
                $recent[] = $entry;
            }
        }
        return $recent;
    }

    /**
     * Run a read-modify-write over the key's timestamp file under flock.
     *
     * @param callable(list<int>): list<int> $fn Receives the current
     *        timestamps, returns the ones to persist.
     */
    private function withLockedFile(string $path, callable $fn): void
    {
        if (!is_dir($this->cacheDir) && !@mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            return;
        }

        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return;
        }

        try {
            $raw = stream_get_contents($handle) ?: '';
            $decoded = json_decode($raw, true);
            $current = [];
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (is_int($entry)) {
                        $current[] = $entry;
                    }
                }
            }

            $next = $fn($current);
            ftruncate($handle, 0);
            rewind($handle);
            if ($next === []) {
                @unlink($path);
            } else {
                fwrite($handle, (string) json_encode($next));
                fflush($handle);
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Cache path for a key. Keys are opaque caller strings hashed to a
     * single safe filename.
     */
    private function pathFor(string $key): string
    {
        return $this->cacheDir . '/' . hash('sha256', $key) . '.json';
    }
}
