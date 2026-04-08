<?php

namespace App\Controllers\Admin;

use App\Models\MarketplaceLicenseModel;
use App\Models\PluginModel;
use App\Services\PluginManager;

class Plugins extends BaseAdminController
{
    public function index()
    {
        PluginManager::instance()->discover();

        $plugins = model(PluginModel::class)->orderBy('folder')->findAll();

        // Build license lookup keyed by store_product_id
        $allLicenses = (new MarketplaceLicenseModel())->where('item_type', 'plugin')->findAll();
        $licenses    = [];
        foreach ($allLicenses as $lic) {
            $licenses[$lic->store_product_id] = $lic;
        }

        return $this->adminView('plugins/index', array_merge(
            $this->baseData(lang('Plugins.title'), 'plugins'),
            [
                'plugins'         => $plugins,
                'licenses'        => $licenses,
                'invalidLicenses' => array_filter(
                    (new \App\Services\MarketplaceService())->getInvalidLicenses(),
                    fn($l) => $l->item_type === 'plugin'
                ),
            ]
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

            case 'disabled':
                session()->setFlashdata('error', lang('Admin.activationBlockedDisabled', [$folder]));
                break;

            case 'requires_confirmation':
                session()->setFlashdata('confirm_activate', $folder);
                break;

            case 'tampered_bundled':
                session()->setFlashdata('error', lang('Admin.activationBlockedBundled', [$folder]));
                break;

            case 'tampered_no_urls':
                session()->setFlashdata('error', lang('Admin.activationBlockedNoUrls', [$folder]));
                break;

            case 'tampered_free_flag':
                session()->setFlashdata('error', lang('Admin.activationBlockedFreeFlag', [$folder]));
                break;

            case 'invalid_license':
                return redirect()->to('/admin/plugins')->with('error', lang('Admin.pluginInvalidLicense'));
        }

        return redirect()->to('/admin/plugins');
    }

    public function saveLicense()
    {
        $licenseKey     = trim($this->request->getPost('license_key') ?? '');
        $storeProductId = (int) ($this->request->getPost('store_product_id') ?? 0);

        if (! $licenseKey || ! $storeProductId) {
            return redirect()->to('/admin/plugins')->with('error', lang('Plugins.licenseKeyRequired'));
        }

        // Determine validation URL (Pubvana vs third-party)
        $plugin    = model(PluginModel::class)->where('store_product_id', $storeProductId)->first();
        $isPubvana = $plugin && in_array(strtolower(trim($plugin->author ?? '')), ['pubvana', 'pubvana_team'], true);
        $validateUrl = $isPubvana
            ? PUBVANA_DSTORE_API . 'license/validate'
            : ($plugin->license_validate_url ?? null);

        if (! $validateUrl) {
            return redirect()->to('/admin/plugins')->with('error', lang('Plugins.licenseCheckFailed'));
        }

        // Validate against the store API
        try {
            $client   = \Config\Services::curlrequest(['timeout' => 10]);
            $response = $client->post($validateUrl, [
                'json' => [
                    'license_key' => $licenseKey,
                    'product_id'  => $storeProductId,
                    'domain'      => base_url(),
                ],
                'http_errors' => false,
            ]);

            $body  = json_decode($response->getBody(), true);
            $valid = ($response->getStatusCode() === 200 && ! empty($body['valid']));
        } catch (\Throwable $e) {
            return redirect()->to('/admin/plugins')->with('error', lang('Plugins.licenseCheckFailed'));
        }

        if (! $valid) {
            $error = $body['error'] ?? lang('Plugins.licenseInvalid');
            return redirect()->to('/admin/plugins')->with('error', $error);
        }

        // Upsert marketplace_licenses
        $licenseModel = new MarketplaceLicenseModel();
        $existing     = $licenseModel->where('store_product_id', $storeProductId)
            ->where('item_type', 'plugin')
            ->first();

        $productName = $plugin->name ?? 'Plugin';

        $licenseData = [
            'store_product_id'     => $storeProductId,
            'product_name'         => $productName,
            'item_type'            => 'plugin',
            'license_valid'        => 1,
            'license_last_checked' => date('Y-m-d H:i:s'),
            'author'               => $plugin->author ?? '',
        ];

        if ($existing) {
            $licenseData['license_key'] = $licenseKey;
            $licenseModel->update($existing->id, $licenseData);
        } else {
            $licenseData['license_key'] = $licenseKey;
            $licenseModel->insert($licenseData);
        }

        return redirect()->to('/admin/plugins')->with('success', lang('Plugins.licenseSaved'));
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
