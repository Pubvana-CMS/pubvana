<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Plugins\Pages\Services\PagesService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * LIKE wildcard safety in the pages search provider.
 *
 * A user-supplied % or _ must be treated as literal text, not as a SQL
 * wildcard. Page::searchContent() runs the LIKE pre-filter with an explicit
 * ESCAPE clause and Page::escapeLikePattern() neutralizes the wildcard
 * characters before the pattern is built.
 *
 * The escape character is '!', not a backslash. MySQL reads a backslash
 * inside a string literal as an escaped quote, so ESCAPE '\' is a syntax
 * error there while SQLite accepts it. These tests run on SQLite, so they
 * cannot prove the clause is portable: the escape character must stay a
 * plain literal, and it must be escaped in the term, which
 * testBangInTermMatchesOnlyLiteralBang pins.
 */
#[CoversClass(Page::class)]
#[CoversClass(PagesService::class)]
final class PagesSearchWildcardTest extends TestCase
{
    private PDO $pdo;
    private PagesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        PagesSchema::create($this->pdo);
        $this->service = new PagesService($this->pdo, ['route_prefix' => '/page']);
    }

    private function publish(string $title, string $content): void
    {
        $this->service->createPage(['title' => $title, 'content' => $content, 'status' => 'published'], 1);
    }

    public function testPercentIsLiteralNotAWildcard(): void
    {
        $this->publish('Discounts', '<p>Save 100% today.</p>');
        $this->publish('Unrelated', '<p>Nothing to see.</p>');

        $results = $this->service->searchProvider('%');

        self::assertCount(1, $results);
        self::assertSame('Discounts', $results[0]['title']);
    }

    public function testUnderscoreIsLiteralNotAWildcard(): void
    {
        $this->publish('Snake Case', '<p>the_key value</p>');
        $this->publish('Unrelated', '<p>Nothing to see.</p>');

        $results = $this->service->searchProvider('_');

        self::assertCount(1, $results);
        self::assertSame('Snake Case', $results[0]['title']);
    }

    public function testBangInTermMatchesOnlyLiteralBang(): void
    {
        // '!' is the escape character, so it has to be doubled in the pattern
        // or the LIKE clause reads the following character as an escape.
        $this->publish('Exclaim', '<p>Wow! Indeed.</p>');
        $this->publish('Unrelated', '<p>Nothing to see.</p>');

        $results = $this->service->searchProvider('!');

        self::assertCount(1, $results);
        self::assertSame('Exclaim', $results[0]['title']);
    }

    public function testAPlainTermStillMatches(): void
    {
        $this->publish('About Us', '<p>hello</p>');

        $results = $this->service->searchProvider('about');

        self::assertCount(1, $results);
        self::assertSame('/page/about-us', $results[0]['url']);
    }

    public function testDraftsAndDeletedPagesAreStillExcluded(): void
    {
        $this->service->createPage(['title' => 'Drafty', 'content' => 'needle', 'status' => 'draft'], 1);
        $gone = $this->service->createPage(['title' => 'Gone', 'content' => 'needle', 'status' => 'published'], 1);
        $this->service->deletePage((int) $gone->id);

        self::assertSame([], $this->service->searchProvider('needle'));
    }
}
