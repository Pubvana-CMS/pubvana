<?php

namespace App\Services;

use App\Libraries\TemplateEngine\Engine;
use App\Models\AdminNotificationModel;
use App\Models\MarketplaceLicenseModel;
use App\Services\VettingService;

class WidgetService
{
    private array $manifestCache = [];

    public function discover(): array
    {
        $widgets = [];
        foreach (glob(WIDGETS_PATH . '*', GLOB_ONLYDIR) as $dir) {
            $folder   = basename($dir);
            $jsonFile = $dir . '/widget_info.json';
            if (! is_file($jsonFile)) {
                continue;
            }

            $info = json_decode(file_get_contents($jsonFile), true);

            $disabledReason = null;

            if (! is_array($info)) {
                $disabledReason = lang('Admin.addonDisabledInvalidJson', [$folder, 'widget_info.json']);
                $info = [];
            } else {
                $required = ['name', 'version', 'description', 'author'];
                $missing  = array_diff($required, array_keys($info));
                if (! empty($missing)) {
                    $disabledReason = lang('Admin.addonDisabledMissingFields', [$folder, implode(', ', $missing)]);
                }
            }

            // Reject widgets containing PHP files (widgets are JSON + templates only)
            if ($disabledReason === null) {
                $phpFiles = glob($dir . '/*.php');
                if (! empty($phpFiles)) {
                    $disabledReason = lang('Admin.addonDisabledPhpFiles', [$folder]);
                }
            }

            $info['folder']           = $folder;
            $info['_disabled_reason'] = $disabledReason;
            $widgets[] = $info;
        }
        return $widgets;
    }

    public function sync(): void
    {
        $widgetModel = model(\App\Models\WidgetModel::class);
        $hasNew = false;

        // Remove orphaned records — widget folder deleted from disk
        $registered = $widgetModel->findAll();
        foreach ($registered as $row) {
            if (! is_dir(WIDGETS_PATH . $row->folder)) {
                $widgetModel->delete($row->id);
            }
        }

        foreach ($this->discover() as $info) {
            $folder         = $info['folder'];
            $disabledReason = $info['_disabled_reason'];
            $exists         = $widgetModel->where('folder', $folder)->first();

            $metaFields = [
                // Flags
                'bundled'             => ! empty($info['bundled']) ? 1 : 0,
                'free'                => ! empty($info['free'])    ? 1 : 0,
                // Support & store URLs
                'support_url'         => $info['support_url']         ?? null,
                'author_url'          => $info['author_url']          ?? null,
                'items_url'           => $info['items_url']           ?? null,
                'item_url'            => $info['item_url']            ?? null,
                'store_url'           => $info['store_url']           ?? null,
                // Category URLs
                'categories_url'      => $info['categories_url']      ?? null,
                'categories_all_url'  => $info['categories_all_url']  ?? null,
                'category_url'        => $info['category_url']        ?? null,
                // Discovery URLs
                'featured_url'        => $info['featured_url']        ?? null,
                // License URLs
                'license_validate_url' => $info['license_validate_url'] ?? null,
                'license_check_url'   => $info['license_check_url']   ?? null,
                // Update URLs
                'update_url'          => $info['update_url']          ?? null,
                'update_check_url'    => $info['update_check_url']    ?? null,
                'download_url'        => $info['download_url']        ?? null,
            ];

            // ── Disabled → always force inactive ────────────────────────
            if ($disabledReason !== null) {
                if ($exists) {
                    $widgetModel->update($exists->id, array_merge([
                        'is_active'       => 0,
                        'disabled'        => 1,
                        'disabled_reason' => $disabledReason,
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ], $metaFields));
                } else {
                    $widgetModel->insert(array_merge([
                        'name'            => $info['name']        ?? $folder,
                        'folder'          => $folder,
                        'description'     => $info['description'] ?? '',
                        'version'         => $info['version']     ?? '0.0.0',
                        'author'          => VettingService::normalizeAuthor($info['author'] ?? ''),
                        'is_active'       => 0,
                        'disabled'        => 1,
                        'disabled_reason' => $disabledReason,
                        'created_at'      => date('Y-m-d H:i:s'),
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ], $metaFields));
                    $hasNew = true;
                }
                continue;
            }

            // ── Valid addon ─────────────────────────────────────────────
            if (! $exists) {
                $isPubvana = in_array(VettingService::normalizeAuthor($info['author'] ?? ''), ['pubvana', 'pubvana_team'], true);
                $isBundled = ! empty($info['bundled']);
                $isFree    = ! empty($info['free']);

                $initialActive = ($isBundled && $isPubvana) || ($isFree && ! $isPubvana) ? 1 : 0;

                $widgetModel->insert(array_merge([
                    'name'        => $info['name'],
                    'folder'      => $folder,
                    'description' => $info['description'],
                    'version'     => $info['version'],
                    'author'      => VettingService::normalizeAuthor($info['author'] ?? ''),
                    'is_active'   => $initialActive,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ], $metaFields));
                $hasNew = true;

                // For non-bundled, non-free widgets: resolve product ID inline and check license
                if (! $isBundled && ! $isFree) {
                    $newRow = $widgetModel->where('folder', $folder)->first();
                    if ($newRow) {
                        $resolved = false;
                        $slug = $newRow->folder;

                        if ($slug) {
                            if ($isPubvana) {
                                try {
                                    $client   = \Config\Services::curlrequest(['timeout' => 5]);
                                    $response = $client->get(PUBVANA_DSTORE_API . 'item/' . $slug, ['http_errors' => false]);
                                    if ($response->getStatusCode() === 200) {
                                        $body    = json_decode($response->getBody(), true);
                                        $storeId = (int) ($body['id'] ?? 0);
                                        if ($storeId) {
                                            $widgetModel->update($newRow->id, ['store_product_id' => $storeId]);
                                            $resolved = true;
                                        }
                                    }
                                } catch (\Throwable $e) {
                                    // Resolution failed — handled below
                                }
                            } elseif (! empty($newRow->item_url)) {
                                try {
                                    $client   = \Config\Services::curlrequest(['timeout' => 5]);
                                    $response = $client->get($newRow->item_url . '/' . $slug, ['http_errors' => false]);
                                    if ($response->getStatusCode() === 200) {
                                        $body    = json_decode($response->getBody(), true);
                                        $storeId = (int) ($body['id'] ?? 0);
                                        if ($storeId) {
                                            $widgetModel->update($newRow->id, ['store_product_id' => $storeId]);
                                            $resolved = true;
                                        }
                                    }
                                } catch (\Throwable $e) {
                                    // Resolution failed
                                }
                            }
                        }

                        if ($resolved) {
                            $newRow  = $widgetModel->where('folder', $folder)->first();
                            $license = (new MarketplaceLicenseModel())->where('store_product_id', $newRow->store_product_id)->first();
                            if ($license && (int) ($license->license_valid ?? -1) === 1) {
                                $widgetModel->updateByFolder($folder, ['is_active' => 1]);
                            }
                        } else {
                            $supportUrl = $info['support_url'] ?? null;
                            $message    = $supportUrl
                                ? lang('Admin.widgetValidationFailedLink', [$info['name'] ?? $folder, $supportUrl])
                                : lang('Admin.widgetValidationFailed', [$info['name'] ?? $folder]);

                            (new AdminNotificationModel())->insert([
                                'source'         => 'widget',
                                'source_name'    => $folder,
                                'severity'       => 'warning',
                                'message'        => $message,
                                'action_url'     => '',
                                'action_label'   => '',
                                'is_dismissable' => 1,
                            ]);
                        }
                    }
                }
            } else {
                $newVersion = $info['version'];
                $newAuthor  = VettingService::normalizeAuthor($info['author'] ?? '');
                $clearDisabled = [];
                if (! empty($exists->disabled)) {
                    $clearDisabled = ['disabled' => null, 'disabled_reason' => null];
                }

                if ($newVersion !== ($exists->version ?? '')) {
                    $widgetModel->update($exists->id, array_merge([
                        'version'     => $newVersion,
                        'name'        => $info['name'],
                        'description' => $info['description'],
                        'author'      => $newAuthor,
                        'pv_safe'     => null,
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ], $metaFields, $clearDisabled));
                    $hasNew = true;
                } elseif (($info['name'] ?? '') !== ($exists->name ?? '') || ($info['description'] ?? '') !== ($exists->description ?? '')) {
                    $widgetModel->update($exists->id, array_merge([
                        'name'        => $info['name'],
                        'description' => $info['description'],
                        'author'      => $newAuthor,
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ], $metaFields, $clearDisabled));
                } elseif ($newAuthor !== ($exists->author ?? '')) {
                    $widgetModel->update($exists->id, array_merge([
                        'author'     => $newAuthor,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], $metaFields, $clearDisabled));
                } elseif ($exists->support_url === null || $exists->author_url === null || ! empty($exists->disabled)) {
                    $widgetModel->update($exists->id, $metaFields);
                }

            }
        }

        if ($hasNew) {
            (new VettingService())->checkApproval();
        }
    }

    public function renderArea(string $slug): string
    {
        $instanceModel = model(\App\Models\WidgetInstanceModel::class);
        $widgetModel   = model(\App\Models\WidgetModel::class);
        $instances = $instanceModel->getForAreas([$slug]);

        $html = '';
        foreach ($instances as $instance) {
            $widgetRow = $widgetModel->where('folder', $instance->folder)->first();
            if ($widgetRow && $widgetRow->store_product_id) {
                $license = (new MarketplaceLicenseModel())->where('store_product_id', $widgetRow->store_product_id)->first();
                if ($license && (int) ($license->license_valid ?? -1) !== 1) {
                    continue; // Skip rendering - invalid license
                }
            }
            $html .= $this->renderWidget($instance->folder, $instance->options_json);
        }
        return $html;
    }

    public function renderWidget(string $folder, ?string $optionsJson): string
    {
        $manifest = $this->readManifest($folder);
        if (! $manifest) {
            return '';
        }

        // Merge defaults with saved options
        $defaults = $this->getDefaults($manifest);
        $saved = $optionsJson ? json_decode($optionsJson, true) : [];
        $merged = array_merge($defaults, $saved);

        // Resolve data providers
        $providerData = [];
        $providers = $manifest['output']['providers'] ?? [];
        if ($providers) {
            $providerData = (new WidgetDataService())->resolve($providers, $merged);
        }

        // Load theme cls_ overrides and resolved icon variables
        $themeClasses = $this->getThemeWidgetClasses();
        $themeIcons   = service('theme')->getThemeIconClasses();

        // Inject selected page-level context vars so widgets can react to page state
        $pageData    = service('theme')->getPageData();
        $pageContext = [];
        if (array_key_exists('paywall', $pageData)) {
            $pageContext['paywall'] = $pageData['paywall'];
        }

        // Render template — theme classes, icons, saved options, provider data, and page context merged
        $template = $manifest['output']['template'] ?? 'widget.tpl';
        $tplPath = WIDGETS_PATH . $folder . '/views/' . $template;
        $basePath = WIDGETS_PATH . $folder . '/views/';
        $engine = new Engine();

        return $engine->render($tplPath, array_merge($pageContext, $themeClasses, $themeIcons, $merged, $providerData), $basePath);
    }

    public function renderAdminForm(string $folder, array $savedOptions = []): string
    {
        $manifest = $this->readManifest($folder);
        if (! $manifest) {
            return '';
        }

        $admin = $manifest['admin'] ?? [];
        $options = $admin['options'] ?? [];
        $notice = $admin['notice'] ?? '';
        $defaults = $this->getDefaults($manifest);
        $merged = array_merge($defaults, $savedOptions);

        $html = '';

        // Render notice if present
        if ($notice !== '') {
            $html .= '<div class="alert alert-info mb-3">'
                   . '<i class="fas fa-info-circle mr-2"></i>'
                   . esc($notice)
                   . '</div>';
        }

        // Auto-generate form fields from options
        foreach ($options as $key => $cfg) {
            $type    = $cfg['type'] ?? 'text';
            $label   = $cfg['label'] ?? $key;
            $value   = $merged[$key] ?? '';
            $fieldId = 'widget-opt-' . $key;

            $html .= match ($type) {
                'checkbox' => $this->renderCheckbox($key, $label, $value, $fieldId),
                'textarea' => $this->renderTextarea($key, $label, $value, $fieldId),
                'number'   => $this->renderInput($key, $label, $value, $fieldId, 'number'),
                'select'   => $this->renderSelect($key, $label, $value, $fieldId, $cfg['choices'] ?? []),
                default    => $this->renderInput($key, $label, $value, $fieldId, 'text'),
            };
        }

        return $html;
    }

    public function readManifest(string $folder): ?array
    {
        if (isset($this->manifestCache[$folder])) {
            return $this->manifestCache[$folder];
        }

        $path = WIDGETS_PATH . $folder . '/widget_info.json';
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data)) {
            return null;
        }

        $this->manifestCache[$folder] = $data;
        return $data;
    }

    private function getThemeWidgetClasses(): array
    {
        static $classes = null;
        if ($classes !== null) {
            return $classes;
        }

        $classes = [];
        $themeService = service('theme');
        $theme = $themeService->getActive();
        if (! $theme) {
            return $classes;
        }

        $jsonPath = THEMES_PATH . $theme->folder . '/theme_info.json';
        if (! is_file($jsonPath)) {
            return $classes;
        }

        $info = json_decode(file_get_contents($jsonPath), true);
        $classes = $info['css_class_mapping'] ?? [];
        return $classes;
    }

    private function getDefaults(array $manifest): array
    {
        $defaults = [];
        foreach ($manifest['admin']['options'] ?? [] as $key => $cfg) {
            $defaults[$key] = $cfg['default'] ?? '';
        }
        return $defaults;
    }

    private function renderInput(string $key, string $label, string $value, string $id, string $type): string
    {
        $escaped = esc($value);
        return '<div class="mb-3">'
             . '<label class="form-label" for="' . $id . '">' . esc($label) . '</label>'
             . '<input type="' . $type . '" name="options[' . $key . ']" id="' . $id . '" class="form-control" value="' . $escaped . '">'
             . '</div>';
    }

    private function renderTextarea(string $key, string $label, string $value, string $id): string
    {
        return '<div class="mb-3">'
             . '<label class="form-label" for="' . $id . '">' . esc($label) . '</label>'
             . '<textarea name="options[' . $key . ']" id="' . $id . '" class="form-control" rows="6">' . esc($value) . '</textarea>'
             . '</div>';
    }

    private function renderCheckbox(string $key, string $label, string $value, string $id): string
    {
        $checked = ! empty($value) ? ' checked' : '';
        return '<div class="mb-3 form-check">'
             . '<input type="checkbox" name="options[' . $key . ']" id="' . $id . '" class="form-check-input" value="1"' . $checked . '>'
             . '<label class="form-check-label" for="' . $id . '">' . esc($label) . '</label>'
             . '</div>';
    }

    private function renderSelect(string $key, string $label, string $value, string $id, array $choices): string
    {
        $html = '<div class="mb-3">'
              . '<label class="form-label" for="' . $id . '">' . esc($label) . '</label>'
              . '<select name="options[' . $key . ']" id="' . $id . '" class="form-control">';
        foreach ($choices as $val => $text) {
            $selected = ($value === (string) $val) ? ' selected' : '';
            $html .= '<option value="' . esc($val) . '"' . $selected . '>' . esc($text) . '</option>';
        }
        $html .= '</select></div>';
        return $html;
    }
}
