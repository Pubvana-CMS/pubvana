<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Controllers;

use Pubvana\Controllers\Admin\AdminController;

class ProfilesAdminController extends AdminController
{
    public function index(): void
    {
        $user    = $this->app->auth()->user();
        if ($user === null) {
            $this->app->session()->flash('error', 'You must be signed in to view your profile.');
            $this->app->redirect('/login');
            return;
        }

        $profile = $this->app->profiles()->findOrCreate((int) $user->id);
        $avatarPicker = $this->app->media()->avatarPicker('avatar', $profile->avatar ?? '');

        $this->render('pubvana/profiles/admin/profile/index', [
            'pageTitle'    => 'My Profile',
            'profile'      => $profile,
            'user'         => $user,
            'avatarPicker' => $avatarPicker,
            'returnUrl'    => $this->adminBase(),
            'adminBase'    => $this->adminBase(),
        ]);
    }

    public function show(string $userId): void
    {
        $currentUser = $this->app->auth()->user();
        if ((int) ($currentUser?->id) !== (int) $userId && !$currentUser?->can('profile.edit.any')) {
            $this->app->session()->flash('error', 'You do not have permission to edit other users\' profiles.');
            $this->app->redirect('/admin/users');
            return;
        }

        // Resolve the target through the model's findById (null on a miss),
        // never find(). A bare find() returns an unhydrated instance, which
        // would send a bogus id into findOrCreate() and trip the user_id
        // foreign key. Null here means the user does not exist.
        $user = (new \Enlivenapp\FlightShield\Models\User($this->app->db()))
            ->findById((int) $userId);

        if ($user === null) {
            $this->app->session()->flash('error', 'User not found.');
            $this->app->redirect('/admin/users');
            return;
        }

        $profile    = $this->app->profiles()->findOrCreate((int) $userId);
        $avatarPicker = $this->app->media()->avatarPicker('avatar', $profile->avatar ?? '');

        $this->render('pubvana/profiles/admin/profile/index', [
            'pageTitle'    => 'Edit Profile — ' . htmlspecialchars((string) ($user->username ?? '')),
            'profile'      => $profile,
            'user'         => $user,
            'avatarPicker' => $avatarPicker,
            'returnUrl'    => '/admin/users/' . (int) $userId . '/edit',
            'adminBase'    => $this->adminBase(),
        ]);
    }

    public function update(string $userId): void
    {
        $currentUser = $this->app->auth()->user();
        if ((int) ($currentUser?->id) !== (int) $userId && !$currentUser?->can('profile.edit.any')) {
            $this->app->session()->flash('error', 'You do not have permission to edit other users\' profiles.');
            $this->app->redirect('/admin/users');
            return;
        }

        $post = $this->app->request()->data->getData();
        $postedReturn = isset($post['return_url']) ? (string) $post['return_url'] : null;
        unset($post['_csrf_token'], $post['return_url']);

        if ($this->app->profiles()->updateProfile((int) $userId, $post) === null) {
            $this->app->session()->flash('error', 'Website must be a full http:// or https:// URL.');
            $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->adminBase()));
            return;
        }

        $this->app->session()->flash('success', 'Profile updated.');
        $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->adminBase()));
    }

    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/profiles'), '/');
    }
}
