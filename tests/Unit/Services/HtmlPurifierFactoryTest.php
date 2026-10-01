<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Pubvana\Services\HtmlPurifierFactory;

/**
 * The application's purifier config keeps the link and media markup the
 * editor writes, and still drops scripts, event handlers, and unlisted URL
 * schemes.
 */
#[CoversClass(HtmlPurifierFactory::class)]
final class HtmlPurifierFactoryTest extends TestCase
{
    private function purify(string $html): string
    {
        return (new \HTMLPurifier(HtmlPurifierFactory::create()))->purify($html);
    }

    public function testKeepsLinkTargetAndRel(): void
    {
        $out = $this->purify(
            '<p><a href="https://example.com" target="_blank" rel="noopener noreferrer">new tab</a>'
            . '<a href="/local" target="_self">same tab</a>'
            . '<a href="https://example.com" rel="nofollow">nofollow</a></p>'
        );

        self::assertStringContainsString('target="_blank"', $out);
        self::assertStringContainsString('target="_self"', $out);
        self::assertStringContainsString('noreferrer', $out);
        self::assertStringContainsString('noopener', $out);
        self::assertStringContainsString('nofollow', $out);
    }

    public function testDropsFrameTargetOutsideTheAllowedSet(): void
    {
        $out = $this->purify('<p><a href="https://example.com" target="_elsewhere">x</a></p>');

        self::assertStringContainsString('href="https://example.com"', $out);
        self::assertStringNotContainsString('target=', $out);
    }

    public function testKeepsVideoSourceAndFigure(): void
    {
        $out = $this->purify(
            '<video controls poster="/storage/p.jpg" src="/storage/a.mp4" class="rounded"></video>'
            . '<video controls width="640"><source src="/a.mp4" type="video/mp4"></video>'
            . '<figure class="figure"><img src="/a.jpg" alt="x"><figcaption>cap</figcaption></figure>'
        );

        self::assertStringContainsString('controls', $out);
        self::assertStringContainsString('poster="/storage/p.jpg"', $out);
        self::assertStringContainsString('src="/storage/a.mp4"', $out);
        self::assertStringContainsString('<source src="/a.mp4" type="video/mp4">', $out);
        self::assertStringContainsString('<figure class="figure">', $out);
        self::assertStringContainsString('<figcaption>cap</figcaption>', $out);
    }

    public function testKeepsListedIframeEmbeds(): void
    {
        $out = $this->purify(
            '<iframe width="560" height="315" src="https://www.youtube.com/embed/abc" title="v"></iframe>'
        );

        self::assertStringContainsString('src="https://www.youtube.com/embed/abc"', $out);
    }

    public function testDropsSrcFromUnlistedIframeHosts(): void
    {
        $out = $this->purify('<iframe src="https://evil.example.com/x"></iframe>');

        self::assertStringNotContainsString('evil.example.com', $out);
    }

    public function testDropsScriptsHandlersAndJavascriptUrls(): void
    {
        $out = $this->purify(
            '<p>ok</p><script>alert(1)</script>'
            . '<img src="/a.jpg" onerror="alert(1)">'
            . '<a href="javascript:alert(1)">bad</a>'
        );

        self::assertStringNotContainsString('<script>', $out);
        self::assertStringNotContainsString('onerror', $out);
        self::assertStringNotContainsString('javascript:', $out);
    }
}
