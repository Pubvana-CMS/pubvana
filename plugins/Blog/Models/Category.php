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
 */
class Category extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'categories', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findBySlug(string $slug): ?self
    {
        $this->reset();
        $this->eq('slug', $slug)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt')->eq('slug', $slug);

        if ($excludeId !== null) {
            $query->notEq('id', $excludeId);
        }

        $result = $query->find();
        return ((int) $result->cnt) > 0;
    }

    public function getAll(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->order('name ASC')
            ->findAll();
    }

    public function createRecord(array $data): self
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $record = new self($this->getDatabaseConnection());

        foreach ($data as $key => $value) {
            $record->$key = $value;
        }

        $record->created_at = $now;
        $record->updated_at = $now;
        $record->insert();

        return $record;
    }

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

    public function delete(): void
    {
        parent::delete();
    }
}
