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
 */
class AiKeyGrant extends \flight\ActiveRecord
{
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
     * @param int      $keyId
     * @param string[] $permissions
     */
    public function replaceFor(int $keyId, array $permissions): void
    {
        $pdo = $this->getDatabaseConnection();

        $stmt = $pdo->prepare('DELETE FROM ai_key_grants WHERE key_id = :key_id');
        $stmt->execute([':key_id' => $keyId]);

        $permissions = array_values(array_filter(array_unique(array_map('trim', $permissions)), static fn(string $value): bool => $value !== ''));
        if ($permissions === []) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO ai_key_grants (key_id, permission) VALUES (:key_id, :permission)');
        foreach ($permissions as $permission) {
            $stmt->execute([':key_id' => $keyId, ':permission' => $permission]);
        }
    }
}