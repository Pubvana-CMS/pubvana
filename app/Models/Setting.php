<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * Setting - ActiveRecord model for the settings table.
 *
 * One row per runtime setting. Keys are namespaced with dot notation
 * (e.g. 'CMS.siteName', 'blog.postsPerPage') where the first segment
 * identifies the owning plugin or core feature.
 *
 * Storage format:
 *   - Scalars are stored human-readable in `value`, with the PHP type
 *     recorded in `type` so reads cast back exactly what was written
 *   - Arrays and objects are JSON-encoded, with type 'array'/'object'
 *   - NULL values store an empty `value` with type 'NULL'
 *
 * This model is storage-only. Resolution order (DB vs env vs config vs
 * declaration defaults) lives in SettingsService. The admin UI whitelist
 * lives in the adext 'admin.settings' declarations.
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 *
 * @package Pubvana\Models
 */
class Setting extends AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'settings', $config);
    }

    public int $id;
    public string $key;
    public ?string $value = null;
    public string $type = 'string';
    public bool $autoload = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // -----------------------------------------------------------------
    // Query Methods
    // -----------------------------------------------------------------

    /**
     * Find one setting row by its full key.
     *
     * @param string $key Namespaced key (e.g. 'CMS.siteName')
     * @return self|null Hydrated row, or null when no row exists
     */
    public function findByKey(string $key): ?self
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('key', $key)->find();

        return $model->isHydrated() ? $model : null;
    }

    /**
     * Load every row in a single query (boot-time bulk load).
     *
     * Loads autoload=1 AND autoload=0 rows together so per-key reads elsewhere
     * (e.g. PublicController buildGlobalData's CMS.siteByline / logo / favicon /
     * copyright, CommentService setting('comments...')) hit the in-memory cache
     * instead of round-tripping per missed key. The bulk query is cheaper than
     * N single-row reads, even counting rows we mark autoload=0 for change
     * tracking.
     *
     * @return self[] Every row in the settings table
     */
    public function getAutoloadRows(): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->findAll();
    }

    /**
     * Load every row for one namespace (everything before the first dot).
     *
     * @param string $namespace Namespace (e.g. 'CMS', 'blog')
     * @return self[] All rows whose key starts with "{namespace}."
     */
    public function getForNamespace(string $namespace): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->like('key', $namespace . '.%')->findAll();
    }

    /**
     * Load all rows as key => decoded value pairs.
     *
     * Convenience for debugging/export; the service keeps its own cache
     * and does not use this on hot paths.
     *
     * @return array<string, mixed> Decoded values keyed by setting key
     */
    public function getAllDecoded(): array
    {
        $model = new self($this->getDatabaseConnection());
        $rows = $model->findAll();

        $out = [];
        foreach ($rows as $row) {
            $out[$row->key] = $row->decodedValue();
        }
        return $out;
    }

    // -----------------------------------------------------------------
    // Value Casting
    // -----------------------------------------------------------------

    /**
     * Decode this row's stored value back to its PHP type.
     *
     * Uses the `type` column recorded at write time so an int comes
     * back an int, a bool comes back a bool, etc.
     *
     * @return mixed
     */
    public function decodedValue(): mixed
    {
        return self::cast($this->type, $this->value);
    }

    /**
     * Cast a raw stored string back to the recorded PHP type.
     *
     * @param string $type  PHP type name as returned by gettype()
     * @param mixed  $stored Raw stored representation (usually string|null)
     * @return mixed
     */
    public static function cast(string $type, mixed $stored): mixed
    {
        if ($stored === null) {
            return null;
        }

        return match ($type) {
            'boolean'  => (bool) $stored,
            'integer'  => (int) $stored,
            'double'   => (float) $stored,
            'array'    => json_decode((string) $stored, true) ?? [],
            'object'   => json_decode((string) $stored) ?? new \stdClass(),
            'NULL'     => null,
            default    => (string) $stored,
        };
    }
}
