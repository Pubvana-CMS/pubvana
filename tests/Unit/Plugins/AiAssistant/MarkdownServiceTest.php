<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\AiAssistant;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\AiAssistant\Services\MarkdownService;
use Pubvana\Tests\Support\TestCase;

/**
 * MarkdownService conversion and sanitization.
 */
#[CoversClass(MarkdownService::class)]
final class MarkdownServiceTest extends TestCase
{
    public function testToHtmlRendersMarkdown(): void
    {
        $service = new MarkdownService();

        $html = $service->toHtml('# Hello');

        self::assertStringContainsString('<h1>', $html);
        self::assertStringContainsString('Hello', $html);
    }

    public function testToHtmlStripsRawHtmlAndUnsafeLinks(): void
    {
        $service = new MarkdownService();

        $html = $service->toHtml('<script>alert(1)</script> [x](javascript:alert(1))');

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }

    public function testToMarkdownConvertsHtml(): void
    {
        $service = new MarkdownService();

        $markdown = $service->toMarkdown('<h1>Hello</h1><p>World</p>');

        self::assertStringContainsString('Hello', $markdown);
        self::assertStringContainsString('World', $markdown);
    }

    public function testToMarkdownTrims(): void
    {
        $service = new MarkdownService();

        self::assertSame('', $service->toMarkdown(''));
    }
}
