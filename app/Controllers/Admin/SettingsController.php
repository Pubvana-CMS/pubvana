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
        $settings = $this->app->settings();
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
                $normalized['value'] = $settings->get(
                    $normalized['key'],
                    $normalized['default'] ?? null
                );
                $fields[] = $normalized;
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
     * Lazily resolve a select field's options.
     *
     * Fields whose options are derived data (the homepage page selector)
     * declare an empty list and get filled here, only when the admin
     * Settings form renders or saves. Public requests never reach this.
     *
     * @param array<string, mixed> $field Field declaration (mutated in place)
     */
    protected function resolveOptions(array &$field): void
    {
        if ($field['type'] !== 'select' || !empty($field['options'])) {
            return;
        }
        if (($field['key'] ?? '') === 'CMS.homepagePageId') {
            $field['options'] = $this->app->pages()->publishedOptions();
        }
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
