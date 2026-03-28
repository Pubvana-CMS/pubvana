<?php

namespace App\Controllers\Admin;

use App\Models\SocialModel;
use App\Services\IconService;
use App\Services\ThemeService;

class Social extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }

        $model = new SocialModel();
        $links = $model->orderBy('sort_order')->findAll();

        $themeService = new ThemeService();
        $activeTheme  = $themeService->getActive();
        $themeInfo    = [];

        if ($activeTheme) {
            $jsonPath = THEMES_PATH . $activeTheme->folder . '/theme_info.json';
            if (is_file($jsonPath)) {
                $themeInfo = json_decode(file_get_contents($jsonPath), true) ?? [];
            }
        }

        $iconNotice = '';

        $iconPack    = $themeInfo['icon_pack'] ?? '';
        $iconPackVer = $themeInfo['icon_pack_ver'] ?? '';

        if ($iconPack && $iconPackVer && ! empty($links)) {
            $expectedBase = IconService::getBaseClass($iconPack, $iconPackVer);

            if ($expectedBase) {
                $updated = false;

                foreach ($links as $link) {
                    if (! str_starts_with($link->icon, $expectedBase)) {
                        $platformKey = IconService::getPlatformFromIcon($link->icon);
                        if ($platformKey) {
                            $newIcon = IconService::getClass($platformKey, $iconPack, $iconPackVer);
                            $model->update($link->id, ['icon' => $newIcon]);
                            $link->icon = $newIcon;
                            $updated = true;
                        }
                    }
                }

                if ($updated) {
                    $iconNotice = 'It looks like the theme may have changed. Social link icons have been updated to match the current theme\'s icon pack.';
                }
            }
        }

        // Build FA6 display icons for admin preview (admin always runs FA6)
        $adminIcons = [];
        foreach ($links as $link) {
            $platformKey = IconService::getPlatformFromIcon($link->icon);
            $adminIcons[$link->id] = $platformKey
                ? IconService::getClass($platformKey, 'FontAwesome', '6')
                : $link->icon;
        }

        return $this->adminView('social/index', array_merge(
            $this->baseData('Social Links', 'social'),
            ['links' => $links, 'themeInfo' => $themeInfo, 'iconNotice' => $iconNotice, 'adminIcons' => $adminIcons]
        ));
    }

    public function store()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        if (! $this->validate(['platform' => 'required', 'url' => 'required|valid_url_strict'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        // Admin picker sends FA6 classes; convert to the active theme's icon pack
        $icon        = $this->request->getPost('icon') ?? 'fab fa-link';
        $platformKey = IconService::getPlatformFromIcon($icon);

        if ($platformKey) {
            $themeService = new ThemeService();
            $activeTheme  = $themeService->getActive();
            if ($activeTheme) {
                $jsonPath  = THEMES_PATH . $activeTheme->folder . '/theme_info.json';
                if (is_file($jsonPath)) {
                    $info = json_decode(file_get_contents($jsonPath), true) ?? [];
                    $pack = $info['icon_pack'] ?? '';
                    $ver  = $info['icon_pack_ver'] ?? '';
                    if ($pack && $ver) {
                        $icon = IconService::getClass($platformKey, $pack, $ver);
                    }
                }
            }
        }

        (new SocialModel())->insert([
            'platform'   => $this->request->getPost('platform'),
            'url'        => $this->request->getPost('url'),
            'icon'       => $icon,
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => 1,
        ]);
        return redirect()->to('/admin/social')->with('success', lang('Admin.socialLinkAdded'));
    }

    public function toggle(int $id)
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        $model = new SocialModel();
        $link  = $model->find($id);
        if ($link) {
            $model->update($id, ['is_active' => $link->is_active ? 0 : 1]);
        }
        return redirect()->to('/admin/social')->with('success', lang('Admin.socialLinkUpdated'));
    }

    public function delete(int $id)
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', lang('Admin.permissionDenied'));
        }
        (new SocialModel())->delete($id);
        return redirect()->to('/admin/social')->with('success', lang('Admin.socialLinkDeleted'));
    }
}
