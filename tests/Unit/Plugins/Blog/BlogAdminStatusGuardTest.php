<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use PHPUnit\Framework\TestCase;

/**
 * Source guard for AUDIT L4: the blog admin create and edit paths must
 * validate status against the draft/published/scheduled allowlist, so an
 * arbitrary status string can never be stored.
 */
final class BlogAdminStatusGuardTest extends TestCase
{
    private string $source = '';

    protected function setUp(): void
    {
        $file = dirname(__DIR__, 4) . '/plugins/Blog/Controllers/BlogAdminController.php';
        self::assertFileExists($file);
        $this->source = (string) file_get_contents($file);
    }

    public function testStoreAndUpdateValidateStatus(): void
    {
        // Three sites: the index() query filter plus the store() and
        // update() write paths.
        self::assertSame(
            3,
            substr_count($this->source, "!in_array(\$status, ['draft', 'published', 'scheduled'], true)"),
        );
    }

    public function testStoreRejectsBackToCreate(): void
    {
        self::assertMatchesRegularExpression(
            "/Invalid status\.'\);\s*\n\s*\\\$this->app->redirect\(\\\$this->adminBase\(\) \. '\/create'\);/s",
            $this->source,
        );
    }

    public function testUpdateRejectsBackToEdit(): void
    {
        self::assertMatchesRegularExpression(
            "/Invalid status\.'\);\s*\n\s*\\\$this->app->redirect\(\\\$this->adminBase\(\) \. '\/' \. \\\$id \. '\/edit'\);/s",
            $this->source,
        );
    }
}
