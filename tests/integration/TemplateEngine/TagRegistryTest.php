<?php

namespace Tests\Integration\TemplateEngine;

use App\Libraries\TemplateEngine\TagRegistry;
use CodeIgniter\Test\CIUnitTestCase;

class TagRegistryTest extends CIUnitTestCase
{
    private TagRegistry $tags;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tags = new TagRegistry();
    }

    public function testLangReturnsLocalizedString(): void
    {
        $result = $this->tags->call('lang', ['Blog.home']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testBaseUrlReturnsUrl(): void
    {
        $result = $this->tags->call('base_url', ['test/path']);
        $this->assertStringContainsString('test/path', $result);
    }

    public function testThemeUrlReturnsUrl(): void
    {
        $result = $this->tags->call('theme_url', ['css/theme.css']);
        $this->assertStringContainsString('css/theme.css', $result);
    }

    public function testPostUrlReturnsUrl(): void
    {
        $result = $this->tags->call('post_url', ['my-post']);
        $this->assertStringContainsString('my-post', $result);
    }

    public function testCategoryUrlReturnsUrl(): void
    {
        $result = $this->tags->call('category_url', ['tech']);
        $this->assertStringContainsString('tech', $result);
    }

    public function testTagUrlReturnsUrl(): void
    {
        $result = $this->tags->call('tag_url', ['php']);
        $this->assertStringContainsString('php', $result);
    }

    public function testUnknownTagReturnsEmpty(): void
    {
        $this->assertSame('', $this->tags->call('nonexistent', ['arg']));
    }
}
