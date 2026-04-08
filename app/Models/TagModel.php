<?php

namespace App\Models;

use CodeIgniter\Model;

class TagModel extends Model
{
    protected $table      = 'tags';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['name', 'slug'];

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    public function getWithPostCount(int $limit = 0): array
    {
        $builder = $this->db->table('tags t')
            ->select('t.*, COUNT(ttp.post_id) as post_count')
            ->join('tags_to_posts ttp', 'ttp.tag_id = t.id', 'left')
            ->join('posts p', 'p.id = ttp.post_id AND p.status = "published" AND p.deleted_at IS NULL', 'left')
            ->groupBy('t.id')
            ->orderBy('t.name', 'ASC');

        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultObject();
    }

    /**
     * Remove all post associations for a tag (pivot cleanup before tag delete).
     */
    public function deletePostLinks(int $tagId): void
    {
        $this->db->table('tags_to_posts')->where('tag_id', $tagId)->delete();
    }
}
