<?php

namespace App\Controllers\Admin;

use App\Models\MarketplaceLicenseModel;
use App\Models\SocialModel;
use App\Models\ThemeModel;
use App\Services\ActivityLogger;
use App\Services\IconService;

class Themes extends BaseAdminController
{
    public function index(): string
    {
        $themeService = service('theme');
        $themeService->sync();

        $themes = (new ThemeModel())->findAll();

        // Build license lookup keyed by store_product_id
        $allLicenses = (new MarketplaceLicenseModel())->where('item_type', 'theme')->findAll();
        $licenses    = [];
        foreach ($allLicenses as $lic) {
            $licenses[$lic->store_product_id] = $lic;
        }

        return $this->adminView('themes/index', array_merge($this->baseData('Themes', 'themes'), [
            'themes'          => $themes,
            'validation'      => $themeService->getValidationResults(),
            'licenses'        => $licenses,
            'invalidLicenses' => array_filter(
                (new \App\Services\MarketplaceService())->getInvalidLicenses(),
                fn($l) => $l->item_type === 'theme'
            ),
        ]));
    }

    public function activate(int $id)
    {
        if (! auth()->user()->can('admin.themes')) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.permissionDenied'));
        }

        $theme = (new ThemeModel())->find($id);
        if (! $theme) {
            return redirect()->to('/admin/themes')->with('error', 'Theme not found.');
        }

        $service = service('theme');

        if (! $service->validateTheme($theme->folder)) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.themeValidationFailed'));
        }

        $status = $service->activate($id);
        if ($status !== 'activated') {
            $errorKey = match ($status) {
                'disabled'           => 'Admin.activationBlockedDisabled',
                'tampered_bundled'   => 'Admin.activationBlockedBundled',
                'tampered_no_urls'   => 'Admin.activationBlockedNoUrls',
                'tampered_free_flag' => 'Admin.activationBlockedFreeFlag',
                'invalid_license'    => 'Admin.licenseRequired',
                default              => 'Admin.themeInvalidLicense',
            };
            $name = $theme->name ?? 'Theme';
            return redirect()->to('/admin/themes')->with('error', lang($errorKey, [$name]));
        }

        // Convert social link icons to the newly activated theme's icon pack
        $jsonPath = THEMES_PATH . $theme->folder . '/theme_info.json';
        if (is_file($jsonPath)) {
            $info = json_decode(file_get_contents($jsonPath), true) ?? [];
            $pack = $info['icon_pack'] ?? '';
            $ver  = $info['icon_pack_ver'] ?? '';

            if ($pack && $ver) {
                $expectedBase = IconService::getBaseClass($pack, $ver);
                if ($expectedBase) {
                    $socialModel = new SocialModel();
                    $links = $socialModel->findAll();

                    foreach ($links as $link) {
                        if (! str_starts_with($link->icon, $expectedBase)) {
                            $platformKey = IconService::getPlatformFromIcon($link->icon);
                            if ($platformKey) {
                                $newIcon = IconService::getClass($platformKey, $pack, $ver);
                                $socialModel->update($link->id, ['icon' => $newIcon]);
                            }
                        }
                    }
                }
            }
        }

        ActivityLogger::log('theme.activated', 'theme', $id, 'Activated theme: ' . ($theme->name ?? $id));
        return redirect()->to('/admin/themes')->with('success', lang('Admin.themeActivated'));
    }

    public function options(int $id): string
    {
        if (! auth()->user()->can('admin.themes')) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.permissionDenied'));
        }

        $theme = (new ThemeModel())->find($id);
        if (! $theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $jsonFile = THEMES_PATH . $theme->folder . '/theme_info.json';
        $phpFile  = THEMES_PATH . $theme->folder . '/theme_info.php';
        if (is_file($jsonFile)) {
            $info = json_decode(file_get_contents($jsonFile), true) ?? [];
        } elseif (is_file($phpFile)) {
            $info = require $phpFile;
        } else {
            $info = [];
        }

        $service = service('theme');
        $saved   = [];
        foreach (array_keys($info['options'] ?? []) as $key) {
            $saved[$key] = $service->getThemeOption($id, $key, $info['options'][$key]['default'] ?? '');
        }

        return $this->adminView('themes/options', array_merge($this->baseData('Theme Options', 'themes'), [
            'theme'   => $theme,
            'info'    => $info,
            'options' => $info['options'] ?? [],  // definitions (type, label, default)
            'saved'   => $saved,                  // current saved values
        ]));
    }

    public function saveOptions(int $id)
    {
        if (! auth()->user()->can('admin.themes')) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.permissionDenied'));
        }

        $theme = (new ThemeModel())->find($id);
        if (! $theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $jsonFile = THEMES_PATH . $theme->folder . '/theme_info.json';
        $phpFile  = THEMES_PATH . $theme->folder . '/theme_info.php';
        if (is_file($jsonFile)) {
            $info = json_decode(file_get_contents($jsonFile), true) ?? [];
        } elseif (is_file($phpFile)) {
            $info = require $phpFile;
        } else {
            $info = [];
        }

        $service  = service('theme');

        $posted = $this->request->getPost('options') ?? [];
        foreach (array_keys($info['options'] ?? []) as $key) {
            $value = $posted[$key] ?? null;
            $service->saveThemeOption($id, $key, $value);
        }

        return redirect()->to("/admin/themes/{$id}/options")->with('success', lang('Admin.themeOptionsSaved'));
    }

    public function saveLicense()
    {
        $licenseKey     = trim($this->request->getPost('license_key') ?? '');
        $storeProductId = (int) ($this->request->getPost('store_product_id') ?? 0);

        if (! $licenseKey || ! $storeProductId) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.licenseKeyRequired'));
        }

        // Determine validation URL (Pubvana vs third-party)
        $theme     = (new ThemeModel())->where('store_product_id', $storeProductId)->first();
        $isPubvana = $theme && in_array(strtolower(trim($theme->author ?? '')), ['pubvana', 'pubvana_team'], true);
        $validateUrl = $isPubvana
            ? PUBVANA_DSTORE_API . 'license/validate'
            : ($theme->license_validate_url ?? null);

        if (! $validateUrl) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.licenseCheckFailed'));
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
            return redirect()->to('/admin/themes')->with('error', lang('Admin.licenseCheckFailed'));
        }

        if (! $valid) {
            $error = $body['error'] ?? lang('Admin.licenseInvalid');
            return redirect()->to('/admin/themes')->with('error', $error);
        }

        // Upsert marketplace_licenses
        $licenseModel = new MarketplaceLicenseModel();
        $existing     = $licenseModel->where('store_product_id', $storeProductId)
            ->where('item_type', 'theme')
            ->first();

        $productName = $theme->name ?? 'Theme';

        $licenseData = [
            'store_product_id'     => $storeProductId,
            'product_name'         => $productName,
            'item_type'            => 'theme',
            'license_valid'        => 1,
            'license_last_checked' => date('Y-m-d H:i:s'),
            'author'               => $theme->author ?? '',
        ];

        if ($existing) {
            $licenseData['license_key'] = $licenseKey;
            $licenseModel->update($existing->id, $licenseData);
        } else {
            $licenseData['license_key'] = $licenseKey;
            $licenseModel->insert($licenseData);
        }

        return redirect()->to('/admin/themes')->with('success', lang('Admin.licenseSaved'));
    }
}
