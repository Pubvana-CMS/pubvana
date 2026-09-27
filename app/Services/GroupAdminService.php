<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightShield\Models\AuthGroup;
use Enlivenapp\FlightShield\Result;
use flight\Engine;

/**
 * GroupAdminService - Group administration rules Shield's Groups API does
 * not enforce.
 *
 * The superadmin group is a Pubvana invariant, not a Shield one. Shield has
 * no concept of it and will delete the group on request, reassigning every
 * member to the hardcoded 'user' fallback (Groups::delete()), which strips
 * admin access from every operator and leaves nobody who can restore the
 * group through the panel. Refusing here keeps the rule in Pubvana code and
 * leaves vendor untouched.
 *
 * A shell operator who deletes the group with `shield:group delete` can
 * still put it back (`shield:group create` then `shield:user addgroup`), so
 * the web path is the one that needs the guard.
 *
 * @package Pubvana\Services
 */
class GroupAdminService
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
     * Delete a group. The superadmin group is never deletable.
     */
    public function deleteGroup(AuthGroup $group): Result
    {
        if (strtolower($group->alias) === 'superadmin') {
            return $this->deny('The superadmin group cannot be deleted.');
        }

        $this->app->auth()->groups()->delete($group->alias);

        return (new Result())->setSuccess(true);
    }

    /**
     * Shortcut for a failed Result.
     */
    protected function deny(string $reason): Result
    {
        return (new Result())->setSuccess(false)->setReason($reason);
    }
}
