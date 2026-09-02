<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Models;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property string      $status
 * @property string      $submit_label
 * @property string|null $success_message
 * @property string|null $notification_emails
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEq(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
  *
 * @property int $cnt Aggregate alias from COUNT(*) selects
*/
class Form extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'forms', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->isNull('deleted_at')->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findPublishedBySlug(string $slug): ?self
    {
        $this->reset();
        $this->eq('slug', $slug)
            ->eq('status', 'published')
            ->isNull('deleted_at')
            ->find();

        return $this->isHydrated() ? $this : null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt')
            ->eq('slug', $slug)
            ->isNull('deleted_at');

        if ($excludeId !== null) {
            $query->notEq('id', $excludeId);
        }

        $result = $query->find();
        return (int) $result->cnt > 0;
    }

    /**
     * @return array<int, Form>
     */
    public function paginate(int $page = 1, int $perPage = 25): array
    {
        return (new self($this->getDatabaseConnection()))
            ->isNull('deleted_at')
            ->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();
    }

    /**
     * @return array<int, Form>
     */
    public function listAll(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->isNull('deleted_at')
            ->order('name ASC')
            ->findAll();
    }

    public function countAll(): int
    {
        $result = (new self($this->getDatabaseConnection()))
            ->select('COUNT(*) as cnt')
            ->isNull('deleted_at')
            ->find();

        return (int) $result->cnt;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRecord(array $data): self
    {
        $record = new self($this->getDatabaseConnection());
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($data as $key => $value) {
            $record->$key = $value;
        }

        $record->created_at = $now;
        $record->updated_at = $now;
        $record->insert();

        return $record;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateRecord(array $data): void
    {
        $allowed = ['name', 'description', 'status', 'submit_label', 'success_message', 'notification_emails'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $this->$field = $data[$field];
            }
        }

        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }

    public function softDelete(): void
    {
        $this->deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }
}
