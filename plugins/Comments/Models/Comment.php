<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Comments\Models;

/**
 * Comment - ActiveRecord model for the comments table.
 *
 * Supports nested (threaded) comments via parent_id, attribution to either
 * a registered user (user_id) or a guest (guest_name/guest_email/guest_website),
 * and moderation via the status column (pending | approved | rejected).
 *
 * @property int         $id
 * @property string      $commentable_type
 * @property int         $commentable_id
 * @property int|null    $parent_id
 * @property int|null    $user_id
 * @property string|null $guest_name
 * @property string|null $guest_email
 * @property string|null $guest_website
 * @property string      $body
 * @property string      $status
 * @property string|null $ip_address
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Comment extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'comments', $config);
    }

    /**
     * Find a single comment by ID.
     */
    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * Find all comments for a content item, ordered for tree building.
     */
    public function findByContent(string $type, int $id, ?string $status = 'approved'): array
    {
        $query = (new self($this->getDatabaseConnection()))
            ->eq('commentable_type', $type)
            ->eq('commentable_id', $id)
            ->order('created_at ASC');

        if ($status !== null) {
            $query->eq('status', $status);
        }

        return $query->findAll();
    }

    /**
     * Paginated listing with optional status filter.
     */
    public function paginate(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        $query = (new self($this->getDatabaseConnection()))
            ->order('created_at DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage);

        if ($status !== null) {
            $query->eq('status', $status);
        }

        return $query->findAll();
    }

    /**
     * Count comments, optionally filtered by status.
     */
    public function countByStatus(?string $status = null): int
    {
        $query = (new self($this->getDatabaseConnection()))
            ->select('COUNT(*) as cnt');

        if ($status !== null) {
            $query->eq('status', $status);
        }

        $result = $query->find();
        return (int) $result->cnt;
    }

    /**
     * Count comments grouped by commentable_type, optionally filtered by status.
     *
     * @return array<string, int> commentable_type => count
     */
    public function countByType(?string $status = null): array
    {
        $sql     = 'SELECT commentable_type, COUNT(*) as cnt FROM comments';
        $params  = [];
        $wheres  = [];

        if ($status !== null) {
            $wheres[] = 'status = :status';
            $params[':status'] = $status;
        }

        if (!empty($wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        $sql .= ' GROUP BY commentable_type';

        $out = [];
        foreach ($this->query($sql, $params) as $row) {
            $out[(string) $row->commentable_type] = (int) $row->cnt;
        }
        return $out;
    }

    /**
     * Create a new comment from an array of data.
     */
    public function createRecord(array $data): self
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $record = new self($this->getDatabaseConnection());

        foreach ($data as $key => $value) {
            $record->$key = $value;
        }

        $record->created_at = $now;
        $record->updated_at = $now;
        $record->insert();

        return $record;
    }

    /**
     * Update a comment's status.
     */
    public function updateStatus(int $id, string $status): ?self
    {
        $record = (new self($this->getDatabaseConnection()))->findById($id);

        if ($record === null) {
            return null;
        }

        $record->status = $status;
        $record->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $record->save();

        return $record;
    }

    /**
     * Hard delete a comment by ID.
     */
    public function deleteById(int $id): bool
    {
        $record = (new self($this->getDatabaseConnection()))->findById($id);

        if ($record === null) {
            return false;
        }

        $record->delete();
        return true;
    }

    /**
     * Get direct children of a comment.
     */
    public function getChildren(int $parentId): array
    {
        return (new self($this->getDatabaseConnection()))
            ->eq('parent_id', $parentId)
            ->order('created_at ASC')
            ->findAll();
    }

    /**
     * Calculate the nesting depth of a comment by walking the parent chain.
     */
    public function getDepth(int $commentId): int
    {
        $depth = 0;
        $current = (new self($this->getDatabaseConnection()))->findById($commentId);

        while ($current !== null && $current->parent_id !== null) {
            $depth++;
            $current = (new self($this->getDatabaseConnection()))->findById((int) $current->parent_id);
        }

        return $depth;
    }
}
