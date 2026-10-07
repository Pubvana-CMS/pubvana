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

        try {
            $avatarPicker = $this->app->media()->avatarPicker(
                'avatar',
                (string) ($profile->avatar ?? ''),
                $this->adminBase() . '/' . (int) $user->id . '/avatar'
            );
        } catch (\Throwable) {
            // Media plugin disabled: the form renders without a picker.
            $avatarPicker = '';
        }

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

        $profile = $this->app->profiles()->findOrCreate((int) $userId);

        try {
            $avatarPicker = $this->app->media()->avatarPicker(
                'avatar',
                (string) ($profile->avatar ?? ''),
                $this->adminBase() . '/' . (int) $userId . '/avatar'
            );
        } catch (\Throwable) {
            // Media plugin disabled: the form renders without a picker.
            $avatarPicker = '';
        }

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

        $previousAvatar = (string) ($this->app->profiles()->findOrCreate((int) $userId)->avatar ?? '');

        $updated = $this->app->profiles()->updateProfile((int) $userId, $post);
        if ($updated === null) {
            $this->app->session()->flash('error', 'Website, Twitter, Facebook and LinkedIn must be full http:// or https:// URLs.');
            $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->adminBase()));
            return;
        }

        $this->removeUnusedAvatar((int) $userId, $previousAvatar, (string) ($updated->avatar ?? ''));

        $this->app->session()->flash('success', 'Profile updated.');
        $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->adminBase()));
    }

    /**
     * Accept an avatar upload from the admin profile form's picker. Stores
     * one file per user under the Media plugin's avatar directory; a new
     * upload replaces the old file. JSON response for the picker's fetch.
     */
    public function avatar(string $userId): void
    {
        $currentUser = $this->app->auth()->user();
        if ((int) ($currentUser?->id) !== (int) $userId && !$currentUser?->can('profile.edit.any')) {
            $this->app->halt(403, 'You do not have permission to edit this profile.');
            return;
        }

        $file = $this->app->request()->files->file ?? null;
        if (!$this->isUploadedFile($file)) {
            $this->app->json(['error' => 'No file uploaded or upload error.'], 400);
            return;
        }

        try {
            $path = $this->app->media()->storeAvatar((int) $userId, $file);
        } catch (\InvalidArgumentException $e) {
            $this->app->json(['error' => $e->getMessage()], 422);
            return;
        }

        // The file becomes live when the form is saved: update() removes the
        // files the row stops pointing at, so an abandoned form cannot leave
        // the row pointing at a deleted file.
        $url = '/' . ltrim($path, '/');
        $this->app->json(['success' => true, 'url' => $url, 'path' => $path]);
    }

    /**
     * Whether the request carried a complete, successful file upload.
     *
     * storeAvatar() reads tmp_name, size and name directly, so the shape is
     * checked here: a malformed entry becomes a 400, not an undefined key.
     *
     * @param mixed $file Raw value from the request's files collection
     * @return bool True when $file is a usable $_FILES entry
     */
    private function isUploadedFile(mixed $file): bool
    {
        if (!is_array($file)) {
            return false;
        }

        foreach (['name', 'tmp_name', 'error', 'size'] as $key) {
            if (!array_key_exists($key, $file)) {
                return false;
            }
        }

        return (int) $file['error'] === UPLOAD_ERR_OK
            && is_string($file['tmp_name'])
            && $file['tmp_name'] !== '';
    }

    /**
     * Delete the stored avatar files the profile row no longer points at.
     *
     * The row is the record of the live file, so this runs after a save. It
     * sweeps the user's other avatar files, which covers the previous file
     * and any upload replaced before the form was saved. A previous path
     * outside the avatar directory points into the media library, which the
     * sweep does not touch.
     */
    private function removeUnusedAvatar(int $userId, string $previous, string $current): void
    {
        try {
            $this->app->media()->sweepAvatars($userId, $current);

            if ($previous !== '' && $previous !== $current) {
                $this->app->media()->deleteLegacyAvatar($userId, $previous);
            }
        } catch (\Throwable) {
            // Media plugin disabled: no stored avatar to remove.
        }
    }

    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/profiles'), '/');
    }
}
