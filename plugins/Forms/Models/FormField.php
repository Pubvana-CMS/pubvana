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
 */
class FormField extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'form_fields', $config);
    }

    public function forForm(int $formId): array
    {
        return (new self($this->getDatabaseConnection()))
            ->eq('form_id', $formId)
            ->order('sort_order ASC')
            ->findAll();
    }

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
