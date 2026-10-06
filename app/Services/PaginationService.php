<?php

declare(strict_types=1);

namespace Pubvana\Services;

/**
 * PaginationService - turns a total and a current page into a windowed
 * page list.
 *
 * One algorithm, two renderers. The public side renders the returned
 * array through themes/{theme}/Views/partials/pagination.tpl, the admin
 * side through app/Views/admin/_pagination.php. Callers pass the URL
 * shape in as a callable, because the public side pages by path
 * (/blog/page/3) and the admin side by query string (?page=3).
 *
 * @package Pubvana\Services
 */
class PaginationService
{
    /** Pages shown either side of the current one. */
    private const NEIGHBOURS = 2;

    /**
     * Build the page list, or null when there is one page or fewer.
     *
     * @param int                    $current Current page number (1-based)
     * @param int                    $total   Total items across every page
     * @param int                    $perPage Items per page
     * @param callable(int): string  $pageUrl Builds the URL for a page number
     *
     * @return array{current: int, total: int, prev_url: string|null, next_url: string|null, pages: list<array{number: int|string, url: string, active: bool, gap: bool}>}|null
     */
    public function build(int $current, int $total, int $perPage, callable $pageUrl): ?array
    {
        $pages = (int) ceil($total / max($perPage, 1));

        if ($pages <= 1) {
            return null;
        }

        // Show the current page plus two neighbours, and always the first and
        // last page. A jump between shown numbers becomes a gap item, so a
        // thousand-page archive does not emit a thousand links.
        $numbers = [1 => true, $pages => true];
        for ($n = max(1, $current - self::NEIGHBOURS); $n <= min($pages, $current + self::NEIGHBOURS); $n++) {
            $numbers[$n] = true;
        }
        $numbers = array_keys($numbers);
        sort($numbers);

        $items = [];
        $previous = 0;
        foreach ($numbers as $n) {
            if ($previous > 0 && $n - $previous > 1) {
                $items[] = [
                    'number' => '...',
                    'url'    => '',
                    'active' => false,
                    'gap'    => true,
                ];
            }
            $items[] = [
                'number' => $n,
                'url'    => $pageUrl($n),
                'active' => $n === $current,
                'gap'    => false,
            ];
            $previous = $n;
        }

        return [
            'current'  => $current,
            'total'    => $pages,
            'prev_url' => $current > 1 ? $pageUrl($current - 1) : null,
            'next_url' => $current < $pages ? $pageUrl($current + 1) : null,
            'pages'    => $items,
        ];
    }
}
