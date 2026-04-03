<?php

namespace App\Services;

class HCaptchaService
{
    public function verify(string $token): bool
    {
        $secretKey = setting('App.hcaptchaSecretKey') ?? '';
        if (ENVIRONMENT === 'testing' || $secretKey === '') {
            return true; // skip in dev/test if no key configured
        }
        $client   = \Config\Services::curlrequest();
        $response = $client->post('https://hcaptcha.com/siteverify', [
            'form_params' => [
                'secret'   => $secretKey,
                'response' => $token,
            ],
        ]);
        $body = json_decode($response->getBody(), true);
        return $body['success'] ?? false;
    }
}
