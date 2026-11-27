<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Controllers;

use Pubvana\Controllers\Public\PublicController;
use flight\Engine;

/**
 * PagesPublicController - Public page rendering.
 *
 * Handles rendering published pages by slug. Admin CRUD is handled
 * by PagesAdminController.
 *
 * @package Pubvana\Plugins\Pages\Controllers
 */
class PagesPublicController extends PublicController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.pages');
    }

    /**
     * Public page listing.
     *
     * Redirects to the homepage. Pages are accessed by slug, not listed.
     */
    public function index(): void
    {
        $this->app->redirect('/');
    }

    /**
     * Public page rendering.
     *
     * Finds a published page by slug and renders it through the
     * theme layout with full global data.
     *
     * @param string $slug        Page slug
     * @param bool   $isHomepage  True when this page is serving the homepage
     */
    public function view(string $slug, bool $isHomepage = false): void
    {
        $page = $this->app->pages()->findPageBySlug($slug);

        if ($page === null) {
            $this->app->halt(404, 'Page not found');
            return;
        }

        $author = $this->authorContext((int) $page->created_by);

        $this->render('pubvana/pages/page', [
            'title'          => $page->title,
            'content'        => $page->content,
            'featured_image' => null,
            'ai_disclosure'  => $this->aiDisclosure((int) $page->ai_generated),
            'commentable'    => ['type' => 'page', 'id' => (int) $page->id],
            'allow_comments' => (bool) $page->allow_comments,
            'is_homepage'    => $isHomepage,
            'updated_at'     => $page->updated_at,
            'seo_context'    => [
                'content_type' => 'page',
                'content_id'   => (int) $page->id,
                'title'        => $page->title,
                'description'  => '',
                'content'      => (string) ($page->content ?? ''),
                'image'        => '',
                'author'       => $author,
                'author_id'    => (int) $page->created_by,
                'ai_generated' => !empty($page->ai_generated),
                'updated_at'   => $page->updated_at,
                'og_type'      => 'website',
            ],
        ]);
    }

    /**
     * Preview a page by its preview token.
     *
     * Drafts render through the same theme template as the published page.
     */
    public function preview(string $token): void
    {
        $page = $this->app->pages()->findPageByPreviewToken($token);

        if ($page === null) {
            $this->app->halt(404, 'Preview not found');
            return;
        }

        // The route checks only the token, so anyone who knows the URL can
        // open the page. Set no-store so captureResponse() skips this page,
        // and noindex so search engines do.
        $this->app->response()->header('Cache-Control', 'private, no-store');
        $this->app->response()->header('X-Robots-Tag', 'noindex');

        $author = $this->authorContext((int) $page->created_by);

        $this->render('pubvana/pages/page', [
            'title'          => $page->title . ' (Preview)',
            'content'        => $page->content,
            'featured_image' => null,
            'ai_disclosure'  => $this->aiDisclosure((int) $page->ai_generated),
            'commentable'    => ['type' => 'page', 'id' => (int) $page->id],
            'allow_comments' => false,
            'is_homepage'    => false,
            'updated_at'     => $page->updated_at,
            'seo_context'    => [
                'content_type' => 'page',
                'content_id'   => (int) $page->id,
                'title'        => $page->title . ' (Preview)',
                'description'  => '',
                'content'      => (string) ($page->content ?? ''),
                'image'        => '',
                'author'       => $author,
                'author_id'    => (int) $page->created_by,
                'ai_generated' => !empty($page->ai_generated),
                'updated_at'   => $page->updated_at,
                'og_type'      => 'website',
            ],
        ]);
    }

    /**
     * Author context for the page creator, or null when SEO is off.
     *
     * Pages has no hard dependency on the SEO plugin, so a missing facade
     * returns null instead of breaking the page.
     *
     * @return array{name: string, username: string, url: string, sameAs: string[], jobTitle: ?string, worksFor: ?string}|null
     */
    private function authorContext(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        try {
            return $this->app->seo()->authorContext($userId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whether to show the visible AI-assistance disclosure for this page.
     */
    private function aiDisclosure(int|bool $aiGenerated): bool
    {
        if (empty($aiGenerated)) {
            return false;
        }

        return (bool) $this->app->settings()->get('Seo.ai_disclosure_enabled', true);
    }
}
