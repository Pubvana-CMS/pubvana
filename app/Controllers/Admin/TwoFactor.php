<?php

namespace App\Controllers\Admin;

use OTPHP\TOTP;

class TwoFactor extends BaseAdminController
{
    /**
     * Show QR code setup page for the current user.
     */
    public function setup(): string
    {
        $userId = auth()->id();
        $user   = auth()->user();

        // Generate a fresh secret each time setup is loaded
        $totp = TOTP::generate();
        $totp->setLabel($user->email ?? ($user->username ?? 'user'));
        $totp->setIssuer(setting('App.siteName') ?? 'Pubvana');

        $secret = $totp->getSecret();
        $uri    = $totp->getProvisioningUri();

        // Store temporarily in session until confirmed
        session()->set('totp_temp_secret', $secret);

        return $this->adminView('twofactor/setup', array_merge(
            $this->baseData('2FA Setup', 'users'),
            [
                'provisioning_uri' => $uri,
                'secret'           => $secret,
                'user_id'          => $userId,
            ]
        ));
    }

    /**
     * Validate the first code and save the secret.
     */
    public function confirm()
    {
        $userId = auth()->id();
        $secret = session()->get('totp_temp_secret');

        if (! $secret) {
            return redirect()->to('/admin/users/2fa/setup')
                ->with('error', lang('Admin.tfaSessionExpired'));
        }

        $code = trim($this->request->getPost('totp_code') ?? '');
        $totp = TOTP::createFromSecret($secret);

        if (! $totp->verify($code, null, 1)) {
            return redirect()->to('/admin/users/2fa/setup')
                ->with('error', lang('Admin.tfaInvalidCode'));
        }

        $userAdmin = model(\App\Models\UserAdminModel::class);
        $userAdmin->enableTotp($userId, $secret);

        session()->remove('totp_temp_secret');
        // Mark as verified so the filter doesn't immediately challenge the user
        session()->set('totp_2fa_verified', true);

        return redirect()->to('/admin/users/' . $userId . '/profile')
            ->with('success', lang('Admin.tfaEnabled'));
    }

    /**
     * Disable TOTP after verifying the current code.
     */
    public function disable()
    {
        $userId = auth()->id();

        $userAdmin = model(\App\Models\UserAdminModel::class);
        $row = $userAdmin->getTotpInfo($userId);

        if (! $row || ! $row->totp_enabled) {
            return redirect()->to('/admin/users/' . $userId . '/profile')
                ->with('error', lang('Admin.tfaNotEnabled'));
        }

        $code = trim($this->request->getPost('totp_code') ?? '');
        $totp = TOTP::createFromSecret($row->totp_secret);

        if (! $totp->verify($code, null, 1)) {
            return redirect()->to('/admin/users/' . $userId . '/profile')
                ->with('error', lang('Admin.tfaInvalidDisable'));
        }

        $userAdmin->disableTotp($userId);

        session()->remove('totp_2fa_verified');

        return redirect()->to('/admin/users/' . $userId . '/profile')
            ->with('success', lang('Admin.tfaDisabled'));
    }
}
