<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Models;

/**
 * AiKeyGrant - Per-key API grant (ai_key_grants table).
 *
 * One row per granted permission. Granting is explicitly opt-in:
 * a key with no rows is deny-all. Updates replace the whole set for
 * a key atomically.
 *
 * Schema:
 *   id          - Auto-increment primary key
 *   key_id      - FK to ai_keys.id
 *   permission  - Granted permission alias (e.g. 'posts.create')
 *
 * @package Pubvana\Plugins\AiAssistant\Models
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 */
class AiKeyGrant extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'ai_key_grants', $config);
    }

    /**
     * All permission aliases granted to a key.
     *
     * @param int $keyId
     * @return string[]
     */
    public function permissionsFor(int $keyId): array
    {
        $model = new self($this->getDatabaseConnection());
        $rows = $model->eq('key_id', $keyId)->order('permission ASC')->findAll();

        $permissions = [];
        foreach ($rows as $row) {
            if (isset($row->permission)) {
                $permissions[] = (string) $row->permission;
            }
        }
        return $permissions;
    }

    /**
     * Replace every grant for a key with the given set.
     *
     * The delete and the inserts share one transaction. The unique index on
     * (key_id, permission) means the delete has to happen first, so without
     * the transaction a failure partway through the insert loop leaves the key
     * on a partial set. A key with no rows is deny-all, so the visible result
     * of the failure is a key stripped of permissions, not an error.
     *
     * @param int      $keyId
     * @param string[] $permissions
     * @throws \Throwable When a statement fails; the key keeps the set it had
     */
    public function replaceFor(int $keyId, array $permissions): void
    {
        $pdo = $this->getDatabaseConnection();

        $permissions = array_values(array_filter(array_unique(array_map('trim', $permissions)), static fn(string $value): bool => $value !== ''));

        // Outside the try: if beginTransaction() throws, no transaction of
        // ours is open, and rolling back would discard the caller's work.
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('DELETE FROM ai_key_grants WHERE key_id = :key_id');
            $stmt->execute([':key_id' => $keyId]);

            if ($permissions !== []) {
                $stmt = $pdo->prepare('INSERT INTO ai_key_grants (key_id, permission) VALUES (:key_id, :permission)');
                foreach ($permissions as $permission) {
                    $stmt->execute([':key_id' => $keyId, ':permission' => $permission]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollback();
            throw $e;
        }
    }
}