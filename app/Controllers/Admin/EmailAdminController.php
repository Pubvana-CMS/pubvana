<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use flight\Engine;

/**
 * EmailAdminController - SMTP settings (Tools > Email).
 *
 * Thin MVC controller: index() renders the form and recent-sends list,
 * save() hands the posted array to the Mailer service (which validates,
 * coerces, and encrypts) and flashes the result, test() sends a probe via
 * the Mailer service and flashes its debug output. All SMTP/encryption
 * work lives in the service.
 *
 * @package Pubvana\Controllers\Admin
 */
class EmailAdminController extends AdminController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana');
    }

    /**
     * Email settings page: form + recent sends.
     */
    public function index(): void
    {
        $settings = $this->app->settings();
        $fields = [];

        foreach ($this->app->adext()->get('admin.settings', 'email') as $contributor => $tab) {
            foreach (($tab['fields'] ?? []) as $field) {
                if (($field['type'] ?? '') === 'password') {
                    $field['value'] = '';
                    $field['placeholder'] = 'Leave blank to keep the current password';
                } else {
                    $field['value'] = $settings->get($field['key'], $field['default'] ?? null);
                }
                $field['options'] = (array) ($field['options'] ?? []);
                $fields[] = $field;
            }
        }

        $mailer = $this->app->mailer();

        $this->render('admin/email', [
            'pageTitle' => 'Email',
            'fields'    => $fields,
            'recent'    => $mailer->recent(15),
            'sentCount' => (new \Pubvana\Models\Mail($this->app->db()))->countByStatus('sent'),
            'flash'     => $this->app->session()->pullFlash('email_flash'),
            'test'      => $this->app->session()->pullFlash('email_test'),
        ]);
    }

    /**
     * Save email settings via the Mailer service.
     */
    public function save(): void
    {
        $post = (array) ($this->app->request()->data->getData()['settings'] ?? []);
        $result = $this->app->mailer()->saveSettings($post);

        $message = $result['saved'] > 0
            ? 'Saved ' . $result['saved'] . ' setting' . ($result['saved'] === 1 ? '' : 's') . '.'
            : 'Nothing to save.';
        if (!empty($result['rejected'])) {
            $message .= ' Rejected: ' . implode(', ', $result['rejected']);
        }

        $this->app->session()->flash('email_flash', $message);
        $this->app->redirect('/admin/email');
    }

    /**
     * Send a probe message and flash the SMTP debug output.
     */
    public function test(): void
    {
        $to = $this->app->request()->data->test_to ?? null;
        $to = is_string($to) ? trim($to) : '';

        if ($to === '') {
            $this->app->session()->flash('email_flash', 'Enter a recipient address for the test email.');
            $this->app->redirect('/admin/email');
            return;
        }

        $result = $this->app->mailer()->test($to);
        $this->app->session()->flash('email_test', $result);
        $this->app->redirect('/admin/email');
    }
}