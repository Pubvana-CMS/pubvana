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

        $this->render('pubvana/profiles/profile_edit', [
            'title'       => 'Edit Profile',
            'profileBase' => $this->profileBase(),
            'profile'     => $profile,
            'user'        => $user,
        ]);
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

        if ($this->app->profiles()->updateProfile((int) $user->id, $post) === null) {
            $this->app->session()->flash('danger', 'Website must be a full http:// or https:// URL.');
            $this->app->redirect($profileUrl . '/edit');
            return;
        }

        $this->app->redirect($profileUrl);
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
