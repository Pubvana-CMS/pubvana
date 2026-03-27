<?php

namespace App\Models;

use CodeIgniter\Model;

class CommentModel extends Model
{
    protected $table      = 'comments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'post_id', 'author_name', 'author_email', 'content',
        'status', 'parent_id', 'user_id',
    ];

    public function approved(): static
    {
        return $this->where('status', 'approved');
    }

    public function pending(): static
    {
        return $this->where('status', 'pending');
    }

    public function getTree(int $postId, int $maxDepth = 3): array
    {
        $flat = $this->where('post_id', $postId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return $this->buildTree($flat, null, 0, $maxDepth);
    }

    protected function buildTree(array $flat, ?int $parentId = null, int $depth = 0, int $maxDepth = 3): array
    {
        if ($depth >= $maxDepth) {
            return [];
        }
        $tree = [];
        foreach ($flat as $item) {
            $pid = isset($item->parent_id) ? (int) $item->parent_id : null;
            if ($pid === $parentId) {
                $item->children = $this->buildTree($flat, (int) $item->id, $depth + 1, $maxDepth);
                $tree[]         = $item;
            }
        }
        return $tree;
    }

    public function getRecentApproved(int $limit = 5): array
    {
        return $this->db->table('comments c')
            ->select('c.author_name, c.content, c.created_at, p.slug as post_slug, p.title as post_title')
            ->join('posts p', 'p.id = c.post_id')
            ->where('c.status', 'approved')
            ->orderBy('c.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultObject();
    }
}
