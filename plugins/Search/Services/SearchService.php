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
 * The weights are fixed and deliberately simple, so a result order can be
 * explained. Per token: a quoted phrase pays 20/12/8 for title/excerpt/
 * content; a single word pays 12/10/8 in the title for a prefix, whole-word,
 * or inner-substring hit, then 5 in the excerpt and 3 in the content. Recency
 * adds up to +4, losing a point per 30 days of age.
 *
 * A provider that wants body scoring must return the stripped body as
 * `content`; a provider that omits it simply forfeits that tier.
 *
 * `maxScore()` derives the ceiling the weights allow for a given query, and
 * the envelope carries it as `max_score`, so a result can be read against the
 * best it could have scored instead of as a bare number.
 *
 * Admins can enable/disable sources in the admin UI; disabled sources are
 * excluded from aggregation.
 */
class SearchService
{
    /** @var Engine<object> */
    protected Engine $app;

    /**
     * Score weights. maxScore() derives the per-query ceiling from these, so a
     * change here moves the reported maximum with it.
     *
     * The title tiers are mutually exclusive, so only TITLE_PREFIX counts
     * toward the ceiling: an item cannot collect a prefix and a whole-word hit
     * from the same token.
     */
    private const PHRASE_TITLE    = 20;
    private const PHRASE_EXCERPT  = 12;
    private const PHRASE_CONTENT  = 8;
    private const TITLE_PREFIX    = 12;
    private const TITLE_WORD      = 10;
    private const TITLE_SUBSTRING = 8;
    private const EXCERPT_WORD    = 5;
    private const CONTENT_WORD    = 3;
    private const RECENCY_MAX     = 4;

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
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, query: string, error: ?string, from: string, max_score: float}
     */
    public function search(string $term, int $page = 1): array
    {
        $term = trim($term);
        $perPage = (int) $this->setting('resultsPerPage', 10);
        $minLength = (int) $this->setting('minQueryLength', 3);

        if (mb_strlen($term) < $minLength) {
            return [
                'items'     => [],
                'total'     => 0,
                'page'      => $page,
                'per_page'  => $perPage,
                'query'     => $term,
                'error'     => 'Please enter at least ' . $minLength . ' characters.',
                'from'      => '',
                // Nothing was scored, so there is no ceiling to report.
                'max_score' => 0.0,
            ];
        }

        $sources = $this->enabledSources(false);

        if (empty($sources)) {
            return [
                'items'     => [],
                'total'     => 0,
                'page'      => $page,
                'per_page'  => $perPage,
                'query'     => $term,
                'error'     => 'No search sources are enabled. An administrator needs to enable at least one.',
                'from'      => '',
                'max_score' => 0.0,
            ];
        }

        $allItems = [];
        $words    = $this->tokenize($term);

        foreach ($sources as $key => $source) {
            $callable = $source['callable'] ?? null;
            if (!is_callable($callable)) {
                error_log("SearchService: source '{$key}' has no callable - skipped");
                continue;
            }

            try {
                $results = $callable($term);
            } catch (\Throwable $e) {
                // A provider that throws contributes nothing, which renders as
                // an ordinary empty result set. Log it, or a broken source is
                // indistinguishable from a term that genuinely matched nothing.
                error_log("SearchService: source '{$key}' failed for term '{$term}' - " . $e->getMessage());
                continue;
            }

            if (!is_array($results)) {
                error_log(
                    "SearchService: source '{$key}' returned " . get_debug_type($results)
                    . ' instead of an array - skipped'
                );
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
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'query'     => $term,
            'error'     => null,
            'from'      => $this->sourceLabel($allItems),
            'max_score' => $this->maxScore($words),
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
                    $score += self::PHRASE_TITLE;
                }
                if ($excerpt !== '' && str_contains($excerpt, $w)) {
                    $score += self::PHRASE_EXCERPT;
                }
                if ($content !== '' && str_contains($content, $w)) {
                    $score += self::PHRASE_CONTENT;
                }
            } else {
                $single[] = $w;
            }
        }

        foreach ($single as $w) {
            // Title, most specific match first. A word-boundary match always
            // implies a substring match, so checking the substring first (as
            // this once did) made the whole-word tier unreachable.
            if ($title !== '' && str_starts_with($title, $w)) {
                $score += self::TITLE_PREFIX;
            } elseif (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $title) === 1) {
                $score += self::TITLE_WORD;
            } elseif ($title !== '' && str_contains($title, $w)) {
                $score += self::TITLE_SUBSTRING;
            }

            // Excerpt
            if ($excerpt !== '' && str_contains($excerpt, $w)) {
                $score += self::EXCERPT_WORD;
            }

            // Content
            if ($content !== '' && str_contains($content, $w)) {
                $score += self::CONTENT_WORD;
            }
        }

        // Recency boost: up to the maximum for recent items
        $ageDays = $this->ageDays((string) ($item['published_at'] ?? ''));
        if ($ageDays !== null) {
            $score += max(0, self::RECENCY_MAX - (int) floor($ageDays / 30));
        }

        return round($score, 2);
    }

    /**
     * Highest score any single item could reach for this query, so a result can
     * be read against the ceiling the weights allow rather than as a bare
     * number. Derived from the same consts as scoreItem(), never hand-summed.
     *
     * Recency counts at full value here, which means an item old enough to have
     * lost that boost cannot reach the ceiling. A query matching only part of an
     * item cannot either. Both are intended: this is the best case, not the
     * typical case.
     *
     * @param string[] $words Tokenized (phrases + words) query
     * @return float
     */
    protected function maxScore(array $words): float
    {
        $score = (float) self::RECENCY_MAX;

        foreach ($words as $w) {
            $score += str_contains($w, ' ')
                ? self::PHRASE_TITLE + self::PHRASE_EXCERPT + self::PHRASE_CONTENT
                : self::TITLE_PREFIX + self::EXCERPT_WORD + self::CONTENT_WORD;
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
