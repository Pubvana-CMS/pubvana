<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Models;

/**
 * Page - ActiveRecord model for the pages table.
 *
 * Represents a static page (About, Contact, Terms, etc.).
 * Pages are simple content containers with draft/published status and
 * soft-delete support.
 *
 * Schema:
 *   id                - Auto-increment primary key
 *   title             - Page title
 *   slug              - URL-safe slug (unique across the table, deleted rows included)
 *   content           - HTML content (sanitized on save)
 *   status            - 'draft' or 'published'
 *   ai_generated      - 1 when the body was drafted with AI assistance
 *   allow_comments    - 1 if comments are allowed on this page (per-page toggle)
 *   created_by        - User ID of creator
 *   created_at        - Creation timestamp
 *   updated_at        - Last update timestamp
 *   deleted_at        - Soft-delete timestamp
 *
 * @package Pubvana\Plugins\Pages\Models
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEqual(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field, string ...$fields)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 * @method self startWrap()
 * @method self endWrap(string $op)
 * @property int $cnt Aggregate alias from COUNT(*) selects
 */
class Page extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'pages', $config);
    }

    public int $id;
    public ?string $title = null;
    public ?string $slug = null;
    public ?string $content = null;
    public string $status = 'draft';
    public int $ai_generated = 0;
    public int $allow_comments = 0;
    public int $created_by = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    // -----------------------------------------------------------------
    // Finders
    // -----------------------------------------------------------------

    /**
     * Find a page by ID (excluding soft-deleted).
     *
     * @param int $id Page ID
     * @return self|null Page or null if not found
     */
    public function findById(int $id): ?self
    {
        // Fresh instance: reset() does not clear declared typed props, so a
        // miss on a reused instance would return stale data as hydrated.
        $query = new self($this->getDatabaseConnection());
        $query->eq('id', $id)->isNull('deleted_at')->find();
        return $query->isHydrated() ? $query : null;
    }

    /**
     * Find a published page by slug.
     *
     * @param string $slug URL slug
     * @return self|null Published page or null
     */
    public function findBySlug(string $slug): ?self
    {
        // Fresh instance: see findById() for why $this cannot be reused.
        $query = new self($this->getDatabaseConnection());
        $query->eq('slug', $slug)
             ->eq('status', 'published')
             ->isNull('deleted_at')
             ->find();
        return $query->isHydrated() ? $query : null;
    }

    /**
     * Creator id for a published page slug, or null when not found.
     *
     * Lean single-column lookup for the Profiles author card block; avoids
     * hydrating the full page row.
     */
    public function createdByForSlug(string $slug): ?int
    {
        $query = new self($this->getDatabaseConnection());
        $values = $query->eq('slug', $slug)
                        ->eq('status', 'published')
                        ->isNull('deleted_at')
                        ->limit(1)
                        ->pluck('created_by');
        return $values === [] ? null : (int) $values[0];
    }

    /**
     * Whether a slug is taken anywhere in the table.
     *
     * Soft-deleted rows count. The slug column carries a plain unique index,
     * so a deleted page still occupies its slug and an insert that ignores
     * that fails on the constraint. Counting every row keeps generateSlug()
     * in step with what the database will accept.
     *
     * @param string $slug Slug to check
     * @param int|null $excludeId Page ID to exclude from the check
     * @return bool True if the slug is taken
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt')->eq('slug', $slug);

        if ($excludeId !== null) {
            $query->notEqual('id', $excludeId);
        }

        $result = $query->find();
        return ($result->cnt ?? 0) > 0;
    }

    /**
     * Find all pages with pagination.
     *
     * @param int $page Current page number
     * @param int $perPage Results per page
     * @return self[] Array of pages
     */
    public function findAllPaginated(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $model = new self($this->getDatabaseConnection());
        return $model->isNull('deleted_at')
                     // id breaks created_at ties, so paging never repeats or
                     // drops a row when two pages share a timestamp.
                     ->order('created_at DESC', 'id DESC')
                     ->limit($perPage)
                     ->offset($offset)
                     ->findAll();
    }

    /**
     * Find published pages ordered by title.
     *
     * Consumed by link collectors (redirect target suggestions, navigation)
     * that need a stable, alphabetized list of live pages.
     *
     * @param int|null $limit Maximum results, null for every published page
     * @return self[] Array of published pages
     */
    public function findAllPublished(?int $limit = null): array
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('status', 'published')
              ->isNull('deleted_at')
              ->order('title ASC');

        if ($limit !== null) {
            $model->limit($limit);
        }

        return $model->findAll();
    }

    /**
     * Count all non-deleted pages.
     *
     * @return int Total page count
     */
    public function countAll(): int
    {
        $model = new self($this->getDatabaseConnection());
        $model->select('COUNT(*) as cnt')->isNull('deleted_at')->find();
        return (int) ($model->cnt ?? 0);
    }

    /**
     * Published pages as an id => title map, ordered by title.
     *
     * Consumed by the admin Settings form's homepage selector. Lazily
     * fetched from SettingsController rather than at core boot, so the
     * query never runs on a public request.
     *
     * @return array<int, string> page id => page title
     */
    public function getPublishedOptions(): array
    {
        $model = new self($this->getDatabaseConnection());
        $pages = $model->select('id', 'title')
                       ->eq('status', 'published')
                       ->isNull('deleted_at')
                       ->order('title ASC')
                       ->findAll();
        $options = [];
        foreach ($pages as $page) {
            $options[(int) $page->id] = (string) $page->title;
        }
        return $options;
    }

    /**
     * Find published pages matching a search term in title, slug, or content.
     *
     * Raw prepared statement because the pattern needs an explicit ESCAPE
     * clause so a caller-supplied % or _ stays literal; the fluent like()
     * operator cannot carry one. The escape character is '!' and not a
     * backslash: MySQL reads a backslash inside a string literal as an escaped
     * quote, so ESCAPE '\' is a syntax error there while SQLite accepts it,
     * which is why the SQLite suite never caught it. '!' is a plain literal on
     * MySQL, SQLite and Postgres alike.
     *
     * Supplies normalized content matches for the Search plugin. Ranking is
     * owned by SearchService, so this only finds matching content; the
     * stripped body rides along as `content` for the service to score.
     *
     * @param string $term       Raw search term
     * @param string $urlPrefix  Public route prefix for result URLs
     * @return list<array<string, mixed>>
     */
    public function searchContent(string $term, string $urlPrefix): array
    {
        $query = new self($this->getDatabaseConnection());
        /** @var array<int, static> $pages */
        $pages = $query->query(
            "SELECT * FROM pages
             WHERE (title LIKE :q ESCAPE '!' OR slug LIKE :q ESCAPE '!' OR content LIKE :q ESCAPE '!')
               AND status = :status
               AND deleted_at IS NULL",
            [':q' => '%' . $this->escapeLikePattern($term) . '%', ':status' => 'published']
        );

        $results = [];
        foreach ($pages as $page) {
            $stripped = html_entity_decode(strip_tags((string) ($page->content ?? '')), ENT_QUOTES, 'UTF-8');
            $len = mb_strlen($stripped);
            $pos = mb_stripos($stripped, $term);
            if ($pos !== false) {
                $start = max(0, $pos - 80);
                $excerpt = ($start > 0 ? '...' : '') . mb_substr($stripped, $start, 200) . ($start + 200 < $len ? '...' : '');
            } else {
                $excerpt = mb_substr($stripped, 0, 200) . ($len > 200 ? '...' : '');
            }

            $results[] = [
                'id'           => (int) $page->id,
                'title'        => (string) $page->title,
                'url'          => $urlPrefix . '/' . $page->slug,
                'excerpt'      => $excerpt,
                'content'      => $stripped,
                'content_type' => 'Page',
                'published_at' => $page->created_at,
            ];
        }

        return $results;
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    /**
     * Create a new page with auto-generated slug.
     *
     * Runs on a fresh instance so the returned page is not aliased to the
     * model that created it. Mutating `$this` would mean a second call
     * overwrites the page handed back by the first.
     *
     * @param string $title Page title
     * @param string $content HTML content
     * @param int $createdBy User ID of creator
     * @return self The created page
     */
    public function createPage(string $title, string $content, int $createdBy, int $ai_generated = 0): self
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $page = new self($this->getDatabaseConnection());
        $page->title = $title;
        $page->slug = $page->generateSlug($title);
        $page->content = $content;
        $page->status = 'draft';
        $page->created_by = $createdBy;
        $page->ai_generated = $ai_generated;
        $page->created_at = $now;
        $page->updated_at = $now;
        $page->insert();

        return $page;
    }

    /**
     * Update this page.
     *
     * @param array{title?: string, content?: string|null, status?: string, allow_comments?: mixed, ai_generated?: mixed} $data Fields to update
     */
    public function updatePage(array $data): void
    {
        if (isset($data['title'])) {
            $this->title = $data['title'];
        }
        if (isset($data['content'])) {
            $this->content = $data['content'];
        }
        if (isset($data['status'])) {
            $this->status = $data['status'];
        }
        if (isset($data['allow_comments'])) {
            $this->allow_comments = $data['allow_comments'] ? 1 : 0;
        }
        if (isset($data['ai_generated'])) {
            $this->ai_generated = $data['ai_generated'] ? 1 : 0;
        }

        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Soft-delete this page.
     */
    public function softDelete(): void
    {
        $this->deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Neutralize the LIKE wildcards % and _ so a user-supplied search term
     * matches them literally. searchContent() carries the matching ESCAPE
     * clause.
     *
     * The escape character there is '!', so '!' is doubled first. strtr()
     * replaces without rescanning what it already emitted, but an escape
     * character must still be escaped by itself to keep the resulting pattern
     * well formed. A backslash needs no handling: it is not the escape
     * character, so it is already literal in the pattern.
     */
    private function escapeLikePattern(string $term): string
    {
        return strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']);
    }

    /**
     * Generate a URL-safe slug from a title.
     *
     * @param string $title Source text
     * @return string URL-safe slug
     */
    protected function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = str_replace('&', 'and', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug) ?? '';
        $slug = preg_replace('/[\s]+/', '-', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $original = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
