<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use PHPUnit\Framework\TestCase;

/**
 * Source guard for AUDIT M18: the page update() path must validate the
 * title exactly like store() does, so an edit can never blank a page title.
 */
final class PagesUpdateTitleGuardTest extends TestCase
{
    private string $source = '';

    protected function setUp(): void
    {
        $file = dirname(__DIR__, 4) . '/plugins/Pages/Controllers/PagesAdminController.php';
        self::assertFileExists($file);
        $this->source = (string) file_get_contents($file);
    }

    public function testUpdateValidatesTitleLikeStore(): void
    {
        // Both create and edit paths carry the same required-title check.
        self::assertSame(2, substr_count($this->source, "trim(\$post['title'] ?? '') === ''"));

        // The update() check redirects back to the edit form, not the index.
        self::assertMatchesRegularExpression(
            "/public function update\(string \\\$id\): void.*?Title is required\.'\);\s*\n\s*\\\$this->app->redirect\(\\\$this->adminBase\(\) \. '\/' \. \\\$id \. '\/edit'\);/s",
            $this->source,
        );
    }

    public function testStoreRedirectsBackToCreate(): void
    {
        self::assertMatchesRegularExpression(
            "/Title is required\.'\);\s*\n\s*\\\$this->app->redirect\(\\\$this->adminBase\(\) \. '\/create'\);/s",
            $this->source,
        );
    }
}
