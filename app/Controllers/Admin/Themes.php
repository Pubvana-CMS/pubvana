<?php

namespace App\Controllers\Admin;

use App\Models\ThemeModel;
use App\Services\ActivityLogger;
use App\Services\ThemeService;

class Themes extends BaseAdminController
{
    public function index(): string
    {
        $themeService = new ThemeService();
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

        $service = new ThemeService();

        if (! $service->validateTheme($theme->folder)) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.themeValidationFailed'));
        }

        $ok = $service->activate($id);
        if (! $ok) {
            return redirect()->to('/admin/themes')->with('error', lang('Admin.themeInvalidLicense'));
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

        $service = new ThemeService();
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

        $service  = new ThemeService();

        $posted = $this->request->getPost('options') ?? [];
        foreach (array_keys($info['options'] ?? []) as $key) {
            $value = $posted[$key] ?? null;
            $service->saveThemeOption($id, $key, $value);
        }

        return redirect()->to("/admin/themes/{$id}/options")->with('success', lang('Admin.themeOptionsSaved'));
    }
}
