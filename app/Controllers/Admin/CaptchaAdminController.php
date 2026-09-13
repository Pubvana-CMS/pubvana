<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use flight\Engine;
use Pubvana\Services\SecretCipher;

/**
 * CaptchaAdminController - Site-wide captcha settings (Settings > Captcha).
 *
 * Thin MVC controller: index() renders the declared admin.settings fields
 * (slot 'captcha') plus one switch per registered captcha.area, save()
 * validates and stores the provider config, then hands the posted area
 * switches to CaptchaService::setProtectedAreas() (which drops any key
 * that is not a registered area). All captcha logic lives in the service.
 *
 * @package Pubvana\Controllers\Admin
 */
class CaptchaAdminController extends AdminController
{
    /** @var string One-shot flash key for save results */
    protected const FLASH_KEY = 'captcha_flash';

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana');
    }

    private SecretCipher|null $cipher = null;

    private function cipher(): SecretCipher
    {
        if ($this->cipher === null) {
            $this->cipher = new SecretCipher($this->app);
        }
        return $this->cipher;
    }

    /**
     * Captcha settings page: provider config + per-area switches.
     */
    public function index(): void
    {
        $settings = $this->app->settings();
        $fields = [];

        foreach ($this->app->adext()->get('admin.settings', 'captcha') as $contributor => $tab) {
            foreach (($tab['fields'] ?? []) as $field) {
                if (($field['type'] ?? '') === 'password') {
                    // Never echo the stored secret back into the page.
                    $field['value'] = '';
                } else {
                    $field['value'] = $settings->get((string) $field['key'], $field['default'] ?? null);
                }
                $field['options'] = (array) ($field['options'] ?? []);
                $fields[] = $field;
            }
        }

        $captcha = $this->app->captcha();
        $areas = [];
        foreach ($captcha->areas() as $key => $area) {
            $areas[(string) $key] = [
                'label'       => (string) ($area['label'] ?? $key),
                'description' => (string) ($area['description'] ?? ''),
                'enabled'     => $captcha->isProtected((string) $key),
            ];
        }

        $this->render('admin/captcha', [
            'pageTitle' => 'Captcha',
            'fields'    => $fields,
            'areas'     => $areas,
            'enabled'   => $captcha->isEnabled(),
            'flash'     => $this->app->session()->pullFlash(self::FLASH_KEY),
        ]);
    }

    /**
     * Save captcha settings.
     *
     * Provider is whitelisted against the declared select options, the
     * secret key stores only when something was typed (blank keeps the
     * current value), and the area switches go through the service so
     * unknown keys are dropped.
     */
    public function save(): void
    {
        $data = $this->app->request()->data->getData();
        $post = (array) ($data['settings'] ?? []);
        $settings = $this->app->settings();

        $provider = (string) ($post['Captcha.provider'] ?? 'none');
        if (!in_array($provider, ['none', 'hcaptcha', 'recaptcha'], true)) {
            $provider = 'none';
        }
        $settings->set('Captcha.provider', $provider);

        $siteKey = trim((string) ($post['Captcha.site_key'] ?? ''));
        $settings->set('Captcha.site_key', $siteKey);

        $secretKey = trim((string) ($post['Captcha.secret_key'] ?? ''));
        if ($secretKey !== '') {
            // Stored encrypted (same cipher as the Mail password), never
            // plaintext: a DB read must not hand out the provider secret.
            try {
                $settings->set('Captcha.secret_key', $this->cipher()->encrypt($secretKey));
            } catch (\Throwable $e) {
                error_log('Captcha save: secret encryption failed - ' . $e->getMessage());
                $this->app->session()->flash(self::FLASH_KEY, 'The secret key could not be encrypted and was not saved.');
                $this->app->redirect('/admin/captcha');
                return;
            }
        }

        $areas = is_array($data['areas'] ?? null) ? $data['areas'] : [];
        $this->app->captcha()->setProtectedAreas($areas);

        $configured = $this->app->captcha()->isEnabled();
        $message = 'Captcha settings saved.';
        if (!$configured) {
            $message .= ' Pick a provider and enter a site key to start enforcing the switches below.';
        }
        $this->app->session()->flash(self::FLASH_KEY, $message);
        $this->app->redirect('/admin/captcha');
    }
}
