<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use flight\Engine;

/**
 * SettingsController - General settings page (Settings > General).
 *
 * Renders one tab per admin.settings contribution. Tabs are sorted by
 * priority; every tab's fields live inside a SINGLE form so saving
 * writes all tabs atomically regardless of which tab is visible
 * (inactive panes stay in the DOM, hidden via Alpine).
 *
 * Security model:
 *   - save() whitelists against declared fields ONLY. A posted key that
 *     is not declared in adext is rejected and logged - undeclared keys
 *     (secrets, deployment-level values) can never enter the store.
 *   - Values coerce per the declaration's field type, so a number stays
 *     an int, a checkbox becomes a real bool.
 *
 * @package Pubvana\Controllers\Admin
 */
class SettingsController extends AdminController
{
    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana');
    }

    /**
     * Show the tabbed General settings page.
     */
    public function general(): void
    {
        $this->render('admin/settings/general', [
            'pageTitle' => 'Settings',
            'tabs'      => $this->tabs(),
            'saved'     => ($this->app->request()->query->saved ?? null) === '1',
            'flash'     => $this->app->session()->pullFlash('settings_flash'),
        ]);
    }

    /**
     * Save all declared fields from the single form.
     *
     * Whitelist: only keys declared across all tabs are accepted.
     * Coercion follows each field's declared type. Redirects back to
     * the page with a flash message; nothing is written when every
     * posted key was rejected.
     */
    public function save(): void
    {
        // Form fields post as settings[FULL.KEY] - the array wrapper keeps
        // PHP from mangling dots in top-level keys ($_POST['CMS.siteName']
        // would arrive as CMS_siteName).
        $post = (array) ($this->app->request()->data->getData()['settings'] ?? []);
        $declared = $this->app->settings()->declaredFields();
        $saved = 0;
        $rejected = [];

        foreach ($declared as $key => $field) {
            // Checkbox fields post nothing when unchecked - absent means false
            if (!array_key_exists($key, $post)) {
                if (($field['type'] ?? '') === 'checkbox') {
                    $this->app->settings()->set($key, false);
                    $saved++;
                }
                continue;
            }

            try {
                $this->app->settings()->set($key, $this->coerce($field, $post[$key]));
                $saved++;
            } catch (\Throwable $e) {
                error_log('SettingsController: rejected "' . $key . '" - ' . $e->getMessage());
                $rejected[] = $field['label'] ?? $key;
            }
        }

        foreach (array_keys($post) as $posted) {
            if (!isset($declared[$posted])) {
                error_log("SettingsController: dropped undeclared key '{$posted}'");
            }
        }

        $message = $saved > 0
            ? "Saved {$saved} setting" . ($saved === 1 ? '' : 's') . '.'
            : 'Nothing to save.';
        if (!empty($rejected)) {
            $message .= ' Rejected: ' . implode(', ', $rejected);
        }
        $this->app->session()->flash('settings_flash', $message);

        $this->app->redirect('/admin/settings');
    }

    // -----------------------------------------------------------------
    // Internal Helpers
    // -----------------------------------------------------------------

    /**
     * Collect tabs with resolved values for their fields.
     *
     * @return array<int, array{label: string, description: string, fields: list<array<string, mixed>>}>
     */
    protected function tabs(): array
    {
        $tabs = $this->app->adext()->get('admin.settings', 'general');

        $out = [];
        foreach ($tabs as $tab) {
            $fields = [];
            foreach ($tab['fields'] ?? [] as $field) {
                $normalized = $this->normalizeField($field);
                if ($normalized === null) {
                    continue;
                }
                $this->resolveOptions($normalized);

                // A provider select carries the fields its providers own,
                // such as the homepage page picker that belongs to Pages.
                // They render directly under the select that lists them.
                $renderable = array_merge([$normalized], $normalized['provider_fields'] ?? []);
                foreach ($renderable as $one) {
                    $fields[] = $this->withValue($one);
                }
            }

            if (empty($fields)) {
                continue;
            }

            $out[] = [
                'label'       => $tab['label'],
                'description' => $tab['description'] ?? '',
                'fields'      => $fields,
            ];
        }

        return $out;
    }

    /**
     * Light re-validation of a declared field for rendering.
     *
     * The service already validates on read/save; this guards the view
     * against malformed declarations slipping between checks.
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>|null
     */
    protected function normalizeField(array $field): ?array
    {
        foreach (['key', 'label', 'type'] as $required) {
            if (!isset($field[$required]) || $field[$required] === '') {
                return null;
            }
        }
        if (!in_array($field['type'], \Pubvana\Services\SettingsService::FIELD_TYPES, true)) {
            return null;
        }
        return $field;
    }

    /**
     * Lazily resolve a select field's options, and expand provider selects.
     *
     * Two derived sources exist, and both defer to render/save time so the
     * boot-time declaration scan never touches a table or the registry:
     *
     *   providers        options and extra fields come from a registry type
     *                    and slot. The Homepage select lists every plugin
     *                    registered as a homepage provider.
     *   options_callable a callable returning the id => label map, used by a
     *                    plugin that owns the data behind a selector.
     *
     * Public requests never reach this: the settings page and its save
     * handler are the only callers.
     *
     * @param array<string, mixed> $field Field declaration (mutated in place)
     */
    protected function resolveOptions(array &$field): void
    {
        if (($field['type'] ?? '') !== 'select') {
            return;
        }

        $providers = $field['providers'] ?? null;
        if (is_array($providers)) {
            $this->resolveProviders($field, $providers);
            return;
        }

        if (!empty($field['options'])) {
            return;
        }

        $callable = $field['options_callable'] ?? null;
        if (is_callable($callable)) {
            $field['options'] = (array) call_user_func($callable);
        }
    }

    /**
     * Build a select from a registry type/slot, and queue the fields its
     * contributors declare.
     *
     * Option keys are the contributors' tokens, which is what gets stored.
     * The first token becomes the default, because the field is not allowed
     * to name a provider core does not know about.
     *
     * With nothing registered the select cannot mean anything, so the field
     * carries an 'unavailable' notice instead and the view explains how to
     * fix it (enable a plugin that can serve the homepage).
     *
     * @param array<string, mixed> $field       Field declaration (mutated in place)
     * @param array<string, mixed> $declaration Registration source: type, slot
     */
    protected function resolveProviders(array &$field, array $declaration): void
    {
        $type = (string) ($declaration['type'] ?? '');
        $slot = (string) ($declaration['slot'] ?? '');

        $providers = ($type === '' || $slot === '')
            ? []
            : $this->app->adext()->get($type, $slot);

        if ($providers === []) {
            $field['options'] = [];
            $field['unavailable'] = [
                'message' => 'No plugin is currently registered that can serve the homepage.',
                'link'    => '/admin/plugins',
                'label'   => 'Open the plugin manager',
            ];
            return;
        }

        $options = [];
        $providerFields = [];

        foreach ($providers as $contributor => $provider) {
            $token = (string) ($provider['token'] ?? $contributor);
            $options[$token] = (string) ($provider['label'] ?? $token);

            foreach ($provider['fields'] ?? [] as $declared) {
                $normalized = $this->normalizeField($declared);
                if ($normalized === null) {
                    continue;
                }
                $this->resolveOptions($normalized);
                $providerFields[] = $normalized;
            }
        }

        $field['options'] = $options;
        $field['provider_fields'] = $providerFields;
        $field['default'] = $field['default'] ?? array_key_first($options);
    }

    /**
     * Attach the stored value, or the declared default, to a field.
     *
     * @param array<string, mixed> $field Normalized field declaration
     * @return array<string, mixed> Field with its 'value' key set for the view
     */
    protected function withValue(array $field): array
    {
        $field['value'] = $this->app->settings()->get(
            (string) $field['key'],
            $field['default'] ?? null
        );

        return $field;
    }

    /**
     * Coerce raw posted input to the declared field type.
     *
     * @param array<string, mixed> $field Declaration
     * @param mixed                $raw   Posted value (string or array)
     * @return mixed Properly typed value for storage
     * @throws \InvalidArgumentException When input fails validation
     */
    protected function coerce(array $field, mixed $raw): mixed
    {
        $value = is_string($raw) ? trim($raw) : $raw;

        switch ($field['type']) {
            case 'number':
                if (is_numeric($value)) {
                    return $value + 0; // preserves int vs float
                }
                throw new \InvalidArgumentException('not a number');

            case 'checkbox':
                return filter_var($value, FILTER_VALIDATE_BOOL);

            case 'email':
                $email = filter_var((string) $value, FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    throw new \InvalidArgumentException('not a valid email address');
                }
                return $email;

            case 'select':
                $this->resolveOptions($field);
                $options = array_map('strval', array_keys($field['options'] ?? []));
                if (!in_array((string) $value, $options, true)) {
                    throw new \InvalidArgumentException('value not in options');
                }
                return $value;

            default: // text, textarea
                return (string) $value;
        }
    }

}
