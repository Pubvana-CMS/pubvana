<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Redirects logged-in users with TOTP enabled to the 2FA verification page
 * if they haven't verified their TOTP code this session.
 */
class TotpFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        if (! auth()->loggedIn()) {
            return null;
        }

        if (session()->get('totp_2fa_verified')) {
            return null;
        }

        $totpEnabled = model(\App\Models\UserAdminModel::class)->isTotpEnabled(auth()->id());

        if ($totpEnabled) {
            session()->set('totp_redirect_url', current_url());
            return redirect()->to('/auth/2fa');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
