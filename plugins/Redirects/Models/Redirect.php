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
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 * @property int $cnt Aggregate alias from COUNT(*) selects
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
        // Fresh instance: a reused $this would alias every find to the same object.
        $query = new self($this->getDatabaseConnection());
        $query->eq('id', $id)->find();
        return $query->isHydrated() ? $query : null;
    }

    /**
     * Find an enabled redirect matching the given source path.
     *
     * @param string $sourcePath
     * @return self|null
     */
    public function findActiveBySourcePath(string $sourcePath): ?self
    {
        // Fresh instance: see findById() for why $this cannot be reused.
        $query = new self($this->getDatabaseConnection());
        $query->eq('source_path', $sourcePath)
            ->eq('enabled', 1)
            ->find();

        return $query->isHydrated() ? $query : null;
    }

    /**
     * Paginated redirects, newest first.
     *
     * @return array<int, self>
     */
    public function paginate(int $page = 1, int $perPage = 25): array
    {
        $query = new self($this->getDatabaseConnection());
        return $query->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();
    }

    /**
     * Count all redirects.
     *
     * @return int
     */
    public function countAll(): int
    {
        $query = new self($this->getDatabaseConnection());
        $result = $query->select('COUNT(*) as cnt')->find();
        return (int) $result->cnt;
    }
}
