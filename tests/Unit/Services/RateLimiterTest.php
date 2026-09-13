<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\RateLimiter;
use Pubvana\Tests\Support\TestCase;

/**
 * RateLimiter behaviour over a temp cache directory.
 *
 * Covers the sliding-window rules: allowance before the cap, denial at the
 * cap, expiry of entries outside the window, independence between keys,
 * disabled windows, and clear().
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(RateLimiter::class)]
final class RateLimiterTest extends TestCase
{
    private string $cacheDir;
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheDir = sys_get_temp_dir() . '/pv-ratelimit-' . uniqid('', true);
        $this->limiter = new RateLimiter($this->cacheDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
        parent::tearDown();
    }

    public function testFirstAttemptIsAllowed(): void
    {
        self::assertTrue($this->limiter->check('k', 1, 60));
    }

    public function testDeniesOnceTheCapIsReached(): void
    {
        self::assertTrue($this->limiter->check('k', 1, 60));
        $this->limiter->hit('k', 60);

        self::assertFalse($this->limiter->check('k', 1, 60));
    }

    public function testAllowsUpToMaxAttempts(): void
    {
        $this->limiter->hit('k', 60);
        self::assertTrue($this->limiter->check('k', 2, 60));
        $this->limiter->hit('k', 60);

        self::assertFalse($this->limiter->check('k', 2, 60));
    }

    public function testEntriesOutsideTheWindowExpire(): void
    {
        $this->limiter->hit('k', 60);
        // Age every recorded timestamp past the window.
        $path = $this->cacheDir . '/' . hash('sha256', 'k') . '.json';
        $raw = is_file($path) ? (string) file_get_contents($path) : '';
        $timestamps = json_decode($raw, true);
        self::assertIsArray($timestamps);
        file_put_contents($path, json_encode(array_map(static fn($ts) => $ts - 61, $timestamps)));

        self::assertTrue($this->limiter->check('k', 1, 60));
    }

    public function testKeysAreIndependent(): void
    {
        $this->limiter->hit('forms:1:1.1.1.1', 60);
        $this->limiter->hit('forms:1:2.2.2.2', 60);

        self::assertFalse($this->limiter->check('forms:1:1.1.1.1', 1, 60));
        self::assertFalse($this->limiter->check('forms:1:2.2.2.2', 1, 60));
        self::assertTrue($this->limiter->check('forms:1:3.3.3.3', 1, 60));
    }

    public function testADisabledWindowNeverLimits(): void
    {
        self::assertTrue($this->limiter->check('k', 1, 0));
        $this->limiter->hit('k', 0);

        self::assertTrue($this->limiter->check('k', 1, 0));
        self::assertFalse(is_file($this->cacheDir . '/' . hash('sha256', 'k') . '.json'), 'a disabled window records nothing');
    }

    public function testClearResetsTheKey(): void
    {
        $this->limiter->hit('k', 60);
        self::assertFalse($this->limiter->check('k', 1, 60));

        $this->limiter->clear('k');
        self::assertTrue($this->limiter->check('k', 1, 60));
    }

    public function testOnlyRecentEntriesAreKeptInTheFile(): void
    {
        $this->limiter->hit('k', 60);
        $this->limiter->hit('k', 60);
        $this->limiter->hit('k', 60);

        $path = $this->cacheDir . '/' . hash('sha256', 'k') . '.json';
        $timestamps = json_decode((string) file_get_contents($path), true);
        self::assertIsArray($timestamps);
        self::assertCount(3, $timestamps);
        foreach ($timestamps as $ts) {
            self::assertGreaterThanOrEqual(time() - 60, $ts);
        }
    }
}
