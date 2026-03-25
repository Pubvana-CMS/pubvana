<?php

namespace App\Controllers\Admin;

use App\Models\LanguageModel;

class Languages extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $model     = new LanguageModel();
        $languages = $model->orderBy('sort_order', 'ASC')->findAll();

        return $this->adminView('languages/index', array_merge(
            $this->baseData('Languages', 'languages'),
            ['languages' => $languages]
        ));
    }

    public function enable(int $id)
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $model = new LanguageModel();
        $lang  = $model->find($id);

        if (! $lang) {
            return redirect()->to('/admin/languages')->with('error', 'Language not found.');
        }

        $model->update($id, ['is_active' => 1]);
        cache()->delete('active_languages');

        return redirect()->to('/admin/languages')->with('success', esc($lang->name) . ' enabled.');
    }

    public function disable(int $id)
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $model = new LanguageModel();
        $lang  = $model->find($id);

        if (! $lang) {
            return redirect()->to('/admin/languages')->with('error', 'Language not found.');
        }

        if ($lang->is_default) {
            return redirect()->to('/admin/languages')->with('error', 'Cannot disable the default language.');
        }

        $model->update($id, ['is_active' => 0]);
        cache()->delete('active_languages');

        return redirect()->to('/admin/languages')->with('success', esc($lang->name) . ' disabled.');
    }

    public function makeDefault(int $id)
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $model = new LanguageModel();
        $result = $model->makeDefault($id);

        if (! $result) {
            return redirect()->to('/admin/languages')->with('error', 'Language not found.');
        }

        cache()->delete('active_languages');

        $lang = $model->find($id);

        return redirect()->to('/admin/languages')->with('success', esc($lang->name) . ' set as default language.');
    }
}
