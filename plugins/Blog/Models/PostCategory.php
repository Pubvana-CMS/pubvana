<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int $post_id
 * @property int $category_id
 */
class PostCategory extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'posts_to_categories', $config);
    }

    public function getCategoryIds(int $postId): array
    {
        $rows = (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->findAll();

        return array_map(fn($r) => (int) $r->category_id, $rows);
    }

    public function syncForPost(int $postId, array $categoryIds): void
    {
        (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->delete();

        foreach ($categoryIds as $catId) {
            $record = new self($this->getDatabaseConnection());
            $record->post_id = $postId;
            $record->category_id = (int) $catId;
            $record->insert();
        }
    }

    public function deleteForCategory(int $categoryId): void
    {
        (new self($this->getDatabaseConnection()))
            ->eq('category_id', $categoryId)
            ->delete();
    }
}
