<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * Shared base for all Pubvana ActiveRecord models.
 *
 * The vendor ActiveRecord hydrates rows with `new $called_class`, so
 * find()/findAll() return instances of the concrete model class at runtime.
 * The vendor PHPDoc types them as the base ActiveRecord instead, which
 * erases the concrete type for every caller. These overrides restore the
 * true return types for our models without touching vendor code.
 */
abstract class AbstractModel extends \flight\ActiveRecord
{
    /**
     * @param int|string|null $id
     * @return static
     */
    public function find($id = null)
    {
        return parent::find($id);
    }

    /**
     * @return array<int, static>
     */
    public function findAll(): array
    {
        return parent::findAll();
    }
}
