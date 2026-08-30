<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int    $id
 * @property string $name
 * @property string $slug
 */
class Tag extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'tags', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * Bulk-load tags by id, indexed by id, using ActiveRecord's IN operator.
     *
     * Replaces the per-id findById() N+1 in BlogService::getPostTagNames()
     * (a post with N tags used to cost N SELECTs; now it's one).
     *
     * @param int[] $ids
     * @return array<int, self>
     */
    public function findByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn($i) => (int) $i > 0)));
        if ($ids === []) {
            return [];
        }
        $model = new self($this->getDatabaseConnection());
        $rows = $model->select('id', 'name')->in('id', $ids)->findAll();
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row->id] = $row;
        }
        return $byId;
    }

    public function findBySlug(string $slug): ?self
    {
        $this->reset();
        $this->eq('slug', $slug)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findOrCreate(string $name, string $slug): self
    {
        $existing = (new self($this->getDatabaseConnection()));
        $existing->eq('slug', $slug)->find();

        if ($existing->isHydrated()) {
            return $existing;
        }

        $record = new self($this->getDatabaseConnection());
        $record->name = $name;
        $record->slug = $slug;
        $record->insert();

        return $record;
    }

    public function getAll(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->order('name ASC')
            ->findAll();
    }

    public function delete(): void
    {
        parent::delete();
    }
}
