<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SocialLinks\Models;

/**
 * @property int         $id
 * @property string      $platform
 * @property string      $label
 * @property string      $url
 * @property string      $icon
 * @property int         $sort_order
 * @property int         $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class SocialLink extends \flight\ActiveRecord
{
    /**
     * @param \PDO|\flight\database\DatabaseInterface|null $pdo Optional database connection,
     *                                                          defaults to the app connection.
     * @param array<string, mixed>                          $config
     */
    public function __construct(\PDO|\flight\database\DatabaseInterface|null $pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'social_links', $config);
    }

    /**
     * Every link, ordered for admin display and reordering.
     *
     * @return self[]
     */
    public function allOrdered(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->order('sort_order ASC, id ASC')
            ->findAll();
    }

    /**
     * Active links only, ordered for public display.
     *
     * @return self[]
     */
    public function activeOrdered(): array
    {
        return (new self($this->getDatabaseConnection()))
            ->eq('is_active', 1)
            ->order('sort_order ASC, id ASC')
            ->findAll();
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }
}