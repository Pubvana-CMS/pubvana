<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int $tag_id
 * @property int $post_id
 */
class PostTag extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'tags_to_posts', $config);
    }

    public function getTagIds(int $postId): array
    {
        $rows = (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->findAll();

        return array_map(fn($r) => (int) $r->tag_id, $rows);
    }

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
