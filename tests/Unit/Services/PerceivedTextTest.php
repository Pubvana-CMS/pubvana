<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PerceivedText;
use Pubvana\Tests\Support\TestCase;

/**
 * PerceivedText: the searchable text a visitor can perceive from HTML.
 */
#[CoversClass(PerceivedText::class)]
final class PerceivedTextTest extends TestCase
{
    public function testKeepsVisibleTextAndDropsMarkup(): void
    {
        $text = PerceivedText::fromHtml('<p>See <a href="/docs" class="link">the docs</a></p>');

        self::assertSame('See the docs', $text);
    }

    /**
     * An escaped sample is text the visitor reads, so `href` stays searchable
     * even though it sits between angle brackets.
     */
    public function testKeepsEscapedMarkupAsVisibleText(): void
    {
        $text = PerceivedText::fromHtml('<p>Use &lt;a href="x"&gt; here</p>');

        self::assertStringContainsString('<a href="x">', $text);
    }

    public function testReadsAltTitleAndAriaLabel(): void
    {
        $text = PerceivedText::fromHtml(
            '<img src="cat.jpg" alt="A grey cat">'
            . '<a href="/x" title="Read more">link</a>'
            . '<button aria-label="Close dialog">X</button>'
        );

        self::assertStringContainsString('A grey cat', $text);
        self::assertStringContainsString('Read more', $text);
        self::assertStringContainsString('Close dialog', $text);
    }

    public function testIgnoresOtherAttributes(): void
    {
        $text = PerceivedText::fromHtml('<span class="secret-class" data-x="data-value" style="color: red">hi</span>');

        self::assertSame('hi', $text);
    }

    public function testContainsIsCaseInsensitiveAndSkipsEmptyTerm(): void
    {
        self::assertTrue(PerceivedText::contains('Cat', 'A grey cat'));
        self::assertTrue(PerceivedText::contains('cat', 'a grey cat'));
        self::assertFalse(PerceivedText::contains('cat', 'a grey dog'));
        self::assertFalse(PerceivedText::contains('', 'a grey cat'));
    }

    public function testEmptyHtmlIsEmptyText(): void
    {
        self::assertSame('', PerceivedText::fromHtml(''));
        self::assertSame('', PerceivedText::fromHtml('<p></p>'));
    }
}
