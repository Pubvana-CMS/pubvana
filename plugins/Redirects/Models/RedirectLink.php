<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Redirects\Models;

/**
 * @property int         $id
 * @property string      $source_path
 * @property int         $hit_count
 * @property string|null $last_query_string
 * @property string|null $last_referrer
 * @property string|null $last_user_agent
 * @property int         $ignored
 * @property int|null    $resolved_redirect_id
 * @property string|null $resolved_at
 * @property string|null $first_seen_at
 * @property string|null $last_seen_at
 */
class RedirectLink extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'redirects_links', $config);
    }

    /**
     * @return self[]
     */
    public function allByStatus(string $status = 'active'): array
    {
        $query = new self($this->getDatabaseConnection());

        if ($status === 'active') {
            $query->eq('ignored', 0)->isNull('resolved_redirect_id');
        } elseif ($status === 'ignored') {
            $query->eq('ignored', 1);
        } elseif ($status === 'resolved') {
            $query->notNull('resolved_redirect_id');
        }

        return $query
            ->order('last_seen_at DESC')
            ->findAll();
    }

    /**
     * Find an entry by ID.
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
     * Find an entry by its source path.
     *
     * @param string $sourcePath
     * @return self|null
     */
    public function findBySourcePath(string $sourcePath): ?self
    {
        $this->reset();
        $this->eq('source_path', $sourcePath)->find();
        return $this->isHydrated() ? $this : null;
    }
}
