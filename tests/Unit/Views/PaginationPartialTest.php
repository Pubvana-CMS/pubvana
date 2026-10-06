<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Pubvana\Services\PaginationService;

/**
 * The shared admin pagination partial.
 *
 * Fed real PaginationService output so the two stay paired, plus one
 * hand-built array to pin the escaping.
 */
final class PaginationPartialTest extends TestCase
{
    private const PARTIAL = PROJECT_ROOT . '/app/Views/admin/_pagination.php';

    /**
     * @param array<string, mixed>|null $pagination
     */
    private function render(?array $pagination): string
    {
        ob_start();
        include self::PARTIAL;

        return (string) ob_get_clean();
    }

    /**
     * @return array<string, mixed>
     */
    private function page(int $current, int $total, int $perPage = 20): array
    {
        $pagination = (new PaginationService())->build(
            $current,
            $total,
            $perPage,
            static fn(int $n): string => '/admin/page?page=' . $n
        );
        self::assertNotNull($pagination);

        return $pagination;
    }

    public function testNothingToPageRendersNothing(): void
    {
        self::assertSame('', $this->render(null));
    }

    public function testFirstPageDisablesPrevious(): void
    {
        $html = $this->render($this->page(1, 500));

        self::assertStringContainsString(
            '<li class="page-item disabled"><span class="page-link">Previous</span></li>',
            $html
        );
        self::assertStringContainsString('<a class="page-link" href="/admin/page?page=2">Next</a>', $html);
    }

    public function testLastPageDisablesNext(): void
    {
        $html = $this->render($this->page(25, 500));

        self::assertStringContainsString(
            '<li class="page-item disabled"><span class="page-link">Next</span></li>',
            $html
        );
        self::assertStringContainsString('<a class="page-link" href="/admin/page?page=24">Previous</a>', $html);
    }

    public function testCollapsedRunRendersAsADisabledEllipsis(): void
    {
        $html = $this->render($this->page(12, 500));

        self::assertStringContainsString(
            '<li class="page-item disabled"><span class="page-link">...</span></li>',
            $html
        );
    }

    public function testCurrentPageCarriesTheActiveClass(): void
    {
        $html = $this->render($this->page(2, 60));

        self::assertStringContainsString('<li class="page-item active">', $html);
    }

    public function testUrlsAreEscaped(): void
    {
        $html = $this->render([
            'current'  => 1,
            'total'    => 2,
            'prev_url' => null,
            'next_url' => '/admin/page?page=2&sort="x"',
            'pages'    => [
                ['number' => 1, 'url' => '/admin/page?page=1&sort="x"', 'active' => true, 'gap' => false],
            ],
        ]);

        self::assertStringContainsString(
            'href="/admin/page?page=1&amp;sort=&quot;x&quot;"',
            $html
        );
        self::assertStringContainsString(
            'href="/admin/page?page=2&amp;sort=&quot;x&quot;"',
            $html
        );
        self::assertStringNotContainsString('sort="x"', $html);
    }
}
