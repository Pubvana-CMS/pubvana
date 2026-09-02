<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Search\Controllers;

use Pubvana\Controllers\Public\PublicController;

/**
 * SearchPublicController - Public-facing /search?q=term results page.
 */
class SearchPublicController extends PublicController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.search');
    }

    /**
     * Render search results for the current query.
     */
    public function search(): void
    {
        $query = trim((string) ($this->app->request()->query->q ?? ''));
        $page  = max(1, (int) ($this->app->request()->query->page ?? 1));

        $data = [
            'title'   => 'Search',
            'query'   => $query,
            'results' => [],
            'total'   => 0,
            'error'   => null,
            'from'    => '',
        ];

        if ($query !== '') {
            $result = $this->app->search()->search($query, $page);

            $data['results']    = $result['items'];
            $data['total']      = $result['total'];
            $data['error']      = $result['error'];
            $data['from']       = $result['from'];
            $data['pagination'] = $this->buildPagination($result);
        }

        $this->render('search', $data);
    }

    /**
     * Build pagination data (Blog-style) for the results template.
     */
    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>|null
     */
    private function buildPagination(array $result): ?array
    {
        $page    = (int) ($result['page'] ?? 1);
        $perPage = (int) ($result['per_page'] ?? 10);
        $total   = (int) ($result['total'] ?? 0);
        $pages   = (int) ceil($total / max($perPage, 1));

        if ($pages <= 1) {
            return null;
        }

        $query = urlencode((string) ($result['query'] ?? ''));

        return [
            'current' => $page,
            'total'   => $pages,
            'prev'    => $page > 1 ? '/search?q=' . $query . '&page=' . ($page - 1) : null,
            'next'    => $page < $pages ? '/search?q=' . $query . '&page=' . ($page + 1) : null,
        ];
    }
}
