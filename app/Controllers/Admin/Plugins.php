<?php

namespace App\Controllers\Admin;

use App\Models\PluginModel;
use App\Services\PluginManager;

class Plugins extends BaseAdminController
{
    public function index()
    {
        $plugins = model(PluginModel::class)->orderBy('folder')->findAll();

        return $this->adminView('plugins/index', array_merge(
            $this->baseData(lang('Plugins.title'), 'plugins'),
            ['plugins' => $plugins]
        ));
    }

    public function discover()
    {
        $result = PluginManager::instance()->discover();

        if (! empty($result['discovered'])) {
            session()->setFlashdata('success', lang('Plugins.discovered'));
        } else {
            session()->setFlashdata('info', lang('Plugins.noneFound'));
        }

        if (! empty($result['warnings'])) {
            session()->setFlashdata('error', implode('<br>', array_map('esc', $result['warnings'])));
        }

        return redirect()->to('/admin/plugins');
    }

    public function activate()
    {
        $folder = $this->request->getPost('folder');

        if (empty($folder)) {
            return redirect()->to('/admin/plugins');
        }

        $pm     = PluginManager::instance();
        $status = $pm->activate($folder, force: (bool) $this->request->getPost('force'));

        switch ($status) {
            case 'activated':
                session()->setFlashdata('success', lang('Plugins.activated'));
                break;

            case 'not_found':
                session()->setFlashdata('error', lang('Plugins.notFound'));
                break;

            case 'already_active':
                session()->setFlashdata('info', lang('Plugins.alreadyActive'));
                break;

            case 'migration_failed':
                session()->setFlashdata('error', lang('Plugins.migrationFailed'));
                break;

            case 'install_failed':
                session()->setFlashdata('error', lang('Plugins.installFailed'));
                break;

            case 'requires_confirmation':
                session()->setFlashdata('confirm_activate', $folder);
                break;
        }

        return redirect()->to('/admin/plugins');
    }

    public function deactivate()
    {
        $folder = $this->request->getPost('folder');

        if (empty($folder)) {
            return redirect()->to('/admin/plugins');
        }

        if (PluginManager::instance()->deactivate($folder)) {
            session()->setFlashdata('success', lang('Plugins.deactivated'));
        } else {
            session()->setFlashdata('error', lang('Plugins.notFound'));
        }

        return redirect()->to('/admin/plugins');
    }
}
