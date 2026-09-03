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
        $model = new self($this->getDatabaseConnection());
        $this->applyFilters($model, $filters);

        $model->order('id DESC');
        $model->limit($perPage);
        $model->offset(($page - 1) * $perPage);

        return $model->findAll();
    }

    /**
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countFiltered(array $filters = []): int
    {
        $model = new self($this->getDatabaseConnection());
        $this->applyFilters($model, $filters);

        $model->select('COUNT(*) AS cnt');

        return (int) $model->find()->cnt;
    }

    /**
     * Apply the shared list filters to a query instance.
     *
     * All conditions use bound-parameter operators. Raw where() is avoided:
     * it replaces the whole WHERE expression instead of appending.
     *
     * @param array<string, mixed> $filters
     */
    private function applyFilters(self $model, array $filters): void
    {
        if (!empty($filters['user_id'])) {
            $model->eq('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $model->eq('action', $filters['action']);
        }

        if (!empty($filters['entity_type'])) {
            $model->eq('entity_type', $filters['entity_type']);
        }

        if (!empty($filters['entity_name'])) {
            $model->like('entity_name', '%' . $filters['entity_name'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $model->ge('created_at', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $model->le('created_at', $filters['date_to'] . ' 23:59:59');
        }
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
}