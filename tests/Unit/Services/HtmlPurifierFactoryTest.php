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

    public function testKeepsRoleAndAriaAttributesOnAnyElement(): void
    {
        $out = $this->purify(
            '<div role="note" aria-label="Heads up" aria-live="polite">body</div>'
            . '<span aria-hidden="true">decorative</span>'
            . '<img src="/a.jpg" alt="" aria-describedby="caption-1">'
            . '<nav aria-labelledby="nav-heading" aria-current="page">menu</nav>'
        );

        self::assertStringContainsString('role="note"', $out);
        self::assertStringContainsString('aria-label="Heads up"', $out);
        self::assertStringContainsString('aria-live="polite"', $out);
        self::assertStringContainsString('aria-hidden="true"', $out);
        self::assertStringContainsString('aria-describedby="caption-1"', $out);
        self::assertStringContainsString('aria-current="page"', $out);
        self::assertStringContainsString('aria-labelledby="nav-heading"', $out);
    }

    public function testKeepsHtml5LandmarkElements(): void
    {
        $out = $this->purify(
            '<main><section aria-label="Intro"><article><header>H</header>'
            . '<aside>side</aside><footer>F</footer></article></section></main>'
            . '<details open><summary>More</summary><p>body</p></details>'
        );

        self::assertStringContainsString('<main>', $out);
        self::assertStringContainsString('<section aria-label="Intro">', $out);
        self::assertStringContainsString('<article>', $out);
        self::assertStringContainsString('<header>', $out);
        self::assertStringContainsString('<aside>', $out);
        self::assertStringContainsString('<footer>', $out);
        self::assertStringContainsString('<details open="">', $out);
        self::assertStringContainsString('<summary>', $out);
    }

    public function testKeepsTabindexIncludingNegativeAndZero(): void
    {
        $out = $this->purify(
            '<div tabindex="-1">skip me</div><div tabindex="0">focus me</div><div tabindex="3">third</div>'
        );

        self::assertStringContainsString('tabindex="-1"', $out);
        self::assertStringContainsString('tabindex="0"', $out);
        self::assertStringContainsString('tabindex="3"', $out);
    }

    public function testDropsTabindexThatIsNotAnInteger(): void
    {
        $out = $this->purify('<div tabindex="first">x</div>');

        self::assertStringNotContainsString('tabindex', $out);
    }

    public function testKeepsIdsSoAriaRelationshipsCanPointAtThem(): void
    {
        $out = $this->purify('<h2 id="section-1">Heading</h2><p aria-labelledby="section-1">Body</p>');

        self::assertStringContainsString('id="section-1"', $out);
        self::assertStringContainsString('aria-labelledby="section-1"', $out);
    }

    public function testKeepsOnlyTheFirstOfTwoIdenticalIds(): void
    {
        $out = $this->purify('<p id="dup">one</p><p id="dup">two</p>');

        self::assertSame(1, substr_count($out, 'id="dup"'));
    }

    public function testDropsAttributesOutsideTheDefinition(): void
    {
        $out = $this->purify('<p data-track="click" onclick="steal()">x</p>');

        self::assertStringNotContainsString('data-track', $out);
        self::assertStringNotContainsString('onclick', $out);
    }
}
