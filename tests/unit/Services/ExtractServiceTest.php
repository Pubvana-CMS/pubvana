<?php

namespace Tests\Unit\Services;

use App\Services\ExtractService;
use CodeIgniter\Test\CIUnitTestCase;

class ExtractServiceTest extends CIUnitTestCase
{
    public function testDetectsAvailableMethods(): void
    {
        $service = new ExtractService();
        $methods = $service->getAvailableMethods();
        $this->assertIsArray($methods);
        $this->assertNotEmpty($methods); // ZipArchive should be available
    }

    public function testExtractReturnsFalseForNonexistentFile(): void
    {
        $service = new ExtractService();
        $result = $service->extract('/nonexistent/file.zip', WRITEPATH . 'updates/test/');
        $this->assertFalse($result);
    }

    public function testRejectsPathTraversal(): void
    {
        // Create a temp zip with a path traversal entry
        $zipPath = WRITEPATH . 'updates/test_traversal.zip';
        $dir = dirname($zipPath);
        if (! is_dir($dir)) mkdir($dir, 0755, true);

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('../../../etc/evil.txt', 'pwned');
        $zip->close();

        $service = new ExtractService();
        $result = $service->extract($zipPath, WRITEPATH . 'updates/test_out/');
        $this->assertFalse($result);

        @unlink($zipPath);
    }
}
