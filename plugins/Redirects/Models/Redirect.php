<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Models;

/**
 * @property int         $id
 * @property string      $source_path
 * @property string      $target_url
 * @property int         $status_code
 * @property int         $enabled
 * @property string|null $notes
 * @property int         $hit_count
 * @property string|null $last_hit_at
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 */
class Redirect extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'redirects', $config);
    }

    /**
     * @return self[]
     */
    public function allOrdered(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->order('source_path ASC')
            ->findAll();
    }

    /**
     * Find a redirect by ID.
     *
     * @param int $id
     * @return self|null
     */
    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * Find an enabled redirect matching the given source path.
     *
     * @param string $sourcePath
     * @return self|null
     */
    public function findActiveBySourcePath(string $sourcePath): ?self
    {
        $this->reset();
        $this->eq('source_path', $sourcePath)
            ->eq('enabled', 1)
            ->find();

        return $this->isHydrated() ? $this : null;
    }
}
