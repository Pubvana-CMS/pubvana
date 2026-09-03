<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks\Models;

/**
 * @property int         $id
 * @property string      $source_type
 * @property int         $source_id
 * @property string      $source_title
 * @property string      $url
 * @property string      $url_hash
 * @property int|null    $http_status
 * @property string|null $error_message
 * @property int         $dismissed
 * @property string|null $last_checked_at
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEq(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self notNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self limit(int $count)
 */
class BrokenLink extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'broken_links', $config);
    }

    /**
     * Find a broken link by ID.
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
     * Find an existing result by source and URL hash.
     *
     * @param string $sourceType
     * @param int    $sourceId
     * @param string $urlHash
     * @return self|null
     */
    public function findBySourceAndHash(string $sourceType, int $sourceId, string $urlHash): ?self
    {
        $this->reset();
        $this->eq('source_type', $sourceType)
            ->eq('source_id', $sourceId)
            ->eq('url_hash', $urlHash)
            ->find();

        return $this->isHydrated() ? $this : null;
    }

    /**
     * Get all results ordered by source, optionally including dismissed.
     *
     * @return self[]
     */
    public function allOrdered(bool $showDismissed = false): array
    {
        $query = new self($this->getDatabaseConnection());

        if (!$showDismissed) {
            $query->eq('dismissed', 0);
        }

        return $query
            ->order('source_type ASC, source_id ASC, url ASC')
            ->findAll();
    }
}
