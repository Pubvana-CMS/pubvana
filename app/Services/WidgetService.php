<?php

namespace App\Services;

use App\Libraries\TemplateEngine\Engine;

class WidgetService
{
    private array $manifestCache = [];

    public function discover(): array
    {
        $widgets = [];
        foreach (glob(WIDGETS_PATH . '*', GLOB_ONLYDIR) as $dir) {
            $jsonFile = $dir . '/widget_info.json';
            if (! is_file($jsonFile)) {
                continue;
            }

            $info = json_decode(file_get_contents($jsonFile), true);
            if (! is_array($info)) {
                continue;
            }

            $info['folder'] = basename($dir);
            $widgets[] = $info;
        }
        return $widgets;
    }

    public function sync(): void
    {
        $db = db_connect();
        foreach ($this->discover() as $info) {
            $exists = $db->table('widgets')->where('folder', $info['folder'])->countAllResults();
            if (! $exists) {
                $db->table('widgets')->insert([
                    'name'        => $info['name']        ?? $info['folder'],
                    'folder'      => $info['folder'],
                    'description' => $info['description'] ?? '',
                    'version'     => $info['version']     ?? '1.0.0',
                    'is_active'   => 1,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function renderArea(string $slug): string
    {
        $db = db_connect();
        $instances = $db->table('widget_instances wi')
            ->select('wi.*, w.folder, wa.slug AS area_slug')
            ->join('widget_areas wa', 'wa.id = wi.widget_area_id')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wa.slug', $slug)
            ->where('w.is_active', 1)
            ->orderBy('wi.sort_order', 'ASC')
            ->get()->getResultObject();

        $html = '';
        foreach ($instances as $instance) {
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

        // Render template
        $template = $manifest['output']['template'] ?? 'widget.tpl';
        $tplPath = WIDGETS_PATH . $folder . '/views/' . $template;
        $basePath = WIDGETS_PATH . $folder . '/views/';
        $engine = new Engine();

        return $engine->render($tplPath, array_merge($merged, $providerData), $basePath);
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

    private function readManifest(string $folder): ?array
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
