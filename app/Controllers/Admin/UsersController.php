<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use Enlivenapp\FlightShield\Models\AuthGroup;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use Pubvana\Services\UserAdminService;

/**
 * UsersController - Admin CRUD for user management.
 *
 * Handles listing, creating, editing, deleting, and toggling
 * active status for users. All data access goes through
 * flight-shield's management API (auth()->users(), auth()->groups()).
 *
 * Strict MVC: this controller handles HTTP only. Views handle display.
 *
 * @package Pubvana\Controllers\Admin
 */
class UsersController extends AdminController
{
    /**
     * Superadmin visibility flag per Shield's convention:
     * superadmins see everyone, everyone else sees non-superadmins.
     */
    protected function viewerIsSuperadmin(): bool
    {
        return $this->app->auth()->user()?->inGroup('superadmin') ?? false;
    }

    /**
     * Whether the current viewer may act on a user account.
     *
     * A non-superadmin may manage ordinary users and their own account, but
     * not another admin's. Without this, one admin could reset another
     * admin's password (or ban or delete them) and take over the panel.
     *
     * @param string $targetId    Target user id
     * @param bool   $targetAdmin Whether the target is in the admin tier
     *                            (the admin or superadmin group)
     */
    protected function viewerMayManageUser(string $targetId, bool $targetAdmin): bool
    {
        if ($this->viewerIsSuperadmin()) {
            return true;
        }

        if ((string) $this->app->auth()->id() === $targetId) {
            return true;
        }

        return !$targetAdmin;
    }

    /**
     * User listing with pagination.
     *
     * Reads page number from query string, fetches paginated users,
     * and renders the index view with a table of all users.
     *
     * @return void
     */
    public function index(): void
    {
        $request = $this->app->request();
        $page = max(1, (int) ($request->query->page ?? 1));
        $perPage = 20;
        $includeSuperadmins = $this->viewerIsSuperadmin();

        $users = $this->app->auth()->users()->paginated($page, $perPage, $includeSuperadmins);
        $total = $this->app->auth()->users()->count($includeSuperadmins);

        $this->render('admin/users/index', [
            'pageTitle'  => 'Users',
            'users'      => $users,
            'pagination' => $this->app->pagination()->build(
                $page,
                $total,
                $perPage,
                static fn(int $n): string => '/admin/users?page=' . $n
            ),
        ]);
    }

    /**
     * Create user form.
     *
     * Shows a form with username, email, password, and group selection.
     *
     * @return void
     */
    public function create(): void
    {
        $this->render('admin/users/create', [
            'pageTitle' => 'New User',
            'groups'    => $this->visibleGroups(),
        ]);
    }

    /**
     * Store a new user.
     *
     * Creates the user through flight-shield (password validation,
     * duplicate-email guard, hashing, group assignment) and redirects
     * to the user listing. Superadmin assignment is gated by
     * UserAdminService.
     *
     * @return void
     */
    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $group = isset($post['group']) && $post['group'] !== '' ? (string) $post['group'] : null;

        $result = $this->userAdmin()->createUser(
            (string) ($post['username'] ?? ''),
            (string) ($post['email'] ?? ''),
            (string) ($post['password'] ?? ''),
            $group
        );

        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?: 'Failed to create user.');
            $this->app->redirect('/admin/users/create');
            return;
        }

        $this->app->session()->flash('success', 'User created.');
        $this->app->redirect('/admin/users');
    }

    /**
     * Invite a user to register.
     *
     * Takes an email address, validates that registration is available and
     * the address is not already registered, and emails an invitation
     * pointing to the public registration page.
     *
     * @return void
     */
    public function invite(): void
    {
        $email = trim((string) ($this->app->request()->data->email ?? ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->app->session()->flash('error', 'Enter a valid email address.');
            $this->app->redirect('/admin/users');
            return;
        }

        $shield = (array) ($this->app->get('enlivenapp.flight-shield') ?? []);
        if (!($shield['allow_registration'] ?? true)) {
            $this->app->session()->flash('error', 'Registration is disabled, so invitations cannot be sent.');
            $this->app->redirect('/admin/users');
            return;
        }

        $existing = (new UserIdentity($this->app->db()))
            ->getIdentityBySecret(UserIdentity::TYPE_EMAIL_PASSWORD, $email);
        if ($existing !== null) {
            $this->app->session()->flash('error', 'A user with that email address already exists.');
            $this->app->redirect('/admin/users');
            return;
        }

        $siteName = (string) $this->app->settings()->get('CMS.siteName');

        // SITE_URL is the only trustworthy origin for an emailed link; the
        // request Host header is never used, so there is no fallback here.
        if (trim((string) ($this->app->get('siteUrl') ?? '')) === '') {
            $this->app->session()->flash('error', 'SITE_URL is not set, so invitations cannot be sent.');
            $this->app->redirect('/admin/users');
            return;
        }

        $registerUrl = $this->app->url()->absoluteUrl('/auth/register');

        $bodyHtml = '<p>You have been invited to join ' . htmlspecialchars($siteName) . '.</p>'
            . '<p><a href="' . htmlspecialchars($registerUrl) . '">Create your account</a></p>'
            . '<p>If the link does not work, copy this address into your browser: '
            . htmlspecialchars($registerUrl) . '</p>';
        $alt = 'You have been invited to join ' . $siteName . ".\n\n"
            . 'Create your account here: ' . $registerUrl;

        try {
            $this->app->mailer()->sendHtml(
                $email,
                'You\'re invited to ' . $siteName,
                $bodyHtml,
                ['alt' => $alt]
            );
        } catch (\RuntimeException $e) {
            error_log('UsersController::invite - ' . $e->getMessage());
            $this->app->session()->flash('error', 'Failed to send the invitation. Check the mail settings.');
            $this->app->redirect('/admin/users');
            return;
        }

        $this->app->session()->flash('success', 'Invitation sent to ' . $email . '.');
        $this->app->redirect('/admin/users');
    }

    /**
     * Edit user form.
     *
     * Shows a form pre-filled with the user's current data,
     * including group membership and direct permissions.
     *
     * @param string $id User ID
     * @return void
     */
    public function edit(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        $groups = $this->visibleGroups();

        $this->render('admin/users/edit', [
            'pageTitle'       => 'Edit User',
            'editUser'        => $user,
            'groups'          => $groups,
            'userGroups'      => $user->getGroups(),
            'userPermissions' => $user->getPermissions(),
            'email'           => $this->app->auth()->users()->getEmail($user) ?? '',
            'banned'          => $user->isBanned(),
            'banMessage'      => $user->getBanMessage(),
            'requiresReset'   => $user->requiresPasswordReset(),
        ]);
    }

    /**
     * Groups the current viewer may assign. Superadmins see every group;
     * everyone else never sees the superadmin group, which they cannot
     * assign anyway.
     *
     * @return list<AuthGroup>
     */
    protected function visibleGroups(): array
    {
        $groups = $this->app->auth()->groups()->all();

        if ($this->viewerIsSuperadmin()) {
            return array_values($groups);
        }

        return array_values(array_filter(
            $groups,
            static fn (AuthGroup $group): bool => $group->alias !== 'superadmin'
        ));
    }

    /**
     * Update a user.
     *
     * Updates username, email, password (if provided), and group membership.
     * Only updates fields that are present in the POST data.
     *
     * @param string $id User ID
     * @return void
     */
    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        /** @var User|null $user */
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        if (!$this->viewerMayManageUser((string) $user->id, $user->inGroup('admin') || $user->inGroup('superadmin'))) {
            $this->app->session()->flash('error', 'Only a superadmin can manage another administrator.');
            $this->app->redirect('/admin/users');
            return;
        }

        // Group changes first: an unauthorized superadmin grant stops the
        // whole update (profile untouched) instead of slipping through.
        $rawGroups = $post['groups'] ?? [];
        $groups = is_array($rawGroups) ? array_values(array_filter($rawGroups, 'is_scalar')) : [];

        $sync = $this->userAdmin()->syncGroups($user, $groups);
        if (!$sync->isOK()) {
            $this->app->session()->flash('error', $sync->reason() ?: 'Group update denied.');
            $this->app->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $data = [];
        if (isset($post['username'])) {
            $data['username'] = $post['username'];
        }
        if (isset($post['email'])) {
            $data['email'] = $post['email'];
        }
        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }

        // Profile update may fail validation (e.g. weak password). Nothing
        // is written in that case, but the group sync above has already been
        // saved, so the failure must be reported instead of flashing success
        // over a partially applied update.
        $result = $this->app->auth()->users()->updateProfile($user, $data);
        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?: 'User could not be updated.');
            $this->app->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $this->app->session()->flash('success', 'User updated.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Soft-delete a user.
     *
     * Sets the deleted_at timestamp. The user record is preserved
     * but excluded from all future queries. Self-deletion and removal
     * of the last superadmin are refused (see UserAdminService).
     *
     * @param string $id User ID
     * @return void
     */
    public function delete(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        if (!$this->viewerMayManageUser((string) $user->id, $user->inGroup('admin') || $user->inGroup('superadmin'))) {
            $this->app->session()->flash('error', 'Only a superadmin can manage another administrator.');
            $this->app->redirect('/admin/users');
            return;
        }

        $result = $this->userAdmin()->deleteUser($user);
        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?: 'User could not be deleted.');
            $this->app->redirect('/admin/users');
            return;
        }

        $this->app->session()->flash('success', 'User deleted.');
        $this->app->redirect('/admin/users');
    }

    /**
     * Toggle user active status.
     *
     * Flips the active flag between 0 and 1. Active users can log in,
     * inactive users are blocked.
     *
     * @param string $id User ID
     * @return void
     */
    public function toggle(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        if (!$this->viewerMayManageUser((string) $user->id, $user->inGroup('admin') || $user->inGroup('superadmin'))) {
            $this->app->session()->flash('error', 'Only a superadmin can manage another administrator.');
            $this->app->redirect('/admin/users');
            return;
        }

        $result = $this->userAdmin()->setActive($user, !$user->active);
        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?: 'User status could not be changed.');
            $this->app->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $this->app->session()->flash('success', 'User status toggled.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Ban a user with an optional message.
     *
     * Banned users are rejected by Shield at login (and remember-me).
     * Admins cannot ban themselves.
     *
     * @param string $id User ID
     * @return void
     */
    public function ban(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        if (!$this->viewerMayManageUser((string) $user->id, $user->inGroup('admin') || $user->inGroup('superadmin'))) {
            $this->app->session()->flash('error', 'Only a superadmin can manage another administrator.');
            $this->app->redirect('/admin/users');
            return;
        }

        if ((string) $user->id === (string) $this->app->auth()->id()) {
            $this->app->session()->flash('error', 'You cannot ban your own account.');
            $this->app->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $message = trim((string) ($this->app->request()->data->ban_message ?? ''));

        $this->userAdmin()->ban($user, $message !== '' ? $message : null);

        $this->app->session()->flash('success', 'User banned.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Lift a ban.
     *
     * @param string $id User ID
     * @return void
     */
    public function unban(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user !== null && $this->viewerMayManageUser((string) $user->id, $user->inGroup('admin') || $user->inGroup('superadmin'))) {
            $this->userAdmin()->unBan($user);
        }

        $this->app->session()->flash('success', 'Ban lifted.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Toggle Shield's force password reset flag for a user.
     *
     * The self-toggle is refused for the same reason a self-ban is: the flag
     * locks the account out of everything until a new password is saved,
     * which would lock the admin out of their own session.
     *
     * @param string $id User ID
     * @return void
     */
    public function forceReset(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->session()->flash('error', 'That user no longer exists.');
            $this->app->redirect('/admin/users');
            return;
        }

        if (!$this->viewerMayManageUser((string) $user->id, $user->inGroup('admin') || $user->inGroup('superadmin'))) {
            $this->app->session()->flash('error', 'Only a superadmin can manage another administrator.');
            $this->app->redirect('/admin/users');
            return;
        }

        if ((string) $user->id === (string) $this->app->auth()->id()) {
            $this->app->session()->flash('error', 'You cannot require a password reset on your own account.');
            $this->app->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $this->userAdmin()->forceReset($user, !$user->requiresPasswordReset());

        $this->app->session()->flash('success', 'Password reset requirement updated.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Account status actions (ban/unban/force reset) via the local service.
     */
    protected function userAdmin(): UserAdminService
    {
        return new UserAdminService($this->app);
    }
}
