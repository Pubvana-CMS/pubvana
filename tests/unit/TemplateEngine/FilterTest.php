<?php

namespace Tests\Unit\TemplateEngine;

use App\Libraries\TemplateEngine\FilterRegistry;
use CodeIgniter\Test\CIUnitTestCase;

class FilterTest extends CIUnitTestCase
{
    private FilterRegistry $filters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filters = new FilterRegistry();
    }

    public function testDate(): void
    {
        $result = $this->filters->apply('date', '2026-03-26 14:30:00', ['F j, Y']);
        $this->assertSame('March 26, 2026', $result);
    }

    public function testNumberFormat(): void
    {
        $this->assertSame('1,234', $this->filters->apply('number_format', 1234));
    }

    public function testNl2br(): void
    {
        $this->assertStringContainsString('<br', $this->filters->apply('nl2br', "line1\nline2"));
    }

    public function testMd5(): void
    {
        $this->assertSame(md5('test'), $this->filters->apply('md5', 'test'));
    }

    public function testCount(): void
    {
        $this->assertSame(3, $this->filters->apply('count', [1, 2, 3]));
    }

    public function testExcerpt(): void
    {
        $long = str_repeat('word ', 50);
        $result = $this->filters->apply('excerpt', $long, [20]);
        $this->assertLessThanOrEqual(25, strlen($result)); // 20 + possible ellipsis
    }

    public function testDefault(): void
    {
        $this->assertSame('fallback', $this->filters->apply('default', '', ['fallback']));
        $this->assertSame('fallback', $this->filters->apply('default', null, ['fallback']));
        $this->assertSame('value', $this->filters->apply('default', 'value', ['fallback']));
    }

    public function testRaw(): void
    {
        $this->assertSame('<b>bold</b>', $this->filters->apply('raw', '<b>bold</b>'));
    }

    public function testStrtolower(): void
    {
        $this->assertSame('hello', $this->filters->apply('strtolower', 'HELLO'));
    }

    public function testStripTags(): void
    {
        $this->assertSame('text', $this->filters->apply('strip_tags', '<p>text</p>'));
    }

    public function testUnknownFilterReturnsInput(): void
    {
        $this->assertSame('input', $this->filters->apply('nonexistent', 'input'));
    }
}
