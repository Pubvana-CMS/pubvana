<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Seo;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Seo\Services\ContentAnalysisService;
use Pubvana\Tests\Support\TestCase;

/**
 * ContentAnalysisService scoring: pure function, no DB.
 */
#[CoversClass(ContentAnalysisService::class)]
final class ContentAnalysisServiceTest extends TestCase
{
    /** @return array<string, mixed> */
    private function goodData(): array
    {
        $body = str_repeat('Keyword appears here in a short sentence. ', 60);
        $content = '<p>Keyword starts this paragraph. ' . $body . '</p>'
            . '<h2>Keyword heading</h2>'
            . '<p>Second short paragraph with <a href="/a">one</a> and <a href="/b">two</a> links.</p>'
            . '<p><img src="/x.png" alt="Keyword image"></p>';

        return [
            'title' => 'Keyword leads this title of the right length here now',
            'content' => $content,
            'meta_title' => 'Keyword leads this meta title of the right length!',
            'meta_description' => 'Keyword opens this description which is long enough to pass the length check nicely.',
            'focus_keywords' => ['keyword'],
            'slug' => 'keyword-post',
            'has_images' => true,
            'image_alts' => ['Keyword image'],
        ];
    }

    public function testAnalyzeReturnsScoreAndFifteenChecks(): void
    {
        $result = (new ContentAnalysisService())->analyze($this->goodData());

        self::assertArrayHasKey('score', $result);
        self::assertArrayHasKey('checks', $result);
        self::assertCount(15, $result['checks']);
        self::assertGreaterThanOrEqual(0, $result['score']);
        self::assertLessThanOrEqual(100, $result['score']);
    }

    public function testGoodContentScoresHigh(): void
    {
        $result = (new ContentAnalysisService())->analyze($this->goodData());

        self::assertGreaterThanOrEqual(70, $result['score']);
    }

    public function testEmptyContentScoresLow(): void
    {
        $result = (new ContentAnalysisService())->analyze([]);

        self::assertLessThanOrEqual(20, $result['score']);
        $byId = [];
        foreach ($result['checks'] as $check) {
            $byId[$check['id']] = $check['status'];
        }
        self::assertSame('fail', $byId['title_length']);
        self::assertSame('fail', $byId['content_length']);
        self::assertSame('warning', $byId['title_keyword']);
    }

    public function testTitleLengthBranches(): void
    {
        $service = new ContentAnalysisService();

        self::assertSame('pass', $this->invoke($service, 'checkTitleLength', [str_repeat('t', 55)])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkTitleLength', [str_repeat('t', 40)])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkTitleLength', [''])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkTitleLength', [str_repeat('t', 100)])['status']);
    }

    public function testTitleKeywordBranches(): void
    {
        $service = new ContentAnalysisService();

        self::assertSame('warning', $this->invoke($service, 'checkTitleKeyword', ['Some title', ''])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkTitleKeyword', ['Keyword first here', 'keyword'])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkTitleKeyword', ['A very long title ending with keyword', 'keyword'])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkTitleKeyword', ['No match here', 'keyword'])['status']);
    }

    public function testDescriptionBranches(): void
    {
        $service = new ContentAnalysisService();

        self::assertSame('pass', $this->invoke($service, 'checkDescriptionLength', [str_repeat('d', 140)])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkDescriptionLength', [str_repeat('d', 50)])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkDescriptionLength', [str_repeat('d', 200)])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkDescriptionLength', [''])['status']);

        self::assertSame('warning', $this->invoke($service, 'checkDescriptionKeyword', ['desc', ''])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkDescriptionKeyword', ['', 'kw'])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkDescriptionKeyword', ['has kw here', 'kw'])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkDescriptionKeyword', ['no match', 'kw'])['status']);
    }

    public function testContentLengthBranches(): void
    {
        $service = new ContentAnalysisService();

        self::assertSame('pass', $this->invoke($service, 'checkContentLength', [1200])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkContentLength', [500])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkContentLength', [50])['status']);
    }

    public function testFirstParagraphAndDensity(): void
    {
        $service = new ContentAnalysisService();

        self::assertSame('warning', $this->invoke($service, 'checkKeywordInFirstParagraph', ['<p>hi</p>', ''])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkKeywordInFirstParagraph', ['<p>Keyword here</p>', 'keyword'])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkKeywordInFirstParagraph', ['<p>nothing</p>', 'keyword'])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkKeywordInFirstParagraph', ['plain keyword text here', 'keyword'])['status']);

        self::assertSame('warning', $this->invoke($service, 'checkKeywordDensity', ['text', '', 10])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkKeywordDensity', ['text', 'kw', 0])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkKeywordDensity', ['nothing here at all', 'kw', 10])['status']);
    }

    public function testHeadingsLinksSlug(): void
    {
        $service = new ContentAnalysisService();

        self::assertSame('pass', $this->invoke($service, 'checkHeadingsPresent', ['<h2>x</h2>'])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkHeadingsPresent', ['<p>x</p>'])['status']);

        self::assertSame('warning', $this->invoke($service, 'checkKeywordInHeading', ['<h2>x</h2>', ''])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkKeywordInHeading', ['<h2>Keyword here</h2>', 'keyword'])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkKeywordInHeading', ['<h2>other</h2>', 'keyword'])['status']);

        self::assertSame('pass', $this->invoke($service, 'checkInternalLinks', ['<a href="/a">1</a><a href="/b">2</a>'])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkInternalLinks', ['<a href="/a">1</a>'])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkInternalLinks', ['no links'])['status']);

        self::assertSame('warning', $this->invoke($service, 'checkSlugKeyword', ['slug', ''])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkSlugKeyword', ['', 'kw'])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkSlugKeyword', ['my-keyword-post', 'keyword'])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkSlugKeyword', ['other-slug', 'keyword'])['status']);
    }

    public function testImagesAndReadability(): void
    {
        $service = new ContentAnalysisService();

        self::assertSame('pass', $this->invoke($service, 'checkImagesPresent', [true])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkImagesPresent', [false])['status']);

        self::assertSame('warning', $this->invoke($service, 'checkImageAlts', [[], 'kw'])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkImageAlts', [['Keyword alt'], 'keyword'])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkImageAlts', [['plain alt'], 'keyword'])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkImageAlts', [[''], 'keyword'])['status']);

        self::assertSame('warning', $this->invoke($service, 'checkParagraphLength', ['no paragraphs'])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkParagraphLength', ['<p>short</p>'])['status']);
        self::assertSame('warning', $this->invoke($service, 'checkParagraphLength', ['<p>' . str_repeat('word ', 200) . '</p>'])['status']);

        self::assertSame('warning', $this->invoke($service, 'checkSentenceLength', [''])['status']);
        self::assertSame('pass', $this->invoke($service, 'checkSentenceLength', ['Short. Also short.'])['status']);
        self::assertSame('fail', $this->invoke($service, 'checkSentenceLength', [str_repeat('word ', 30) . '. ' . str_repeat('word ', 30) . '.'])['status']);
    }
}
