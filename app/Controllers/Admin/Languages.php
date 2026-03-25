<?php

namespace App\Controllers\Admin;

use App\Models\LanguageModel;

class Languages extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
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
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $model = new LanguageModel();
        $lang  = $model->find($id);

        if (! $lang) {
            return redirect()->to('/admin/languages')->with('error', lang('Admin.languageNotFound'));
        }

        $model->update($id, ['is_active' => 1]);
        cache()->delete('active_languages');

        return redirect()->to('/admin/languages')->with('success', lang('Admin.languageEnabled_msg', [esc($lang->name)]));
    }

    public function disable(int $id)
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $model = new LanguageModel();
        $lang  = $model->find($id);

        if (! $lang) {
            return redirect()->to('/admin/languages')->with('error', lang('Admin.languageNotFound'));
        }

        if ($lang->is_default) {
            return redirect()->to('/admin/languages')->with('error', lang('Admin.languageCannotDisable'));
        }

        $model->update($id, ['is_active' => 0]);
        cache()->delete('active_languages');

        return redirect()->to('/admin/languages')->with('success', lang('Admin.languageDisabled_msg', [esc($lang->name)]));
    }

    public function makeDefault(int $id)
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $model = new LanguageModel();
        $result = $model->makeDefault($id);

        if (! $result) {
            return redirect()->to('/admin/languages')->with('error', lang('Admin.languageNotFound'));
        }

        cache()->delete('active_languages');

        $lang = $model->find($id);

        return redirect()->to('/admin/languages')->with('success', lang('Admin.languageSetAsDefault', [esc($lang->name)]));
    }
}
