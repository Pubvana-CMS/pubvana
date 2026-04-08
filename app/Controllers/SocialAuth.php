<?php

namespace App\Controllers;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;

class SocialAuth extends BaseController
{
    protected array $supportedProviders = ['google', 'facebook'];

    public function redirect(string $provider)
    {
        $provider = strtolower($provider);
        if (! in_array($provider, $this->supportedProviders, true)) {
            return redirect()->to('/login')->with('error', 'Unsupported OAuth provider.');
        }

        $oauthProvider = $this->makeProvider($provider);
        if (! $oauthProvider) {
            return redirect()->to('/login')->with('error', ucfirst($provider) . ' OAuth is not configured.');
        }

        $options = [];
        if ($provider === 'google') {
            $options['scope'] = ['email', 'profile'];
        } elseif ($provider === 'facebook') {
            $options['scope'] = ['email'];
        }

        $authUrl = $oauthProvider->getAuthorizationUrl($options);
        session()->set('oauth2state_' . $provider, $oauthProvider->getState());

        return redirect()->to($authUrl);
    }

    public function callback(string $provider)
    {
        $provider = strtolower($provider);
        if (! in_array($provider, $this->supportedProviders, true)) {
            return redirect()->to('/login')->with('error', 'Unsupported OAuth provider.');
        }

        $storedState = session()->get('oauth2state_' . $provider);
        $state       = $this->request->getGet('state');

        if (! $storedState || $state !== $storedState) {
            session()->remove('oauth2state_' . $provider);
            return redirect()->to('/login')->with('error', 'Invalid OAuth state. Please try again.');
        }
        session()->remove('oauth2state_' . $provider);

        $code = $this->request->getGet('code');
        if (! $code) {
            return redirect()->to('/login')->with('error', 'No authorization code received.');
        }

        $oauthProvider = $this->makeProvider($provider);
        if (! $oauthProvider) {
            return redirect()->to('/login')->with('error', ucfirst($provider) . ' OAuth is not configured.');
        }

        try {
            $token        = $oauthProvider->getAccessToken('authorization_code', ['code' => $code]);
            $resourceOwner = $oauthProvider->getResourceOwner($token);
        } catch (\Throwable $e) {
            log_message('error', 'SocialAuth callback error: ' . $e->getMessage());
            return redirect()->to('/login')->with('error', 'OAuth authentication failed. Please try again.');
        }

        $email          = $resourceOwner->getEmail();
        $providerUserId = (string) $resourceOwner->getId();
        $identityType   = 'oauth_' . $provider;

        if (! $email) {
            return redirect()->to('/login')->with('error', 'Could not retrieve email from ' . ucfirst($provider) . '.');
        }

        $identityModel = model(\CodeIgniter\Shield\Models\UserIdentityModel::class);

        // Check for existing OAuth identity
        $identity = $identityModel->where('type', $identityType)
            ->where('name', $providerUserId)
            ->first();

        if ($identity) {
            // Log them in via Shield
            $userModel = new \CodeIgniter\Shield\Models\UserModel();
            $user      = $userModel->findById($identity->user_id);
            if ($user) {
                auth()->login($user);
                return redirect()->to('/')->with('success', 'Welcome back!');
            }
        }

        // Check if a user with this email already exists (email_password identity)
        $existingUser = auth()->getProvider()
            ->findByCredentials(['email' => $email]);

        if ($existingUser) {
            // Link OAuth identity to existing account
            $userId = $existingUser->id;
        } else {
            // Create new Shield user
            $users = auth()->getProvider();

            $user = new \CodeIgniter\Shield\Entities\User([
                'username' => $this->uniqueUsername($email),
                'email'    => $email,
                'password' => bin2hex(random_bytes(16)),
                'active'   => 1,
            ]);
            $users->save($user);

            $user   = $users->findById($users->getInsertID());
            $userId = $user->id;
            $user->addGroup('subscriber');
        }

        // Store OAuth identity
        $identityModel->create([
            'user_id' => $userId,
            'type'    => $identityType,
            'name'    => $providerUserId,
            'secret'  => $providerUserId,
        ]);

        $userModel = new \CodeIgniter\Shield\Models\UserModel();
        $user      = $userModel->findById($userId);
        auth()->login($user);

        return redirect()->to('/')->with('success', 'Logged in via ' . ucfirst($provider) . '.');
    }

    protected function makeProvider(string $provider): ?object
    {
        $baseUrl     = rtrim(config('App')->baseURL, '/');
        $callbackUrl = $baseUrl . '/auth/social/' . $provider . '/callback';

        $googleId     = setting('Social.googleClientId') ?? '';
        $googleSecret = setting('Social.googleClientSecret') ?? '';
        $fbId         = setting('Social.facebookClientId') ?? '';
        $fbSecret     = setting('Social.facebookClientSecret') ?? '';

        return match ($provider) {
            'google' => ($googleId && $googleSecret)
                ? new Google([
                    'clientId'     => $googleId,
                    'clientSecret' => $googleSecret,
                    'redirectUri'  => $callbackUrl,
                ])
                : null,

            'facebook' => ($fbId && $fbSecret)
                ? new Facebook([
                    'clientId'        => $fbId,
                    'clientSecret'    => $fbSecret,
                    'redirectUri'     => $callbackUrl,
                    'graphApiVersion' => 'v18.0',
                ])
                : null,

            default => null,
        };
    }

    protected function uniqueUsername(string $email): string
    {
        return model(\App\Models\UserAdminModel::class)
            ->uniqueUsername(strtolower(preg_replace('/[^a-z0-9_]/', '', explode('@', $email)[0])) ?: 'user');
    }
}
