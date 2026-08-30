<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * PagesAdminController - Admin CRUD for pages.
 *
 * Handles listing, creating, editing, deleting, publishing, and
 * revision history of static pages. Extends AdminController for
 * dashboard layout wrapping.
 *
 * @package Pubvana\Plugins\Pages\Controllers
 */
class PagesAdminController extends AdminController
{
    public function index(): void
    {
        $request = $this->app->request();
        $page = max(1, (int) ($request->query->page ?? 1));
        $perPage = 20;

        $result = $this->app->pages()->listPages($page, $perPage);
        $this->render('pubvana/pages/admin/index', [
            'pageTitle' => 'Pages',
            'pages'     => $result['items'],
            'total'     => $result['total'],
            'page'      => $page,
            'perPage'   => $perPage,
        ]);
    }

    public function create(): void
    {
        $joditHtml = $this->app->media()->joditInit('#content');
        $this->render('pubvana/pages/admin/create', [
            'pageTitle' => 'New Page',
            'joditHtml' => $joditHtml,
        ]);
    }

    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        if (trim($post['title'] ?? '') === '') {
            $this->app->session()->flash('error', 'Title is required.');
            $this->app->redirect('/admin/pages/create');
            return;
        }

        $userId = $this->app->auth()->user()->id ?? 0;
        $this->app->pages()->createPage($post, $userId);

        $this->app->session()->flash('success', 'Page created.');
        $this->app->redirect('/admin/pages');
    }

    public function edit(string $id): void
    {
        $page = $this->app->pages()->findPage((int) $id);

        if ($page === null) {
            $this->app->redirect('/admin/pages');
            return;
        }

        $joditHtml = $this->app->media()->joditInit('#content');
        $this->render('pubvana/pages/admin/edit', [
            'pageTitle' => 'Edit Page',
            'editPage'  => $page,
            'joditHtml' => $joditHtml,
        ]);
    }

    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $userId = $this->app->auth()->user()->id ?? 0;

        if ($this->app->pages()->updatePage((int) $id, $post, $userId) === null) {
            $this->app->redirect('/admin/pages');
            return;
        }

        $this->app->session()->flash('success', 'Page updated.');
        $this->app->redirect('/admin/pages/' . $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->app->pages()->deletePage((int) $id);

        $this->app->session()->flash('success', 'Page deleted.');
        $this->app->redirect('/admin/pages');
    }

    public function revisions(string $id): void
    {
        $page = $this->app->pages()->findPage((int) $id);

        if ($page === null) {
            $this->app->redirect('/admin/pages');
            return;
        }

        $revisions = $this->app->pages()->getRevisions((int) $id);

        $this->render('pubvana/pages/admin/revisions', [
            'pageTitle' => 'Revisions: ' . $page->title,
            'page'      => $page,
            'revisions' => $revisions,
        ]);
    }

    public function restore(string $id, string $revisionId): void
    {
        $userId = $this->app->auth()->user()->id ?? 0;
        $this->app->pages()->restoreRevision((int) $id, (int) $revisionId, $userId);
        $this->app->session()->flash('success', 'Revision restored.');
        $this->app->redirect('/admin/pages/' . $id . '/edit');
    }
}
