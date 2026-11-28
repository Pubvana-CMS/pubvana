<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightShield\Models\AuthGroupPermission;
use Enlivenapp\FlightShield\Models\GroupUser;
use Enlivenapp\FlightShield\Models\RememberToken;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use Enlivenapp\FlightShield\Result;
use flight\Engine;
use Pubvana\Models\Session;

/**
 * UserAdminService - Account administration rules Shield's UserManagement
 * does not enforce: ban, unban, forced password reset, and the superadmin
 * safeguards around creating users, syncing group membership, and deleting
 * accounts.
 *
 * Persistence follows the same dirty() pattern UserManagement::setActive()
 * uses: typed property writes bypass ActiveRecord's dirty tracker, so
 * changed fields are pushed explicitly before save().
 *
 * Banned users are already blocked by Shield at login (attempt() and the
 * remember-me path both reject status 'banned'); no additional checking
 * is needed here.
 *
 * Every account holds at least one group. syncGroups() falls back to
 * BASE_GROUP when a caller posts none, which is what a headless or API
 * client does: it never renders the admin checkbox list, so an absent list
 * means 'not supplied', not 'no groups'.
 *
 * @package Pubvana\Services
 */
class UserAdminService
{
    /**
     * The group every account holds at least.
     *
     * Shield seeds it as 'user' (superadmin, admin, user) and moves members
     * to it when a group is deleted. syncGroups() applies the same floor.
     */
    public const BASE_GROUP = 'user';

    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Ban a user, with an optional message shown in the admin UI.
     * The user can no longer log in.
     */
    public function ban(User $user, ?string $message = null): void
    {
        $user->ban($message !== null && $message !== '' ? $message : null);

        $this->saveStatus($user, 'banned', $user->getBanMessage());
    }

    /**
     * Lift a ban. The account's active flag is untouched.
     */
    public function unBan(User $user): void
    {
        $user->unBan();

        $this->saveStatus($user, null, null);
    }

    /**
     * Set or clear Shield's force_reset flag on the user's email identity.
     */
    public function forceReset(User $user, bool $force): void
    {
        (new UserIdentity($this->app->db()))->forcePasswordReset($user, $force);
    }

    /**
     * Create a user through Shield. Assigning any group above the base group
     * is a groups.manage action: a users.create holder must not be able to
     * mint an admin account.
     */
    public function createUser(string $username, string $email, string $password, ?string $group): Result
    {
        $alias = $group !== null ? strtolower(trim($group)) : '';

        if ($alias === 'superadmin' && ! $this->isSuperadminActor()) {
            return $this->deny('Only a superadmin can assign the superadmin group.');
        }

        if ($alias !== '' && ! $this->actorMayAssignGroup($alias)) {
            return $this->deny('Only a holder of groups.manage can assign that group.');
        }

        return $this->app->auth()->users()->create($username, $email, $password, $group);
    }

    /**
     * Replace a user's group memberships.
     *
     * Granting the superadmin group is reserved for superadmins, and the
     * last superadmin can never be demoted.
     *
     * @param array<array-key, mixed> $groups
     */
    public function syncGroups(User $user, array $groups): Result
    {
        $groups = array_values(array_unique(array_map('strval', $groups)));

        // Never leave the account with no groups. An absent list (a headless
        // or API caller that posts no groups at all) is not a request for
        // zero groups, so it falls back to the base group.
        if ($groups === []) {
            $groups = [self::BASE_GROUP];
        }

        $grantsSuperadmin = in_array('superadmin', $groups, true);
        $dropsSuperadmin = $user->inGroup('superadmin') && ! $grantsSuperadmin;

        if ($grantsSuperadmin && ! $this->isSuperadminActor()) {
            return $this->deny('Only a superadmin can assign the superadmin group.');
        }

        if ($dropsSuperadmin && $this->superadminCount() <= 1) {
            return $this->deny('The last superadmin cannot be demoted.');
        }

        // A users.edit holder could otherwise grant the admin group to
        // themselves or to a new account. Each newly added group must be one
        // the actor already holds or one whose permissions the actor already
        // has, so a narrower custom role cannot escalate.
        $current = array_values(array_unique(array_map('strval', $user->getGroups())));

        foreach (array_diff($groups, $current) as $group) {
            if (! $this->actorMayAssignGroup($group)) {
                return $this->deny('Only a holder of groups.manage can assign that group.');
            }
        }

        $user->syncGroups($groups);

        return (new Result())->setSuccess(true);
    }

    /**
     * Update a user's username, email, and password.
     *
     * A password write revokes the credentials issued under the old one:
     * remember tokens and sessions are deleted, matching the self-service
     * flow. Without this, an administrator resetting a password leaves any
     * stolen session signed in.
     *
     * @param array<string, mixed> $data
     */
    public function updateProfile(User $user, array $data): Result
    {
        $result = $this->app->auth()->users()->updateProfile($user, $data);

        $hasPassword = isset($data['password']) && (string) $data['password'] !== '';

        if (! $result->isOK() || ! $hasPassword) {
            return $result;
        }

        (new UserIdentity($this->app->db()))->forcePasswordReset($user, false);
        (new RememberToken($this->app->db()))->deleteByUser((int) $user->id);

        // Keep the acting admin's own session when they change their own
        // password, the same way the profile screen does.
        $actor = $this->app->auth()->user();
        $keep  = null;

        if ($actor !== null && (string) $actor->id === (string) $user->id) {
            $sessionId = $this->app->session()->id();
            $keep      = is_string($sessionId) && $sessionId !== '' ? $sessionId : null;
        }

        (new Session($this->app->db()))->deleteForUser((int) $user->id, $keep);

        return $result;
    }

    /**
     * Soft-delete a user. Refuses self-deletion and removal of the last
     * superadmin.
     */
    public function deleteUser(User $user): Result
    {
        $actor = $this->app->auth()->user();

        if ($actor !== null && (string) $actor->id === (string) $user->id) {
            return $this->deny('You cannot delete your own account.');
        }

        if ($user->inGroup('superadmin') && $this->superadminCount() <= 1) {
            return $this->deny('The last superadmin cannot be deleted.');
        }

        $this->app->auth()->users()->delete($user);

        return (new Result())->setSuccess(true);
    }

    /**
     * Flip a user's active flag. Refuses deactivating your own account
     * (you would lock yourself out mid-session) and deactivating the last
     * superadmin (nobody could recover the panel). Reactivating is always
     * allowed.
     */
    public function setActive(User $user, bool $active): Result
    {
        $actor = $this->app->auth()->user();

        if (!$active && $actor !== null && (string) $actor->id === (string) $user->id) {
            return $this->deny('You cannot deactivate your own account.');
        }

        if (!$active && $user->inGroup('superadmin') && $this->superadminCount() <= 1) {
            return $this->deny('The last superadmin cannot be deactivated.');
        }

        $this->app->auth()->users()->setActive($user, $active);

        return (new Result())->setSuccess(true);
    }

    /**
     * Whether the current user is a superadmin.
     */
    protected function isSuperadminActor(): bool
    {
        return (bool) ($this->app->auth()->user()?->inGroup('superadmin') ?? false);
    }

    /**
     * Whether the current user may grant a group.
     *
     * A superadmin or a groups.manage holder may grant any group. Anyone else
     * may grant a group they already belong to, or one whose permissions they
     * already hold. That keeps a users.edit holder from granting the admin
     * group while still letting an admin assign the groups they already have.
     */
    protected function actorMayAssignGroup(string $group): bool
    {
        $actor = $this->app->auth()->user();

        if ($actor === null) {
            return false;
        }

        if ($actor->inGroup('superadmin') || $actor->can('groups.manage')) {
            return true;
        }

        if ($actor->inGroup($group)) {
            return true;
        }

        foreach ($this->groupPermissions($group) as $permission) {
            if (! $actor->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Permission aliases granted by one group.
     *
     * @return list<string>
     */
    protected function groupPermissions(string $group): array
    {
        /** @var array<int, AuthGroupPermission> $records */
        $records = (new AuthGroupPermission($this->app->db()))
            ->eq('group_alias', $group)
            ->findAll();

        return array_values(array_map(
            static fn (AuthGroupPermission $record): string => (string) $record->permission_alias,
            $records
        ));
    }

    /**
     * Count non-deleted users in the superadmin group.
     */
    protected function superadminCount(): int
    {
        $counts = (new GroupUser($this->app->db()))->countByGroup();

        return (int) ($counts['superadmin'] ?? 0);
    }

    /**
     * Shortcut for a failed Result.
     */
    protected function deny(string $reason): Result
    {
        return (new Result())->setSuccess(false)->setReason($reason);
    }

    /**
     * Persist status fields, mirroring UserManagement::setActive().
     */
    protected function saveStatus(User $user, ?string $status, ?string $statusMessage): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $user->updated_at = $now;
        $user->dirty([
            'status'         => $status,
            'status_message' => $statusMessage,
            'updated_at'     => $now,
        ]);
        $user->save();
    }
}
