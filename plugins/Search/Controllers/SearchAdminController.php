<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Search\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * SearchAdminController - Admin source manager for site search.
 *
 * Lists every registered search source (Blog, Pages, ...) with an
 * enable/disable toggle so admins control which content types participate
 * in search. Also edits the scalar search settings.
 *
 * @package Pubvana\Plugins\Search
 */
class SearchAdminController extends AdminController
{
    /**
     * Show the source manager + settings.
     */
    public function index(): void
    {
        $this->render('pubvana/search/admin/index', [
            'pageTitle'      => 'Search',
            'sources'        => $this->app->search()->enabledSources(true),
            'resultsPerPage' => (int) $this->app->search()->setting('resultsPerPage', 10),
            'minQueryLength' => (int) $this->app->search()->setting('minQueryLength', 3),
        ]);
    }

    /**
     * Persist source toggles and scalar settings.
     */
    public function save(): void
    {
        $data = $this->app->request()->data->getData();
        unset($data['_csrf_token']);

        $search = $this->app->search();

        $resultsPerPage = max(1, (int) ($data['results_per_page'] ?? 10));
        $minQueryLength = max(1, (int) ($data['min_query_length'] ?? 3));

        $this->app->settings()->set('Search.resultsPerPage', (string) $resultsPerPage);
        $this->app->settings()->set('Search.minQueryLength', (string) $minQueryLength);

        foreach ($search->sources() as $key => $source) {
            $want = isset($data['source_' . $key]);
            $search->setSourceEnabled($key, $want);
        }

        $this->app->session()->flash('success', 'Search settings saved.');
        $this->app->redirect('/admin/search');
    }
}
