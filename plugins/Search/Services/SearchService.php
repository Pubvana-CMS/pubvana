<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Search\Services;

use flight\Engine;

/**
 * SearchService - runs a site-wide search across registered content sources.
 *
 * Content sources (Blog, Pages, future Commerce, ...) register via adext
 * type 'search' slot 'provider'. A provider's callable receives the raw
 * query string and returns NORMALIZED CONTENT MATCHES - it supplies
 * content, not ranking. SearchService owns all scoring:
 *
 *   - tokenizes the query (lowercase, phrase support)
 *   - scores every match uniformly (title > excerpt > content)
 *   - applies a small recency boost
 *   - merges, sorts, paginates, and highlights matched terms
 *
 * Admins can enable/disable sources in the admin UI; disabled sources are
 * excluded from aggregation.
 */
class SearchService
{
    /** @var Engine<object> */
    protected Engine $app;

    /**
     * @param Engine<object> $app
    */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Run a search across all enabled sources.
     *
     * @param string $term Raw query
     * @param int    $page 1-based page
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, query: string, error: ?string, from: string}
     */
    public function search(string $term, int $page = 1): array
    {
        $term = trim($term);
        $perPage = (int) $this->setting('resultsPerPage', 10);
        $minLength = (int) $this->setting('minQueryLength', 3);

        if (mb_strlen($term) < $minLength) {
            return [
                'items'    => [],
                'total'    => 0,
                'page'     => $page,
                'per_page' => $perPage,
                'query'    => $term,
                'error'    => 'Please enter at least ' . $minLength . ' characters.',
                'from'     => '',
            ];
        }

        $sources = $this->enabledSources(false);

        if (empty($sources)) {
            return [
                'items'    => [],
                'total'    => 0,
                'page'     => $page,
                'per_page' => $perPage,
                'query'    => $term,
                'error'    => 'No search sources are enabled. An administrator needs to enable at least one.',
                'from'     => '',
            ];
        }

        $allItems = [];
        $words    = $this->tokenize($term);

        foreach ($sources as $key => $source) {
            $callable = $source['callable'] ?? null;
            if (!is_callable($callable)) {
                continue;
            }

            try {
                $results = $callable($term);
            } catch (\Throwable $e) {
                continue;
            }

            if (!is_array($results)) {
                continue;
            }

            foreach ($results as $item) {
                if (!is_array($item) || empty($item['title']) || empty($item['url'])) {
                    continue;
                }

                $item['_source'] = $key;
                $item['_score']  = $this->scoreItem($item, $words);
                $allItems[] = $item;
            }
        }

        // Sort by score desc, then recency desc as tiebreaker
        usort($allItems, function (array $a, array $b): int {
            $score = $b['_score'] <=> $a['_score'];
            if ($score !== 0) {
                return $score;
            }
            return strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? ''));
        });

        $total  = count($allItems);
        $offset = ($page - 1) * $perPage;
        $items  = array_slice($allItems, $offset, $perPage);

        // Highlight matched terms in the visible slice only
        $items = array_map(fn(array $item) => $this->highlight($item, $words), $items);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'query'    => $term,
            'error'    => null,
            'from'     => $this->sourceLabel($allItems),
        ];
    }

    /**
     * All registered search sources (undelegated, callables intact).
     *
     * @return array<string, array<string, mixed>> Keyed by source key
     */
    public function sources(): array
    {
        return $this->app->adext()->get('search', 'provider') ?: [];
    }

    /**
     * Sources that contribute to search, in display order.
     *
     * @param bool $decorate When true, attach 'enabled' + 'label' to each
     * @return array<string, array<string, mixed>>
     */
    public function enabledSources(bool $decorate = true): array
    {
        $sources  = $this->sources();
        $disabled = $this->disabledSourceKeys();

        $out = [];
        foreach ($sources as $key => $source) {
            $on = !in_array($key, $disabled, true);
            if ($decorate) {
                $source['enabled'] = $on;
                $out[$key] = $source;
            } elseif ($on) {
                $out[$key] = $source;
            }
        }
        return $out;
    }

    /**
     * Toggle a source's participation in search.
     */
    public function setSourceEnabled(string $key, bool $enabled): void
    {
        $disabled = $this->disabledSourceKeys();

        if ($enabled) {
            $index = array_search($key, $disabled, true);
            if ($index !== false) {
                unset($disabled[$index]);
                $disabled = array_values($disabled);
            }
        } else {
            if (!in_array($key, $disabled, true)) {
                $disabled[] = $key;
            }
        }

        $this->app->settings()->set('Search.disabledSources', json_encode($disabled));
    }

    /**
     * Keys of sources currently disabled by an admin.
     *
     * @return string[]
     */
    protected function disabledSourceKeys(): array
    {
        $raw = (string) $this->setting('disabledSources', '[]');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    /**
     * Read a Search setting, defaulting to a sensible value.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->app->settings()->get('Search.' . $key, $default);
    }

    /**
     * Split a query into lowercase tokens, honoring quoted phrases.
     *
     * @return string[]
     */
    protected function tokenize(string $term): array
    {
        $tokens = [];
        if (preg_match_all('/"([^"]+)"|\S+/u', $term, $matches)) {
            foreach ($matches[0] as $raw) {
                $t = mb_strtolower(trim($raw, " \t\n\r\0\x0B\""));
                if ($t !== '') {
                    $tokens[] = $t;
                }
            }
        }
        return array_values(array_unique($tokens));
    }

    /**
     * Score a single content item uniformly against the query tokens.
     *
     * @param array<string, mixed> $item  Provider-supplied content match
     * @param string[] $words Tokenized (phrases + words) query
     * @return float
     */
    protected function scoreItem(array $item, array $words): float
    {
        $title    = mb_strtolower((string) ($item['title'] ?? ''));
        $excerpt  = mb_strtolower((string) ($item['excerpt'] ?? ''));
        $content  = mb_strtolower(strip_tags((string) ($item['content'] ?? '')));
        $score    = 0.0;

        $single = [];
        foreach ($words as $w) {
            if (str_contains($w, ' ')) {
                // Phrase: strong match on title/excerpt
                if ($title !== '' && str_contains($title, $w)) {
                    $score += 20;
                }
                if ($excerpt !== '' && str_contains($excerpt, $w)) {
                    $score += 12;
                }
                if ($content !== '' && str_contains($content, $w)) {
                    $score += 8;
                }
            } else {
                $single[] = $w;
            }
        }

        foreach ($single as $w) {
            // Title
            if ($title !== '' && str_starts_with($title, $w)) {
                $score += 12;
            } elseif ($title !== '' && str_contains($title, $w)) {
                $score += 8;
            } elseif (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $title)) {
                $score += 10;
            }

            // Excerpt
            if ($excerpt !== '' && str_contains($excerpt, $w)) {
                $score += 5;
            }

            // Content
            if ($content !== '' && str_contains($content, $w)) {
                $score += 3;
            }
        }

        // Recency boost: up to +4 for recent items
        $ageDays = $this->ageDays((string) ($item['published_at'] ?? ''));
        if ($ageDays !== null) {
            $score += max(0, 4 - (int) floor($ageDays / 30));
        }

        return round($score, 2);
    }

    /**
     * Number of days since a published timestamp, or null if unavailable.
     */
    protected function ageDays(string $date): ?int
    {
        if ($date === '') {
            return null;
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return null;
        }
        return (int) floor((time() - $ts) / 86400);
    }

    /**
     * Wrap matched tokens in <mark> tags on title and excerpt.
     *
     * Escapes HTML first, then injects <mark> around case-insensitive matches.
     */
    /**
     * @param array<string, mixed> $item
     * @param string[] $words
     * @return array<string, mixed>
     */
    protected function highlight(array $item, array $words): array
    {
        $wordMap = [];
        foreach ($words as $w) {
            $wordMap[$w] = htmlspecialchars($w, ENT_QUOTES, 'UTF-8');
        }

        foreach (['title', 'excerpt'] as $field) {
            if (empty($item[$field])) {
                continue;
            }
            $text = htmlspecialchars((string) $item[$field], ENT_QUOTES, 'UTF-8');
            foreach ($wordMap as $regex => $escaped) {
                $quoted = preg_quote($escaped, '/');
                $text = preg_replace('/(' . $quoted . ')/iu', '<mark>$1</mark>', $text) ?? $text;
            }
            $item[$field] = $text;
        }

        return $item;
    }

    /**
     * Human-friendly list of contributing source labels (for a "Results from" line).
     */
    /**
     * @param array<int, array<string, mixed>> $items
     */
    protected function sourceLabel(array $items): string
    {
        $labels = [];
        foreach ($items as $item) {
            if (!empty($item['_source']) && !in_array($item['_source'], $labels, true)) {
                $labels[] = (string) $item['_source'];
            }
        }
        return implode(', ', $labels);
    }
}
