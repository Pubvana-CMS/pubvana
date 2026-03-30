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
        return $this->adminView('marketplace/index', array_merge($this->baseData('Marketplace', 'marketplace'), [
            'items'   => $this->service->fetchAll(),
            'filter'  => '',
            'updates' => $this->service->checkUpdates(),
        ]));
    }

    public function themes(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        return $this->adminView('marketplace/index', array_merge($this->baseData('Themes — Marketplace', 'marketplace'), [
            'items'   => $this->service->fetchThemes(),
            'filter'  => 'theme',
            'updates' => [],
        ]));
    }

    public function widgets(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        return $this->adminView('marketplace/index', array_merge($this->baseData('Widgets — Marketplace', 'marketplace'), [
            'items'   => $this->service->fetchWidgets(),
            'filter'  => 'widget',
            'updates' => [],
        ]));
    }

    public function plugins(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        return $this->adminView('marketplace/index', array_merge($this->baseData('Plugins — Marketplace', 'marketplace'), [
            'items'   => $this->service->fetchPlugins(),
            'filter'  => 'plugin',
            'updates' => [],
        ]));
    }

    public function premiumCore(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        return $this->adminView('marketplace/index', array_merge($this->baseData('Premium Core — Marketplace', 'marketplace'), [
            'items'   => array_filter($this->service->fetchAll(), fn($i) => ($i->item_type ?? '') === 'premium_core'),
            'filter'  => 'premium_core',
            'updates' => [],
        ]));
    }

    public function refresh()
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $this->service->refreshCache();
        return redirect()->to('/admin/marketplace')->with('success', lang('Admin.marketplaceCacheRefreshed'));
    }

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
}
