<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Models;

/**
 * @property int         $id
 * @property int         $post_id
 * @property int         $author_id
 * @property string      $title
 * @property string|null $content
 * @property string|null $excerpt
 * @property string      $status
 * @property string      $created_at
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
class PostRevision extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'post_revisions', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * @return array<int, PostRevision>
     */
    public function getForPost(int $postId): array
    {
        return (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->order('id DESC')
            ->findAll();
    }

    public function createFromPost(Post $post, int $authorId): self
    {
        $record = new self($this->getDatabaseConnection());
        $record->post_id          = (int) $post->id;
        $record->author_id        = $authorId;
        $record->title            = $post->title;
        $record->content          = $post->content;
        $record->excerpt          = $post->excerpt;
        $record->status           = $post->status;
        $record->created_at       = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $record->insert();

        return $record;
    }

    public function pruneForPost(int $postId, int $max = 15): void
    {
        $all = (new self($this->getDatabaseConnection()))
            ->eq('post_id', $postId)
            ->order('id DESC')
            ->findAll();

        if (count($all) <= $max) {
            return;
        }

        $toDelete = array_slice($all, $max);
        foreach ($toDelete as $revision) {
            $revision->delete();
        }
    }
}
