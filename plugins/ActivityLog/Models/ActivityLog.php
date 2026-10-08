<?php

declare(strict_types=1);

namespace Pubvana\Plugins\ActivityLog\Models;

/**
 * ActivityLog - Activity log entry (activity_logs table).
 *
 * One row per tracked admin action.
 *
 * Schema:
 *   id            - Auto-increment primary key
 *   user_id       - FK to users.id (nullable for system actions)
 *   user_name     - Snapshot of the username (survives user deletion)
 *   action        - Action performed (create, update, delete, publish, settings_change, etc.)
 *   entity_type   - Type of entity acted on (blog_post, page, redirect, user, setting, form, etc.)
 *   entity_id     - Target entity id when applicable
 *   entity_name   - Human-readable name of entity
 *   details       - JSON-encoded additional context
 *   ip            - Client IP address
 *   user_agent    - Client user agent
 *   created_at    - Request timestamp
 *
 * @package Pubvana\Plugins\ActivityLog\Models
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $user_name
 * @property string      $action
 * @property string      $entity_type
 * @property int|null    $entity_id
 * @property string      $entity_name
 * @property string|null $details
 * @property string      $ip
 * @property string|null $user_agent
 * @property string|null $created_at
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self ge(string $field, mixed $value, string $operator = 'AND')
 * @method self le(string $field, mixed $value, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 *
 * @property int $cnt Aggregate alias from COUNT(*) selects
 */
class ActivityLog extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'activity_logs', $config);
    }

    /**
     * @param int $limit
     * @return self[] Most recent log entries, newest first.
     */
    public function recent(int $limit = 50): array
    {
        $limit = max(1, $limit);
        $model = new self($this->getDatabaseConnection());
        return $model->order('id DESC')->limit($limit)->findAll();
    }

    /**
     * @param array<string, mixed> $filters
     * @param int                  $page
     * @param int                  $perPage
     * @return self[]
     */
    public function filtered(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        [$where, $params] = $this->listWhere($filters);

        $limit  = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);

        // Raw SQL so the LIKE can carry an explicit ESCAPE '!' (see listWhere).
        $rows = $this->query(
            'SELECT * FROM activity_logs' . $where . ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countFiltered(array $filters = []): int
    {
        [$where, $params] = $this->listWhere($filters);

        $row = $this->query('SELECT COUNT(*) AS cnt FROM activity_logs' . $where, $params, null, true);

        return (int) ($row->cnt ?? 0);
    }

    /**
     * Escape LIKE metacharacters with '!'. Pair with an explicit
     * "ESCAPE '!'" in the SQL: the escape character is then named, so the
     * match is the same on every server. The default escape character
     * depends on the server's sql_mode (NO_BACKSLASH_ESCAPES changes it),
     * and an explicit one does not.
     */
    private function escapeLike(string $term): string
    {
        return strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']);
    }

    /**
     * Build the WHERE clause and bound params for the shared list filters.
     *
     * Raw SQL rather than the builder so the LIKE can carry an explicit
     * ESCAPE '!'. Every value is still a bound parameter.
     *
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function listWhere(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['user_id'])) {
            $clauses[] = 'user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $clauses[] = 'action = :action';
            $params[':action'] = (string) $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $clauses[] = 'entity_type = :entity_type';
            $params[':entity_type'] = (string) $filters['entity_type'];
        }

        if (!empty($filters['entity_name'])) {
            $clauses[] = "entity_name LIKE :entity_name ESCAPE '!'";
            $params[':entity_name'] = '%' . $this->escapeLike((string) $filters['entity_name']) . '%';
        }

        if (!empty($filters['date_from'])) {
            $clauses[] = 'created_at >= :date_from';
            $params[':date_from'] = (string) $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[] = 'created_at <= :date_to';
            $params[':date_to'] = (string) $filters['date_to'] . ' 23:59:59';
        }

        return [$clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * @param string $since
     * @return int
     */
    public function countSince(string $since): int
    {
        $model = new self($this->getDatabaseConnection());
        $model->select('COUNT(*) AS cnt');
        $model->ge('created_at', $since);
        return (int) $model->find()->cnt;
    }

    /**
     * Distinct action values, for the filter dropdown.
     *
     * @return string[]
     */
    public function distinctActions(): array
    {
        $model = new self($this->getDatabaseConnection());
        $values = $model->distinct()->orderByColumn('action')->pluck('action');

        return array_map(static fn ($value): string => (string) $value, $values);
    }

    /**
     * Distinct entity types, for the filter dropdown.
     *
     * @return string[]
     */
    public function distinctEntityTypes(): array
    {
        $model = new self($this->getDatabaseConnection());
        $values = $model->distinct()->orderByColumn('entity_type')->pluck('entity_type');

        return array_map(static fn ($value): string => (string) $value, $values);
    }

    /**
     * Distinct users that have entries, for the filter dropdown.
     *
     * Raw SQL rather than the builder: distinct() only applies to the default
     * table.* select and to pluck(), not to an explicit column list.
     *
     * @return array<int, array{user_id: int, user_name: string}>
     */
    public function distinctUsers(): array
    {
        // query() returns array|ActiveRecord depending on $single; this call
        // is not single mode, so an array of rows is expected. Group by
        // user_id so a user with renamed name snapshots appears once.
        $rows = $this->query(
            'SELECT user_id, MAX(user_name) AS user_name FROM activity_logs WHERE user_id IS NOT NULL GROUP BY user_id ORDER BY user_name',
            []
        );

        $users = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $users[] = [
                    'user_id'   => (int) $row->user_id,
                    'user_name' => (string) $row->user_name,
                ];
            }
        }

        return $users;
    }
}