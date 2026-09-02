<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms\Models;

/**
 * @property int         $id
 * @property int         $form_id
 * @property string      $type
 * @property string      $name
 * @property string      $label
 * @property string|null $help_text
 * @property string|null $placeholder
 * @property int         $is_required
 * @property string      $width
 * @property string|null $options_json
 * @property int         $sort_order
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEq(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 */
class FormField extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'form_fields', $config);
    }

    /**
     * @return array<int, FormField>
     */
    public function forForm(int $formId): array
    {
        return (new self($this->getDatabaseConnection()))
            ->eq('form_id', $formId)
            ->order('sort_order ASC')
            ->findAll();
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

    public function deleteForForm(int $formId): void
    {
        $items = $this->forForm($formId);
        foreach ($items as $item) {
            $item->delete();
        }
    }
}
