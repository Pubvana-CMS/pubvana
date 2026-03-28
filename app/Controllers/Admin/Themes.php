<?php

namespace App\Controllers\Admin;

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

        return $this->adminView('themes/index', array_merge($this->baseData('Themes', 'themes'), [
            'themes'     => $themes,
            'validation' => $themeService->getValidationResults(),
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

        $ok = $service->activate($id);
        if (! $ok) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.themeInvalidLicense'));
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
}
