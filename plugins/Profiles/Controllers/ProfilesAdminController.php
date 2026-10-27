<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use Pubvana\Plugins\Profiles\Services\ProfileSecurityService;
use Pubvana\Plugins\Profiles\Services\ProfileTabService;

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

        $tabs = $this->tabsService()->tabs($user, $user, $this->requestedTab());

        $this->render('pubvana/profiles/admin/profile/index', [
            'pageTitle'    => 'My Profile',
            'profile'      => $profile,
            'user'         => $user,
            'avatarPicker' => $avatarPicker,
            'returnUrl'    => $this->adminBase(),
            'adminBase'    => $this->adminBase(),
            'profileUrl'   => $this->adminBase(),
            'tabs'         => $tabs,
            'security'     => $this->securityData($user),
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

        $isOwner = (int) ($currentUser?->id) === (int) $userId;
        $tabs = $this->tabsService()->tabs($user, $currentUser ?? $user, $this->requestedTab());

        $this->render('pubvana/profiles/admin/profile/index', [
            'pageTitle'    => 'Edit Profile: ' . htmlspecialchars((string) ($user->username ?? '')),
            'profile'      => $profile,
            'user'         => $user,
            'avatarPicker' => $avatarPicker,
            'returnUrl'    => '/admin/users/' . (int) $userId . '/edit',
            'adminBase'    => $this->adminBase(),
            'profileUrl'   => $this->adminBase() . '/' . (int) $userId,
            'tabs'         => $tabs,
            'security'     => $isOwner ? $this->securityData($user) : null,
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

    protected function tabsService(): ProfileTabService
    {
        return new ProfileTabService($this->app);
    }

    protected function securityService(): ProfileSecurityService
    {
        return new ProfileSecurityService($this->app);
    }

    // ── Security tab ────────────────────────────────────────────────

    /**
     * Change the signed-in user's own email address.
     *
     * Owner only. An admin editing someone else's profile never reaches
     * this action: the Security tab is not offered for another account.
     */
    public function updateCredentials(string $userId): void
    {
        $user = $this->requireSelf($userId);
        if ($user === null) {
            return;
        }

        $post = $this->app->request()->data->getData();
        $postedReturn = isset($post['return_url']) ? (string) $post['return_url'] : null;
        unset($post['_csrf_token'], $post['return_url']);

        $result = $this->securityService()->updateCredentials($user, $post);

        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?: 'Could not update the account.');
            $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->securityUrl($userId)));
            return;
        }

        $this->app->session()->flash('success', 'Account updated.');
        $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->securityUrl($userId)));
    }

    /**
     * Change the signed-in user's own password.
     */
    public function updatePassword(string $userId): void
    {
        $user = $this->requireSelf($userId);
        if ($user === null) {
            return;
        }

        $post = $this->app->request()->data->getData();
        $postedReturn = isset($post['return_url']) ? (string) $post['return_url'] : null;

        $result = $this->securityService()->updatePassword(
            $user,
            (string) ($post['current_password'] ?? ''),
            (string) ($post['password'] ?? ''),
            (string) ($post['password_confirm'] ?? '')
        );

        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?: 'Could not change the password.');
            $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->securityUrl($userId)));
            return;
        }

        $this->app->session()->flash('success', 'Password changed. Other sessions were signed out.');
        $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->securityUrl($userId)));
    }

    /**
     * Sign out other devices.
     *
     * A posted session_id signs out that one row. No id signs out every
     * session except the one making the request.
     */
    public function revokeSessions(string $userId): void
    {
        $user = $this->requireSelf($userId);
        if ($user === null) {
            return;
        }

        $post = $this->app->request()->data->getData();
        $postedReturn = isset($post['return_url']) ? (string) $post['return_url'] : null;
        $sessionId = (int) ($post['session_id'] ?? 0);

        if ($sessionId > 0) {
            $revoked = $this->securityService()->revokeSession($user, $sessionId);
            $this->app->session()->flash(
                $revoked ? 'success' : 'error',
                $revoked ? 'Session signed out.' : 'That session is already gone or is the one you are using.'
            );
        } else {
            $count = $this->securityService()->revokeOtherSessions($user);
            $this->app->session()->flash('success', 'Signed out ' . $count . ' other session' . ($count === 1 ? '.' : 's.'));
        }

        $this->app->redirect($this->app->url()->sameSite($postedReturn, $this->securityUrl($userId)));
    }

    /**
     * The signed-in user, only when they are the profile owner.
     *
     * @return \Enlivenapp\FlightShield\Models\User|null Null after flashing and redirecting a non-owner
     */
    private function requireSelf(string $userId): ?object
    {
        $currentUser = $this->app->auth()->user();

        if ($currentUser === null || (int) $currentUser->id !== (int) $userId) {
            $this->app->session()->flash('error', 'You can only change your own security settings.');
            $this->app->redirect($this->adminBase());

            return null;
        }

        return $currentUser;
    }

    /**
     * Everything the Security tab renders for the owner.
     *
     * @param object $user Profile owner (a Shield User at runtime)
     *
     * @return array{email: ?string, two_factor: bool, sessions: list<array<string, mixed>>}
     */
    private function securityData(object $user): array
    {
        /** @var \Enlivenapp\FlightShield\Models\User $user */
        return [
            'email'      => $this->app->auth()->users()->getEmail($user),
            'two_factor' => $this->securityService()->twoFactorEnabled(),
            'sessions'   => $this->securityService()->sessions($user),
        ];
    }

    /**
     * The current ?tab= value, or an empty string for the first tab.
     */
    private function requestedTab(): string
    {
        return (string) ($this->app->request()->query->tab ?? '');
    }

    /**
     * The Security tab's own URL, used as the redirect fallback.
     */
    private function securityUrl(string $userId): string
    {
        return $this->adminBase() . '/' . (int) $userId . '?tab=' . \Pubvana\Plugins\Profiles\Services\ProfileTabService::CORE_SECURITY_TAB;
    }
}
