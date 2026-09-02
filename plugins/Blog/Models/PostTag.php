<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int $tag_id
 * @property int $post_id
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
class PostTag extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'tags_to_posts', $config);
    }

    /**
     * @return array<int, int>
     */
    public function getTagIds(int $postId): array
    {
        $rows = (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->findAll();

        return array_map(fn($r) => (int) $r->tag_id, $rows);
    }

    /**
     * @param array<int, int> $tagIds
     */
    public function syncForPost(int $postId, array $tagIds): void
    {
        (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->delete();

        foreach ($tagIds as $tagId) {
            $record = new self($this->getDatabaseConnection());
            $record->tag_id = (int) $tagId;
            $record->post_id = $postId;
            $record->insert();
        }
    }

    public function deleteForTag(int $tagId): void
    {
        (new self($this->getDatabaseConnection()))
            ->eq('tag_id', $tagId)
            ->delete();
    }
}
