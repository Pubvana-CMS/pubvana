<?php

namespace App\Controllers\Admin;

use App\Services\ActivityLogger;
use App\Services\BackupService;

class Backup extends BaseAdminController
{
    public function index(): string
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $service = new BackupService();

        return $this->adminView('backup/index', array_merge(
            $this->baseData('Backup & Export', 'backup'),
            ['backups' => $service->listBackups()]
        ));
    }

    public function download()
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        try {
            $service = new BackupService();
            $zipPath = $service->createBackup();
        } catch (\Throwable $e) {
            log_message('error', 'Backup failed: ' . $e->getMessage());
            return redirect()->to('/admin/backup')->with('error', lang('Admin.backupFailed', [$e->getMessage()]));
        }

        ActivityLogger::log('settings.updated', 'setting', null, 'Created site backup');

        $filename = basename($zipPath);
        $size     = filesize($zipPath);

        // Stream the zip then delete it
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $size);
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    public function deleteFile()
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $filename = $this->request->getPost('filename') ?? '';
        $service  = new BackupService();

        if ($service->deleteBackup($filename)) {
            return redirect()->to('/admin/backup')->with('success', lang('Admin.backupDeleted'));
        }

        return redirect()->to('/admin/backup')->with('error', lang('Admin.backupCannotDelete'));
    }
}
