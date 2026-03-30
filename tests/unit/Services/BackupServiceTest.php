<?php

namespace Tests\Unit\Services;

use App\Services\BackupService;
use CodeIgniter\Test\CIUnitTestCase;

class BackupServiceTest extends CIUnitTestCase
{
    protected BackupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BackupService();
    }

    public function testBuildMetaReturnsCorrectStructure(): void
    {
        $meta = $this->service->buildMeta('manual', 'admin@example.com');

        $this->assertArrayHasKey('version', $meta);
        $this->assertArrayHasKey('date', $meta);
        $this->assertArrayHasKey('trigger', $meta);
        $this->assertArrayHasKey('triggered_by', $meta);
        $this->assertArrayHasKey('php_version', $meta);
        $this->assertArrayHasKey('ci_version', $meta);
        $this->assertSame('manual', $meta['trigger']);
        $this->assertSame('admin@example.com', $meta['triggered_by']);
        $this->assertSame(APP_VERSION, $meta['version']);
    }

    public function testDeleteBackupRejectsInvalidFilename(): void
    {
        $this->assertFalse($this->service->deleteBackup('../../../etc/passwd'));
        $this->assertFalse($this->service->deleteBackup('not-a-backup.zip'));
        $this->assertFalse($this->service->deleteBackup('backup-old-format.zip'));
    }

    public function testDeleteBackupAcceptsValidFilename(): void
    {
        // Valid format but file doesn't exist — returns false (no file to delete)
        $this->assertFalse($this->service->deleteBackup('2026-03-29_143022-full.zip'));
    }

    public function testGetBackupPathRejectsInvalidFilename(): void
    {
        $this->assertNull($this->service->getBackupPath('../../../etc/passwd'));
        $this->assertNull($this->service->getBackupPath('evil.zip'));
    }

    public function testListBackupsReturnsArray(): void
    {
        $result = $this->service->listBackups();
        $this->assertIsArray($result);
    }
}
