<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Models;

/**
 * @property int         $id
 * @property int         $form_id
 * @property string      $status
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer_url
 * @property string|null $payload_json
 * @property string|null $submitted_at
 * @property string|null $created_at
 * @property string|null $updated_at
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
class FormSubmission extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'form_submissions', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * @return array<int, FormSubmission>
     */
    public function paginate(int $page = 1, int $perPage = 25, ?int $formId = null): array
    {
        $query = new self($this->getDatabaseConnection());

        if ($formId !== null) {
            $query->eq('form_id', $formId);
        }

        return $query
            ->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();
    }

    public function countAll(?int $formId = null): int
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt');

        if ($formId !== null) {
            $query->eq('form_id', $formId);
        }

        $result = $query->find();

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

        $record->submitted_at = $now;
        $record->created_at = $now;
        $record->updated_at = $now;
        $record->insert();

        return $record;
    }
}
