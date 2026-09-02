<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics\Models;

/**
 * PageView - A single recorded page view event.
 *
 * @property int         $id
 * @property string      $page_path
 * @property string      $page_group
 * @property string|null $referrer_domain
 * @property string      $viewed_at
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 */
class PageView extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'analytics_page_views', $config);
    }

    /**
     * Find a page view event by ID.
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
}