<?php

namespace Tests\Unit\Services;

use App\Services\DownloadService;
use CodeIgniter\Test\CIUnitTestCase;

class DownloadServiceTest extends CIUnitTestCase
{
    public function testDetectsAvailableMethods(): void
    {
        $service = new DownloadService();
        $methods = $service->getAvailableMethods();

        $this->assertIsArray($methods);
        // At minimum, one method should be available on a dev machine
        $this->assertNotEmpty($methods);
    }

    public function testDownloadReturnsNullForBadUrl(): void
    {
        $service = new DownloadService();
        $result = $service->download('https://example.invalid/nonexistent.zip', WRITEPATH . 'updates/test.zip');
        $this->assertFalse($result);
    }
}
