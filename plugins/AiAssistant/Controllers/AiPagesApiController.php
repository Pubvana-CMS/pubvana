<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

use Pubvana\Plugins\AiAssistant\Models\AiKey;

/**
 * AiPagesApiController - /api/ai/pages/* endpoints.
 *
 * Creates and updates pages through the Pages plugin. Converts submitted
 * markdown to HTML. Pages are draft or published only.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiPagesApiController extends AiApiController
{
    /**
     * List pages, newest first, across every status.
     */
    public function pages(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.read');

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));
        $status = $query->status ?? null;
        if ($status !== null && !in_array($status, ['draft', 'published'], true)) {
            $this->log($key, 'error', 'page', null, "Invalid status '{$status}'.");
            $this->fail(422, 'status filter must be one of: draft, published.');
        }
        $search = $this->app->ai()->searchParam($query);

        $result = $this->app->ai()->listPagesForApi(
            $page,
            $perPage,
            $status !== null ? (string) $status : null,
            $search
        );

        $detail = 'Listed pages';
        if ($status !== null) {
            $detail .= " (status: {$status})";
        }
        if ($search !== null) {
            $detail .= " (search: {$search})";
        }
        $this->log($key, 'ok', 'page', null, $detail . '.');
        $this->ok($result);
    }

    /**
     * Fetch one page by slug, serving content as markdown.
     */
    public function page(string $slug): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.read');

        $page = $this->app->ai()->findPageForApi($slug);
        if ($page === null) {
            $this->log($key, 'error', 'page', null, "Page '{$slug}' not found.");
            $this->fail(404, 'Page not found.');
        }

        $this->log($key, 'ok', 'page', (int) $page->id, "Fetched page '{$slug}'.");
        $this->ok($this->app->ai()->serializePage($page));
    }

    /**
     * Create a page as a draft by default.
     */
    public function createPage(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.create');

        $payload = $this->payload();

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            $this->log($key, 'error', 'page', null, 'Missing title.');
            $this->fail(422, 'title is required.');
        }

        $content = $this->app->ai()->resolveContent($payload);
        if ($content === null) {
            $this->log($key, 'error', 'page', null, 'Missing content.');
            $this->fail(422, 'Provide content_md (markdown) or content (HTML).');
        }

        $status = $this->resolvePageStatus($key, $payload, null);

        try {
            $page = $this->svc('pages')->createPage([
                'title'          => $title,
                'content'        => $content,
                'status'         => $status,
                'allow_comments' => !empty($payload['allow_comments']) ? 1 : 0,
                'ai_generated'   => 1,
            ], $this->app->ai()->defaultAuthorId());
        } catch (\Throwable $e) {
            $this->log($key, 'error', 'page', null, $e->getMessage());
            $this->fail(500, 'The page could not be created: ' . $e->getMessage());
        }

        $this->app->ai()->saveSeo('page', (int) $page->id, $payload);

        $this->log($key, 'ok', 'page', (int) $page->id, "Created page #{$page->id}.");
        $this->ok([
            'page' => $this->app->ai()->serializePage($page),
            'url'  => $this->app->pluginLoader()->routePrefix('pubvana/pages') . '/' . $page->slug,
            'id'   => (int) $page->id,
        ]);
    }

    /**
     * Partially update a page. Omitting status leaves the current state.
     */
    public function updatePage(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.update');

        $payload = $this->payload();
        $pages = $this->svc('pages');

        $existing = $pages->findPage((int) $id);
        if ($existing === null) {
            $this->log($key, 'error', 'page', (int) $id, 'Page not found.');
            $this->fail(404, 'Page not found.');
        }

        $status = $this->resolvePageStatus($key, $payload, $existing);

        $content = $this->app->ai()->resolveContent($payload);
        $update = [];
        if (array_key_exists('title', $payload)) {
            $update['title'] = (string) $payload['title'];
        }
        if ($content !== null) {
            $update['content'] = $content;
        }
        $update['status'] = $status;
        if (array_key_exists('allow_comments', $payload)) {
            $update['allow_comments'] = !empty($payload['allow_comments']) ? 1 : 0;
        }

        $page = $pages->updatePage((int) $id, $update);
        $this->app->ai()->saveSeo('page', (int) $id, $payload);
        $this->log($key, 'ok', 'page', (int) $id, "Updated page #{$id}.");
        $this->ok($this->app->ai()->serializePage($page));
    }

    /**
     * Delete a page.
     */
    public function deletePage(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.delete');

        $page = $this->svc('pages')->findPage((int) $id);
        if ($page === null) {
            $this->log($key, 'error', 'page', (int) $id, 'Page not found.');
            $this->fail(404, 'Page not found.');
        }

        $this->svc('pages')->deletePage((int) $id);
        $this->log($key, 'ok', 'page', (int) $id, "Deleted page #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    /**
     * Pick the page's target status, checking grants. Pages are draft or
     * published only.
     *
     * State changes are gated: moving to published takes pages.publish,
     * and moving a published page to draft takes the same grant.
     * Re-applying the current status takes nothing.
     *
     * @param array<string, mixed> $payload Posted payload
     * @param \Pubvana\Plugins\Pages\Models\Page|null $existing Page being updated, or null when creating
     * @return string The resolved status
     */
    protected function resolvePageStatus(AiKey $key, array $payload, $existing): string
    {
        $explicit = array_key_exists('status', $payload);
        $status = (string) ($payload['status'] ?? ($existing !== null ? (string) $existing->status : 'draft'));
        $transition = $explicit && ($existing === null || (string) $existing->status !== $status);

        if ($status === 'published') {
            if ($transition) {
                $this->requireGrant($key, 'pages.publish');
            }
} elseif ($status === 'draft') {
                if ($explicit && $existing !== null && in_array((string) $existing->status, ['published', 'scheduled'], true)) {
                    $grant = $this->app->ai()->demoteGrant('pages', (string) $existing->status);
                    if ($grant !== null) {
                        $this->requireGrant($key, $grant);
                    }
                }
            } else {
            $this->log($key, 'error', 'page', $existing !== null ? (int) $existing->id : null, "Invalid status '{$status}'.");
            $this->fail(422, 'status must be one of: draft, published.');
        }

        return $status;
    }
}