<?php

namespace App\Controllers\Admin;

use App\Services\ActivityLogger;
use App\Services\MarketplaceService;

class Marketplace extends BaseAdminController
{
    protected MarketplaceService $service;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->service = new MarketplaceService();
    }

    public function index(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        // Build a slug -> latest_version map from all three extension tables
        $db = db_connect();
        $pendingUpdates = [];
        foreach (['themes', 'widgets', 'plugins'] as $t) {
            $rows = $db->table($t)
                ->where('latest_version IS NOT NULL')
                ->get()->getResult();
            foreach ($rows as $r) {
                if (! empty($r->latest_version) && version_compare($r->latest_version, $r->version ?? '0.0.0', '>')) {
                    $pendingUpdates[$r->folder] = $r->latest_version;
                }
            }
        }

        return $this->adminView('marketplace/index', array_merge(
            $this->baseData('Marketplace', 'marketplace'),
            [
                'categories'      => $this->service->fetchAll(),
                'updates'         => $this->service->checkUpdates(),
                'pending_updates' => $pendingUpdates,
            ]
        ));
    }

    public function licenses(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        return $this->adminView('marketplace/licenses', array_merge(
            $this->baseData('Licenses', 'marketplace_licenses'),
            [
                'licenses' => $this->service->getAllLicenses(),
            ]
        ));
    }

    public function refresh()
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $this->service->refreshCache();

        return redirect()->to('/admin/marketplace')->with('success', lang('Admin.marketplaceCacheRefreshed'));
    }

    /**
     * Install a free item (from marketplace card).
     */
    public function install()
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin/marketplace')->with('error', lang('Admin.permissionDenied'));
        }

        $url    = $this->request->getPost('download_url');
        $type   = $this->request->getPost('item_type') ?? $this->request->getPost('type');
        $folder = $this->request->getPost('slug') ?? $this->request->getPost('folder');

        if (! $url || ! in_array($type, ['theme', 'widget', 'plugin'], true) || ! $folder) {
            return redirect()->to('/admin/marketplace')->with('error', lang('Admin.marketplaceInvalidRequest'));
        }

        $ok = $this->service->installFree($url, $type, $folder);
        if ($ok) {
            ActivityLogger::log('marketplace.installed', 'marketplace', null, 'Installed ' . $type . ': ' . $folder);
            return redirect()->to('/admin/marketplace')->with('success', lang('Admin.marketplaceInstallSuccess', [$folder]));
        }

        return redirect()->to('/admin/marketplace')->with('error', lang('Admin.marketplaceInstallFail'));
    }

    /**
     * Install a licensed (paid) item.
     */
    public function installLicensed()
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin/marketplace')->with('error', lang('Admin.permissionDenied'));
        }

        $licenseKey = trim($this->request->getPost('license_key') ?? '');
        $slug       = trim($this->request->getPost('slug') ?? '');
        $itemType   = trim($this->request->getPost('item_type') ?? '');

        if (! $licenseKey || ! $slug || ! $itemType) {
            return redirect()->to('/admin/marketplace')->with('error', lang('Admin.marketplaceInvalidRequest'));
        }

        $ok = $this->service->installLicensed($licenseKey, $slug, $itemType);
        if ($ok) {
            ActivityLogger::log('marketplace.installed', 'marketplace', null, 'Installed licensed ' . $itemType . ': ' . $slug);
            return redirect()->to('/admin/marketplace')->with('success', lang('Admin.marketplaceInstallSuccess', [$slug]));
        }

        return redirect()->to('/admin/marketplace')->with('error', lang('Admin.marketplaceInstallFail'));
    }

    public function update(string $slug)
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $item = db_connect()->table('marketplace_items')->where('slug', $slug)->get()->getRowObject();
        if (! $item || ! $item->download_url) {
            return redirect()->to('/admin/marketplace')->with('error', lang('Admin.marketplaceCannotUpdate'));
        }

        $ok = $this->service->installFree($item->download_url, $item->item_type, $slug);
        if ($ok) {
            return redirect()->to('/admin/marketplace')->with('success', lang('Admin.marketplaceUpdateSuccess'));
        }

        return redirect()->to('/admin/marketplace')->with('error', lang('Admin.marketplaceUpdateFail'));
    }

    public function revalidate(int $id)
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin/marketplace/licenses')->with('error', lang('Admin.permissionDenied'));
        }

        $status = $this->service->revalidateSingle($id);

        $message = match ($status) {
            'valid'       => lang('Admin.licenseRevalidateValid'),
            'invalid'     => lang('Admin.licenseRevalidateInvalid'),
            'unreachable' => lang('Admin.licenseRevalidateUnreachable'),
            'skipped'     => lang('Admin.licenseRevalidateSkipped'),
            default       => lang('Admin.licenseRevalidateNotFound'),
        };

        $type = $status === 'valid' ? 'success' : 'error';

        return redirect()->to('/admin/marketplace/licenses')->with($type, $message);
    }
}
