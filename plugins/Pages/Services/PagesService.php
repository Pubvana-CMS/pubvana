<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Services;

use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Plugins\Pages\Models\PageRevision;
use Pubvana\Services\HtmlPurifierFactory;

/**
 * Service layer for pages — CRUD, published lookups, and host integrations.
 *
 * Registered on the app engine as `pages` by the Pages plugin, so any
 * consumer reaches pages through `$app->pages()` rather than touching
 * the model directly.
 *
 * @package Pubvana\Plugins\Pages\Services
 */
class PagesService
{
    private Page $pageModel;
    private PageRevision $revisionModel;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
    */
    public function __construct(\PDO $pdo, array $config = [])
    {
        $this->pageModel = new Page($pdo);
        $this->revisionModel = new PageRevision($pdo);
        $this->config = $config;
    }

    // ─── Pages ──────────────────────────────────────────────────────────

    /**
     * @return array{items: Page[], total: int, page: int, per_page: int}
     */
    public function listPages(int $page = 1, int $perPage = 20): array
    {
        return [
            'items'    => $this->pageModel->findAllPaginated($page, $perPage),
            'total'    => $this->pageModel->countAll(),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return Page[]
     */
    public function listPublished(int $limit = 100): array
    {
        return $this->pageModel->findAllPublished($limit);
    }

    public function findPage(int $id): ?Page
    {
        return $this->pageModel->findById($id);
    }

    public function findPageBySlug(string $slug): ?Page
    {
        return $this->pageModel->findBySlug($slug);
    }

    public function pageSlugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->pageModel->slugExists($slug, $excludeId);
    }

    /**
     * @param array<string, mixed> $data
    */
    public function createPage(array $data, int $userId): Page
    {
        $page = $this->pageModel->createPage(
            (string) ($data['title'] ?? ''),
            $this->purifyContent((string) ($data['content'] ?? '')),
            $userId,
            !empty($data['ai_generated']) ? 1 : 0
        );

        $page->updatePage([
            'status'         => (string) ($data['status'] ?? 'draft'),
            'allow_comments' => !empty($data['allow_comments']) ? 1 : 0,
        ]);

        $this->revisionModel->createFromPage($page, $userId);
        $this->pruneRevisions((int) $page->id);

        return $page;
    }

    /**
     * @param array<string, mixed> $data
    */
    public function updatePage(int $id, array $data, ?int $userId = null): ?Page
    {
        $page = $this->pageModel->findById($id);
        if ($page === null) {
            return null;
        }

        $authorId = $userId ?? (int) $page->created_by;
        $this->revisionModel->createFromPage($page, $authorId);

        $page->updatePage([
            'title'          => $data['title'] ?? $page->title,
            'content'        => isset($data['content']) ? $this->purifyContent((string) $data['content']) : $page->content,
            'status'         => $data['status'] ?? $page->status,
            'allow_comments' => !empty($data['allow_comments']) ? 1 : 0,
        ]);
        $this->pruneRevisions($id);

        return $page;
    }

    public function deletePage(int $id): bool
    {
        $page = $this->pageModel->findById($id);
        if ($page === null) {
            return false;
        }

        $page->softDelete();
        return true;
    }

    // ─── Revisions ──────────────────────────────────────────────────────

    /**
     * @return array<int, \Pubvana\Plugins\Pages\Models\PageRevision>
    */
    public function getRevisions(int $pageId): array
    {
        return $this->revisionModel->getForPage($pageId);
    }

    public function restoreRevision(int $pageId, int $revisionId, int $userId): ?Page
    {
        $page = $this->pageModel->findById($pageId);
        if ($page === null) {
            return null;
        }

        $revision = $this->revisionModel->findById($revisionId);
        if ($revision === null || (int) $revision->page_id !== $pageId) {
            return null;
        }

        // Snapshot the current (pre-restore) state first so the restore is
        // reversible; updatePage() below would otherwise overwrite it.
        $this->revisionModel->createFromPage($page, $userId);

        $page->updatePage([
            'title'          => $revision->title,
            'content'        => $revision->content,
            'status'         => $revision->status,
            'allow_comments' => (int) $revision->allow_comments,
        ]);

        $this->pruneRevisions($pageId);

        return $page;
    }

    private function pruneRevisions(int $pageId): void
    {
        $max = $this->config['max_revisions'] ?? 15;
        $this->revisionModel->pruneForPage($pageId, $max);
    }

    /**
     * Sanitize page HTML through the application's shared purifier config.
     *
     * Content arrives from the Jodit editor, so it is stored sanitized the
     * same way blog posts and comments are (Models/Page.php documents this
     * contract). A missing library returns the input unchanged: stripping
     * tags would destroy every page's markup.
     */
    private function purifyContent(string $html): string
    {
        if (!class_exists(\HTMLPurifier_Config::class)) {
            return $html;
        }

        return (new \HTMLPurifier(HtmlPurifierFactory::create()))->purify($html);
    }

    /**
     * Published pages as an id => title map for the Settings homepage selector.
     *
     * @return array<int, string> page id => page title
     */
    public function publishedOptions(): array
    {
        return $this->pageModel->getPublishedOptions();
    }

    // ─── Host Integrations ───────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
    */
    public function searchProvider(string $term): array
    {
        return $this->pageModel->searchContent($term, $this->routePrefix());
    }

    /**
     * Published pages as navigation manager Quick Add targets.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function navLinkableItems(): array
    {
        $items = [];
        foreach ($this->pageModel->findAllPublished() as $page) {
            $items[] = [
                'label' => (string) $page->title,
                'url'   => $this->routePrefix() . '/' . (string) $page->slug,
            ];
        }
        return $items;
    }

    /**
     * Published pages for the Broken Links scanner.
     *
     * @return array<int, array{type: string, id: int, title: string, content: string}>
     */
    public function brokenLinksItems(): array
    {
        $items = [];
        foreach ($this->pageModel->findAllPublished() as $page) {
            $items[] = [
                'type'    => 'page',
                'id'      => (int) $page->id,
                'title'   => (string) $page->title,
                'content' => (string) ($page->content ?? ''),
            ];
        }
        return $items;
    }

    /**
     * Enumerate published pages for the Comments host contract.
     *
     * @return array<int, array{type: string, id: int, title: string, url: string, allow_comments: bool}>
     */
    public function commentHostItems(): array
    {
        $items = [];
        foreach ($this->pageModel->findAllPublished() as $page) {
            $items[] = [
                'type'           => 'page',
                'id'             => (int) $page->id,
                'title'          => (string) $page->title,
                'url'            => $this->routePrefix() . '/' . (string) $page->slug,
                'allow_comments' => (bool) $page->allow_comments,
            ];
        }
        return $items;
    }

    // ─── Dashboard ──────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
    */
    public function dashboardCards(): array
    {
        return [[
            'id'          => 'total-pages',
            'label'       => 'Pages',
            'value'       => $this->pageModel->countAll(),
            'icon'        => 'ti-file',
            'tone'        => 'primary',
            'group'       => 'content',
            'href'        => $this->adminBase(),
            'description' => 'Static pages on the site.',
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dashboardSections(): array
    {
        $recent = $this->pageModel->findAllPaginated(1, 5);
        $adminBase = $this->adminBase();
        $items = [];

        foreach ($recent as $page) {
            $items[] = [
                'label'    => $page->title,
                'meta'     => ucfirst((string) $page->status) . ' · ' . (($ts = strtotime((string) $page->created_at)) === false ? '' : date('M j, Y g:ia', $ts)),
                'href'     => $adminBase . '/' . (int) $page->id . '/edit',
                'emphasis' => $page->status === 'published' ? 'success' : 'secondary',
            ];
        }

        return [[
            'id'          => 'recent-pages',
            'title'       => 'Recent Pages',
            'type'        => 'list',
            'icon'        => 'ti-file-text',
            'group'       => 'content',
            'href'        => $adminBase,
            'empty_state' => 'No pages have been created yet.',
            'items'       => $items,
        ]];
    }

    /**
     * The admin list base for this plugin: /admin plus the plugin route prefix.
     *
     * Dashboard links are admin destinations, not the public route prefix, so
     * they must carry the /admin segment like PagesAdminController::adminBase().
     */
    private function adminBase(): string
    {
        $prefix = $this->routePrefix();
        if ($prefix === '') {
            return '/admin';
        }

        return '/admin' . $prefix;
    }

    /**
     * The public route prefix for this plugin (leading slash, no trailing slash).
     */
    private function routePrefix(): string
    {
        return rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
    }
}