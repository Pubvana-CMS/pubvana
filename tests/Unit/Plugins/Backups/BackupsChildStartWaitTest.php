<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Backups\Controllers\BackupsAdminController;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Tests\Support\TestCase;

use function file_put_contents;
use function mkdir;
use function sys_get_temp_dir;
use function time;
use function touch;
use function uniqid;

/**
 * BackupsAdminController waits for a backgrounded child to identify itself.
 *
 * Without this wait a child that dies on startup is answered as "started"
 * and the admin polls a progress file that never appears.
 *
 * @package Pubvana\Tests\Unit\Plugins\Backups
 */
#[CoversClass(BackupsAdminController::class)]
final class BackupsChildStartWaitTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/pv-childstart-' . uniqid('', true);
        @mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function testAFreshProgressFileCountsAsStarted(): void
    {
        $launchedAt = time();
        file_put_contents($this->dir . '/backup_progress.json', '{"status":"in_progress"}');

        self::assertTrue($this->waited('backup', $launchedAt));
    }

    public function testAFreshLockFileCountsAsStarted(): void
    {
        $launchedAt = time();
        file_put_contents($this->dir . '/operation.lock', '{"operation":"backup"}');

        self::assertTrue($this->waited('backup', $launchedAt));
    }

    public function testAFileLeftByAnEarlierRunDoesNotCount(): void
    {
        file_put_contents($this->dir . '/backup_progress.json', '{"status":"completed"}');
        touch($this->dir . '/backup_progress.json', time() - 600);

        self::assertFalse($this->waited('backup', time()));
    }

    private function waited(string $operation, int $launchedAt): bool
    {
        return (bool) $this->invoke($this->controller(), 'waitForChildStart', [$operation, $launchedAt]);
    }

    private function controller(): BackupsAdminController
    {
        $app = new Engine();
        $app->set('pubvana.backups', []);
        $app->map('backups', fn (): BackupService => new BackupService(
            new \PDO('sqlite::memory:'),
            ['backup_path' => $this->dir],
            ['host' => '', 'port' => 0, 'dbname' => '', 'user' => '', 'password' => ''],
        ));

        return new BackupsAdminController($app);
    }
}
