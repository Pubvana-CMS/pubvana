<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Controllers;

use Pubvana\Controllers\Public\PublicController;
use Pubvana\Services\UrlService;
use Enlivenapp\FlightShield\Models\User;

class ProfilesPublicController extends PublicController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.profiles');
    }

    public function show(string $username): void
    {
        $user = $this->findUserByUsername($username);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }

        $profile = $this->app->profiles()->findOrCreate((int) $user->id);

        $auth = $this->app->auth();
        $isOwner = $auth->loggedIn() && (int) ($auth->user()?->id) === (int) $user->id;

        $avatarUrl = '';
        if (!empty($profile->avatar)) {
            $avatarUrl = '/' . ltrim($profile->avatar, '/');
        }

        // Render guard: only a full http(s) URL becomes a navigable href.
        // Legacy rows stored before scheme validation may still hold
        // javascript: or other junk; those render as no link at all.
        $safeWebsite = UrlService::isSafeExternalUrl($profile->website ?? null)
            ? ($profile->website ?? null)
            : null;

        $this->render('pubvana/profiles/profile', [
            'title'        => ($profile->display_name ?? $user->username) . "'s Profile",
            'profile'      => $profile,
            'user'         => $user,
            'isOwner'      => $isOwner,
            'avatar_url'   => $avatarUrl,
            'safe_website' => $safeWebsite,
        ]);
    }

    public function edit(string $username): void
    {
        $user = $this->findUserByUsername($username);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }
        if ((int) ($this->app->auth()->user()?->id) !== (int) $user->id) {
            $this->app->session()->flash('danger', 'You can only edit your own profile.');
            $this->app->redirect('/');
            return;
        }

        $profile = $this->app->profiles()->findOrCreate((int) $user->id);

        $this->render('pubvana/profiles/profile_edit', [
            'title'   => 'Edit Profile',
            'profile' => $profile,
            'user'    => $user,
        ]);
    }

    public function update(string $username): void
    {
        $user = $this->findUserByUsername($username);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }
        if ((int) ($this->app->auth()->user()?->id) !== (int) $user->id) {
            $this->app->session()->flash('danger', 'You can only edit your own profile.');
            $this->app->redirect('/' . $this->getRoutePrepend() . '/' . $username);
            return;
        }

        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        if ($this->app->profiles()->updateProfile((int) $user->id, $post) === null) {
            $this->app->session()->flash('danger', 'Website must be a full http:// or https:// URL.');
            $this->app->redirect('/' . $this->getRoutePrepend() . '/' . $username . '/edit');
            return;
        }

        $this->app->redirect('/' . $this->getRoutePrepend() . '/' . $username);
    }

    protected function findUserByUsername(string $username): ?User
    {
        $userModel = new User($this->app->db());
        return $userModel->findByCredentials(['username' => $username]);
    }
}
