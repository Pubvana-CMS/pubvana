<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PaginationService;
use Pubvana\Tests\Support\TestCase;

/**
 * PaginationService windowing.
 *
 * Both the public theme partial and the admin partial render whatever this
 * returns, so the shape and the window rule are pinned here once instead of
 * being re-asserted per caller.
 */
#[CoversClass(PaginationService::class)]
final class PaginationServiceTest extends TestCase
{
    private PaginationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaginationService();
    }

    /**
     * @return array{current: int, total: int, prev_url: string|null, next_url: string|null, pages: list<array{number: int|string, url: string, active: bool, gap: bool}>}
     */
    private function build(int $current, int $total, int $perPage = 20): array
    {
        $pagination = $this->service->build(
            $current,
            $total,
            $perPage,
            static fn(int $n): string => '/p/' . $n
        );
        self::assertNotNull($pagination);

        return $pagination;
    }

    /**
     * The numbers as rendered, with each collapsed run as a single '...'.
     *
     * @param array{pages: list<array{number: int|string}>} $pagination
     *
     * @return list<int|string>
     */
    private function numbers(array $pagination): array
    {
        return array_values(array_column($pagination['pages'], 'number'));
    }

    public function testSinglePageBuildsNothing(): void
    {
        self::assertNull($this->service->build(1, 20, 20, static fn(int $n): string => '/p/' . $n));
        self::assertNull($this->service->build(1, 5, 20, static fn(int $n): string => '/p/' . $n));
        self::assertNull($this->service->build(1, 0, 20, static fn(int $n): string => '/p/' . $n));
    }

    public function testPageCountRoundsUp(): void
    {
        self::assertSame(2, $this->build(1, 21)['total']);
        self::assertSame(3, $this->build(1, 41)['total']);
        self::assertSame(25, $this->build(1, 500)['total']);
    }

    public function testZeroPerPageIsFlooredToOneRatherThanDividingByZero(): void
    {
        self::assertSame(25, $this->build(1, 25, 0)['total']);
    }

    public function testFirstPageWindowsForwardOnly(): void
    {
        $pagination = $this->build(1, 500);

        self::assertSame([1, 2, 3, '...', 25], $this->numbers($pagination));
        self::assertNull($pagination['prev_url']);
        self::assertSame('/p/2', $pagination['next_url']);
    }

    public function testMiddlePageWindowsBothSides(): void
    {
        $pagination = $this->build(12, 500);

        self::assertSame([1, '...', 10, 11, 12, 13, 14, '...', 25], $this->numbers($pagination));
        self::assertSame('/p/11', $pagination['prev_url']);
        self::assertSame('/p/13', $pagination['next_url']);
    }

    public function testLastPageWindowsBackwardOnly(): void
    {
        $pagination = $this->build(25, 500);

        self::assertSame([1, '...', 23, 24, 25], $this->numbers($pagination));
        self::assertSame('/p/24', $pagination['prev_url']);
        self::assertNull($pagination['next_url']);
    }

    public function testUrlsComeFromTheCallable(): void
    {
        $pagination = $this->build(12, 500);

        foreach ($pagination['pages'] as $item) {
            if ($item['gap']) {
                // A gap item is inert: the partial renders it as text and
                // never links it, so it carries no URL.
                self::assertSame('', $item['url']);
                continue;
            }

            self::assertSame('/p/' . $item['number'], $item['url']);
        }
    }

    public function testActiveFlagMarksOnlyTheCurrentPage(): void
    {
        $pagination = $this->build(12, 500);

        $active = [];
        foreach ($pagination['pages'] as $item) {
            if ($item['active']) {
                $active[] = $item['number'];
            }
        }

        self::assertSame([12], $active);
        self::assertSame(12, $pagination['current']);
    }
}
