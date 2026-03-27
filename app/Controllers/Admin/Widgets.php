<?php

namespace App\Controllers\Admin;

use App\Models\WidgetAreaModel;
use App\Models\WidgetInstanceModel;
use App\Models\WidgetModel;
use App\Services\WidgetService;

class Widgets extends BaseAdminController
{
    public function areas(): string
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        (new WidgetService())->sync();

        $theme     = $this->themeService->getActive();
        $areaModel = new WidgetAreaModel();
        $areas     = $theme ? $areaModel->where('theme_id', $theme->id)->findAll() : [];

        // Fetch instances by slug so widgets carry over across themes
        $slugs = array_column((array) $areas, 'slug');
        $db = db_connect();
        $instances = $slugs ? $db->table('widget_instances wi')
            ->select('wi.*, w.name as widget_name, w.folder, wa.slug as area_slug')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->whereIn('wa.slug', $slugs)
            ->where('w.is_active', 1)
            ->orderBy('wi.sort_order', 'ASC')
            ->get()->getResultObject() : [];

        return $this->adminView('widgets/areas', array_merge($this->baseData('Widgets', 'widgets'), [
            'areas'     => $areas,
            'instances' => $instances,
            'available' => (new WidgetModel())->where('is_active', 1)->findAll(),
        ]));
    }

    public function addToArea()
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $areaId   = (int) $this->request->getPost('widget_area_id');
        $widgetId = (int) $this->request->getPost('widget_id');
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
        $instance = db_connect()->table('widget_instances wi')
            ->select('wa.slug')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();
        $slug = $instance ? $instance->slug : '';
        (new WidgetInstanceModel())->delete($instanceId);
        return redirect()->to('/admin/widgets#area-' . $slug)->with('success', lang('Admin.widgetRemoved'));
    }

    public function configure(int $instanceId): string
    {
        if (! auth()->user()->can('admin.widgets')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $db = db_connect();
        $instance = $db->table('widget_instances wi')
            ->select('wi.*, w.folder, w.name as widget_name')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();

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
        $instance = db_connect()->table('widget_instances wi')
            ->select('w.folder')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();

        if ($instance) {
            $manifest = (new WidgetService())->readManifest($instance->folder);
            if ($manifest) {
                foreach ($manifest['admin']['options'] ?? [] as $key => $cfg) {
                    if (($cfg['type'] ?? '') === 'checkbox' && ! isset($options[$key])) {
                        $options[$key] = '0';
                    }
                }
            }
        }

        (new WidgetInstanceModel())->update($instanceId, ['options_json' => json_encode($options)]);
        $instance = db_connect()->table('widget_instances wi')
            ->select('wa.slug')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();
        $slug = $instance ? $instance->slug : '';
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
}
