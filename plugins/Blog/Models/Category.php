<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property int|null    $parent_id
 * @property string      $created_at
 * @property string      $updated_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEqual(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self in(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
  *
 * @property int $cnt Aggregate alias from COUNT(*) selects
*/
class Category extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'categories', $config);
    }

    public function findById(int $id): ?self
    {
        // Fresh instance: a reused $this would alias every find to the same object.
        $query = new self($this->getDatabaseConnection());
        $query->eq('id', $id)->find();
        return $query->isHydrated() ? $query : null;
    }

    public function findBySlug(string $slug): ?self
    {
        // Fresh instance: see findById() for why $this cannot be reused.
        $query = new self($this->getDatabaseConnection());
        $query->eq('slug', $slug)->find();
        return $query->isHydrated() ? $query : null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt')->eq('slug', $slug);

        if ($excludeId !== null) {
            $query->notEqual('id', $excludeId);
        }

        $result = $query->find();
        return ((int) $result->cnt) > 0;
    }

    /**
     * @return array<int, Category>
     */
    public function getAll(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->order('name ASC')
            ->findAll();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRecord(array $data): self
    {
        // Whitelisted like updateRecord(): never set arbitrary properties
        // from a caller-supplied array.
        $allowed = ['name', 'slug', 'description', 'parent_id'];

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $record = new self($this->getDatabaseConnection());

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $record->$field = $data[$field];
            }
        }

        $record->created_at = $now;
        $record->updated_at = $now;
        $record->insert();

        return $record;
    }

    /**
     * Move a category's children to a new parent in one statement.
     *
     * Raw SQL: the fluent API can express a conditional read but not a bulk
     * UPDATE ... WHERE, and hydrating every child just to re-save it would
     * be a query per row. Used on delete to keep no child pointing at a
     * category that no longer exists.
     */
    public function reassignChildren(int $parentId, ?int $newParentId): void
    {
        $pdo = $this->getDatabaseConnection();
        $stmt = $pdo->prepare('UPDATE categories SET parent_id = :new WHERE parent_id = :old');
        $stmt->execute([':new' => $newParentId, ':old' => $parentId]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateRecord(array $data): void
    {
        $allowed = ['name', 'slug', 'description', 'parent_id'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $this->$field = $data[$field];
            }
        }

        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }
}
