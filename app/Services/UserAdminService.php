<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightShield\Models\GroupUser;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use Enlivenapp\FlightShield\Result;
use flight\Engine;

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
 * @package Pubvana\Services
 */
class UserAdminService
{
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
     *
     * With the flag set, any request the user makes through a route guarded
     * by ForcePasswordResetMiddleware redirects to the reset page until a
     * new password is saved.
     */
    public function forceReset(User $user, bool $force): void
    {
        (new UserIdentity($this->app->db()))->forcePasswordReset($user, $force);
    }

    /**
     * Create a user through Shield. Assigning the superadmin group is
     * reserved for superadmins; anyone else is refused.
     */
    public function createUser(string $username, string $email, string $password, ?string $group): Result
    {
        if ($group !== null && strtolower($group) === 'superadmin' && ! $this->isSuperadminActor()) {
            return $this->deny('Only a superadmin can assign the superadmin group.');
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

        $grantsSuperadmin = in_array('superadmin', $groups, true);
        $dropsSuperadmin = $user->inGroup('superadmin') && ! $grantsSuperadmin;

        if ($grantsSuperadmin && ! $this->isSuperadminActor()) {
            return $this->deny('Only a superadmin can assign the superadmin group.');
        }

        if ($dropsSuperadmin && $this->superadminCount() <= 1) {
            return $this->deny('The last superadmin cannot be demoted.');
        }

        $user->syncGroups($groups);

        return (new Result())->setSuccess(true);
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
