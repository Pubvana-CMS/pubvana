<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * Session ActiveRecord model.
 *
 * Rows live in the `sessions` table owned by enlivenapp/flight-sessions.
 * The payload is encrypted by that package, so this model never reads it.
 * A row is a signed-in device: deleting the row signs that device out.
 *
 * @property int         $id
 * @property string      $session_id
 * @property int|null    $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $payload
 * @property int         $last_activity
 * @property string|null $created_at
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self gte(string $field, mixed $value, string $operator = 'AND')
 * @method self orderByColumn(string $column, string $direction = 'ASC')
 *
 * @package Pubvana\Models
 */
class Session extends AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'sessions', $config);
    }

    public int $id;
    public string $session_id = '';
    public ?int $user_id = null;
    public ?string $ip_address = null;
    public ?string $user_agent = null;
    public ?string $payload = null;
    public int $last_activity = 0;
    public ?string $created_at = null;

    /**
     * Every session row for one user, newest activity first.
     *
     * $minLastActivity drops rows the idle timeout already expired. The
     * caller computes the cutoff, so this stays a plain query.
     *
     * @return list<self>
     */
    public function allForUser(int $userId, int $minLastActivity = 0): array
    {
        $query = new self($this->getDatabaseConnection());
        $query->eq('user_id', $userId)
              ->gte('last_activity', $minLastActivity)
              ->orderByColumn('last_activity', 'DESC');

        return array_values($query->findAll());
    }

    /**
     * One session row, only when it belongs to the given user.
     */
    public function findForUser(int $userId, int $id): ?self
    {
        $query = new self($this->getDatabaseConnection());
        $query->eq('id', $id)
              ->eq('user_id', $userId)
              ->find();

        return $query->isHydrated() ? $query : null;
    }

    /**
     * Delete a user's sessions, optionally keeping the current one.
     *
     * @return int Number of rows deleted
     */
    public function deleteForUser(int $userId, ?string $keepSessionId = null): int
    {
        $deleted = 0;

        foreach ($this->allForUser($userId) as $session) {
            if ($keepSessionId !== null && $session->session_id === $keepSessionId) {
                continue;
            }

            $session->delete();
            $deleted++;
        }

        return $deleted;
    }
}
