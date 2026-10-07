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

    public function show(string $id): void
    {
        $user = $this->findUser($id);
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
            'title'         => ($profile->display_name ?? $user->username) . "'s Profile",
            'profileBase'   => $this->profileBase(),
            'profile'       => $profile,
            'user'          => $user,
            'isOwner'       => $isOwner,
            'avatar_url'    => $avatarUrl,
            'safe_website'  => $safeWebsite,
            'twitter_url'   => $this->safeUrl($profile->twitter ?? null),
            'facebook_url'  => $this->safeUrl($profile->facebook ?? null),
            'linkedin_url'  => $this->safeUrl($profile->linkedin ?? null),
        ]);
    }

    /**
     * A stored social URL renders only when it is a full safe http(s) URL.
     */
    private function safeUrl(?string $value): ?string
    {
        return UrlService::isSafeExternalUrl($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;
    }

    public function edit(string $id): void
    {
        $user = $this->findUser($id);
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

        try {
            $avatarPicker = $this->app->media()->publicAvatarPicker(
                'avatar',
                (string) ($profile->avatar ?? ''),
                $this->profileBase() . '/' . (int) $user->id . '/avatar'
            );
        } catch (\Throwable) {
            // Media plugin disabled: the form renders without a picker.
            $avatarPicker = '';
        }

        $this->render('pubvana/profiles/profile_edit', [
            'title'          => 'Edit Profile',
            'profileBase'    => $this->profileBase(),
            'profile'        => $profile,
            'user'           => $user,
            'avatarUploadUrl' => $this->profileBase() . '/' . (int) $user->id . '/avatar',
            'avatarPicker'   => $avatarPicker,
        ]);
    }

    /**
     * Accept an avatar upload from the owner's edit form. Stores one file
     * per user under the Media plugin's avatar directory; a new upload
     * replaces the old file. Returns JSON for the picker's fetch call.
     */
    public function avatar(string $id): void
    {
        $user = $this->findUser($id);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }

        if ((int) ($this->app->auth()->user()?->id) !== (int) $user->id) {
            $this->app->halt(403, 'You can only change your own avatar.');
            return;
        }

        $file = $this->app->request()->files->file ?? null;
        if (!$this->isUploadedFile($file)) {
            $this->app->json(['error' => 'No file uploaded or upload error.'], 400);
            return;
        }

        try {
            $path = $this->app->media()->storeAvatar((int) $user->id, $file);
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

    public function update(string $id): void
    {
        $user = $this->findUser($id);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }

        $profileUrl = $this->profileBase() . '/' . (int) $user->id;

        if ((int) ($this->app->auth()->user()?->id) !== (int) $user->id) {
            $this->app->session()->flash('danger', 'You can only edit your own profile.');
            $this->app->redirect($profileUrl);
            return;
        }

        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $previousAvatar = (string) ($this->app->profiles()->findOrCreate((int) $user->id)->avatar ?? '');

        $updated = $this->app->profiles()->updateProfile((int) $user->id, $post);
        if ($updated === null) {
            $this->app->session()->flash('danger', 'Website, Twitter, Facebook and LinkedIn must be full http:// or https:// URLs.');
            $this->app->redirect($profileUrl . '/edit');
            return;
        }

        $this->removeUnusedAvatar((int) $user->id, $previousAvatar, (string) ($updated->avatar ?? ''));

        $this->app->session()->flash('success', 'Profile updated.');
        $this->app->redirect($profileUrl);
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

    /**
     * Public profile URL base, e.g. '/profile'.
     */
    private function profileBase(): string
    {
        return '/' . trim($this->getRoutePrepend(), '/');
    }

    /**
     * Resolve the user a public profile URL points at.
     *
     * The id is the address, never the username: a username in a public URL
     * hands out account names for free. Soft-deleted accounts resolve too.
     * The profile row survives a soft delete, and posts the account wrote keep
     * their byline link, so the page it points at has to still answer. Only a
     * user with no row at all is a 404.
     */
    protected function findUser(string $id): ?User
    {
        $userId = (int) $id;
        if ($userId <= 0) {
            return null;
        }

        $user = new User($this->app->db());
        $user->eq('id', $userId)->find();

        return $user->isHydrated() ? $user : null;
    }
}
