<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminNotificationModel;
use App\Services\UpdateService;

abstract class BaseAdminController extends BaseController
{
    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        if (! auth()->loggedIn() || ! auth()->user()->can('admin.access')) {
            redirect()->to('/login')->with('error', lang('Admin.adminLoginRequired'))->send();
            exit;
        }

        $this->checkDirectoryWritability();
    }

    /**
     * Check that critical CMS directories are writable.
     * Inserts a non-dismissable admin notification for each unwritable directory,
     * and cleans up any resolved notifications when the directory becomes writable.
     */
    protected function checkDirectoryWritability(): void
    {
        $directories = [
            THEMES_PATH,
            WIDGETS_PATH,
            PLUGINS_PATH,
            FCPATH,
            WRITEPATH,
            WRITEPATH . 'cache/',
            WRITEPATH . 'logs/',
        ];

        $model = new AdminNotificationModel();

        foreach ($directories as $path) {
            $writable = false;

            // Actually try to write — don't trust is_writable()
            if (is_dir($path)) {
                $testFile = $path . '.pubvana_write_test_' . mt_rand();
                if (@file_put_contents($testFile, '') !== false) {
                    @unlink($testFile);
                    $writable = true;
                }
            }

            if (! $writable) {
                $existing = $model
                    ->where('source', 'system')
                    ->where('source_name', $path)
                    ->where('dismissed_at', null)
                    ->first();

                if (! $existing) {
                    $model->insert([
                        'source'         => 'system',
                        'source_name'    => $path,
                        'severity'       => 'error',
                        'message'        => lang('Admin.dirNotWritable', [$path]),
                        'is_dismissable' => 0,
                    ]);
                }
            } else {
                // Directory is writable — remove any lingering notification for it.
                $model
                    ->where('source', 'system')
                    ->where('source_name', $path)
                    ->where('dismissed_at', null)
                    ->delete();
            }
        }
    }

    protected function baseData(string $title, string $activeNav = ''): array
    {
        (new UpdateService())->checkForUpdate();

        $notifModel = new AdminNotificationModel();


        return array_merge($this->data, [
            'page_title'        => $title . ' — Pubvana Admin',
            'active_nav'        => $activeNav,
            'user'              => auth()->user(),
            'notifications'     => $notifModel->getActive(),
            'plugin_menu_items' => \App\Services\PluginManager::instance()->getMenuItems(),
        ]);
    }

    protected function adminView(string $view, array $data = []): string
    {
        return view('admin/' . $view, $data);
    }

}
