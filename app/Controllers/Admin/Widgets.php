<?php

namespace App\Controllers\Admin;

use App\Models\MarketplaceLicenseModel;
use App\Models\WidgetAreaModel;
use App\Models\WidgetInstanceModel;
use App\Models\WidgetModel;
use App\Services\MarketplaceService;
use App\Services\VettingService;
use App\Services\WidgetService;

class Widgets extends BaseAdminController
{
    public function areas(): string
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        (new WidgetService())->sync();

        $widgetModel = new WidgetModel();

        // Build license lookup keyed by store_product_id
        $allLicenses = (new MarketplaceLicenseModel())->where('item_type', 'widget')->findAll();
        $licenses    = [];
        foreach ($allLicenses as $lic) {
            $licenses[$lic->store_product_id] = $lic;
        }

        $theme     = $this->themeService->getActive();
        $areaModel = new WidgetAreaModel();
        $areas     = $theme ? $areaModel->where('theme_id', $theme->id)->findAll() : [];

        // Fetch instances by slug so widgets carry over across themes
        $slugs     = array_column((array) $areas, 'slug');
        $instanceModel = new WidgetInstanceModel();
        $instances = $instanceModel->getForAreas($slugs);

        return $this->adminView('widgets/areas', array_merge($this->baseData('Widgets', 'widgets'), [
            'areas'           => $areas,
            'instances'       => $instances,
            'available'       => $widgetModel->where('is_active', 1)->where('disabled IS NULL')->findAll(),
            'licenses'        => $licenses,
            'invalidLicenses' => array_filter(
                (new MarketplaceService())->getInvalidLicenses(),
                fn($l) => $l->item_type === 'widget'
            ),
        ]));
    }

    public function addToArea()
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $areaId   = (int) $this->request->getPost('widget_area_id');
        $widgetId = (int) $this->request->getPost('widget_id');

        $widget = (new WidgetModel())->find($widgetId);
        if (! $widget || ! empty($widget->disabled)) {
            return redirect()->to('/admin/widgets')->with('error', lang('Admin.activationBlockedDisabled', [$widget->name ?? 'Widget']));
        }

        if ((int) ($widget->pv_safe ?? -1) === 0) {
            return redirect()->to('/admin/widgets')->with('error', lang('Admin.widgetBlockedMalicious', [$widget->name ?? 'Widget']));
        }

        $model    = new WidgetInstanceModel();
        $model->insert([
            'widget_id'      => $widgetId,
            'widget_area_id' => $areaId,
            'sort_order'     => 999,
            'options_json'   => null,
        ]);
        $area = (new WidgetAreaModel())->find($areaId);
        $slug = $area ? $area->slug : '';
        return redirect()->to('/admin/widgets#area-' . $slug)->with('success', lang('Admin.widgetAdded'));
    }

    public function removeFromArea(int $instanceId)
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        // Get the area slug before deleting so we can redirect back to the right tab
        $instanceModel = new WidgetInstanceModel();
        $slug = $instanceModel->getAreaSlug($instanceId) ?? '';
        $instanceModel->delete($instanceId);
        return redirect()->to('/admin/widgets#area-' . $slug)->with('success', lang('Admin.widgetRemoved'));
    }

    public function configure(int $instanceId): string
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $instanceModel = new WidgetInstanceModel();
        $instance = $instanceModel->getWithWidget($instanceId);

        if (! $instance) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $options = $instance->options_json ? json_decode($instance->options_json, true) : [];
        $form    = (new \App\Services\WidgetService())->renderAdminForm($instance->folder, $options);

        return $this->adminView('widgets/configure', array_merge($this->baseData('Configure Widget', 'widgets'), [
            'instance' => $instance,
            'form'     => $form,
        ]));
    }

    public function saveConfig(int $instanceId)
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $options = $this->request->getPost('options') ?? [];

        // Ensure unchecked checkboxes are saved as "0" (HTML forms omit unchecked fields)
        $instanceModel = new WidgetInstanceModel();
        $folder = $instanceModel->getWidgetFolder($instanceId);

        if ($folder) {
            $manifest = (new WidgetService())->readManifest($folder);
            if ($manifest) {
                foreach ($manifest['admin']['options'] ?? [] as $key => $cfg) {
                    if (($cfg['type'] ?? '') === 'checkbox' && ! isset($options[$key])) {
                        $options[$key] = '0';
                    }
                }
            }
        }

        $instanceModel->update($instanceId, ['options_json' => json_encode($options)]);
        $slug = $instanceModel->getAreaSlug($instanceId) ?? '';
        return redirect()->to('/admin/widgets#area-' . $slug)->with('success', lang('Admin.widgetConfigured'));
    }

    public function reorder()
    {
        if (! auth()->user()->can('admin.widgets')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied.']);
        }
        $json = $this->request->getJSON(true);
        $order = $json['order'] ?? [];
        $model = new WidgetInstanceModel();
        foreach ($order as $i => $instanceId) {
            $model->update((int) $instanceId, ['sort_order' => $i]);
        }
        return $this->response->setJSON(['success' => true]);
    }

    public function saveLicense()
    {
        $licenseKey     = trim($this->request->getPost('license_key') ?? '');
        $storeProductId = (int) ($this->request->getPost('store_product_id') ?? 0);

        if (! $licenseKey || ! $storeProductId) {
            return redirect()->to('/admin/widgets')->with('error', lang('Admin.licenseKeyRequired'));
        }

        // Determine validation URL (Pubvana vs third-party)
        $widget    = (new WidgetModel())->where('store_product_id', $storeProductId)->first();
        $isPubvana = $widget && in_array(strtolower(trim($widget->author ?? '')), ['pubvana', 'pubvana_team'], true);
        $validateUrl = $isPubvana
            ? PUBVANA_DSTORE_API . 'license/validate'
            : ($widget->license_validate_url ?? null);

        if (! $validateUrl) {
            return redirect()->to('/admin/widgets')->with('error', lang('Admin.licenseCheckFailed'));
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
            return redirect()->to('/admin/widgets')->with('error', lang('Admin.licenseCheckFailed'));
        }

        if (! $valid) {
            $error = $body['error'] ?? lang('Admin.licenseInvalid');
            return redirect()->to('/admin/widgets')->with('error', $error);
        }

        // Upsert marketplace_licenses
        $licenseModel = new MarketplaceLicenseModel();
        $existing     = $licenseModel->where('store_product_id', $storeProductId)
            ->where('item_type', 'widget')
            ->first();

        $productName = $widget->name ?? 'Widget';

        $licenseData = [
            'store_product_id'     => $storeProductId,
            'product_name'         => $productName,
            'item_type'            => 'widget',
            'license_valid'        => 1,
            'license_last_checked' => date('Y-m-d H:i:s'),
            'author'               => $widget->author ?? '',
        ];

        if ($existing) {
            $licenseData['license_key'] = $licenseKey;
            $licenseModel->update($existing->id, $licenseData);
        } else {
            $licenseData['license_key'] = $licenseKey;
            $licenseModel->insert($licenseData);
        }

        return redirect()->to('/admin/widgets')->with('success', lang('Admin.licenseSaved'));
    }

    public function recheck(int $id)
    {
        $result = (new VettingService())->recheckItem('widget', $id);

        if ($result === null) {
            return redirect()->to('/admin/widgets')->with('error', lang('Admin.recheckFailed'));
        }

        return redirect()->to('/admin/widgets')->with('success', lang('Admin.recheckSuccess'));
    }
}
