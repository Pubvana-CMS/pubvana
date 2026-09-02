<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int $post_id
 * @property int $category_id
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEq(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self in(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 */
class PostCategory extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'posts_to_categories', $config);
    }

    /**
     * @return array<int, int>
     */
    public function getCategoryIds(int $postId): array
    {
        $rows = (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->findAll();

        return array_map(fn($r) => (int) $r->category_id, $rows);
    }

    /**
     * @param array<int, int> $categoryIds
     */
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
