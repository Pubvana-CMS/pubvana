<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Controllers;

use Pubvana\Controllers\Public\PublicController;
use Pubvana\Services\PublicCsrf;
use flight\Engine;

class FormsPublicController extends PublicController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.forms');
    }

    public function submit(string $id): void
    {
        // A cached page carries a token the session does not hold, so this
        // route skips the core middleware and the check runs here.
        (new PublicCsrf($this->app))->verify();

        $form = $this->app->forms()->findForm((int) $id);

        if ($form === null || ($form->status ?? '') !== 'published') {
            $this->app->halt(404, 'Form not found');
            return;
        }

        $post = $this->app->request()->data->getData();
        $result = $this->app->forms()->submitForm($form, $post, [
            'ip_address' => $this->app->request()->getVar('REMOTE_ADDR'),
            'user_agent' => $this->app->request()->getHeader('User-Agent'),
            'referrer'   => $this->app->request()->getHeader('Referer'),
        ]);

        $returnUrl = $this->app->forms()->normalizeReturnUrl(
            $post['_return_url'] ?? null,
            $this->app->request()->getHeader('Referer')
        );

        $this->app->forms()->storeSubmissionFlash((int) $form->id, $result);
        $this->app->redirect($returnUrl);
    }
}
