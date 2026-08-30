<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media\Models;

class Media extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'media', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();

        return $this->isHydrated() ? $this : null;
    }

    public function paginate(int $page = 1, int $perPage = 24, ?string $type = null): array
    {
        $query = new self($this->getDatabaseConnection());

        if ($type !== null) {
            $query->eq('type', $type);
        }

        return $query->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();
    }

    public function countAll(?string $type = null): int
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt');

        if ($type !== null) {
            $query->eq('type', $type);
        }

        $result = $query->find();

        return (int) $result->cnt;
    }

    public function createRecord(array $data): self
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $media = new self($this->getDatabaseConnection());

        foreach ($data as $key => $value) {
            $media->$key = $value;
        }

        $media->created_at = $now;
        $media->updated_at = $now;
        $media->insert();

        return $media;
    }

    public function updateMeta(array $data): void
    {
        $allowed = ['alt_text', 'title', 'poster_path'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $this->$field = trim((string) $data[$field]) ?: null;
            }
        }

        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }
}
