<?php

namespace App\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table         = 'posts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'title', 'slug', 'content', 'content_type', 'excerpt', 'status',
        'featured_image', 'media_id', 'author_id', 'published_at', 'views', 'is_featured', 'is_premium',
        'meta_title', 'meta_description', 'lang', 'share_on_publish', 'preview_token',
    ];

    public function published(): static
    {
        return $this->where('status', 'published')->where('published_at <=', date('Y-m-d H:i:s'));
    }

    public function featured(): static
    {
        return $this->where('is_featured', 1);
    }

    public function byCategory(int $id): static
    {
        return $this->join('posts_to_categories ptc', 'ptc.post_id = posts.id')
                    ->where('ptc.category_id', $id);
    }

    public function byTag(int $id): static
    {
        return $this->join('tags_to_posts ttp', 'ttp.post_id = posts.id')
                    ->where('ttp.tag_id', $id);
    }

    public function byAuthor(int $id): static
    {
        return $this->where('author_id', $id);
    }

    public function incrementViews(int $id): void
    {
        $this->set('views', 'views + 1', false)->where('id', $id)->update();
    }

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    public function generateToken(int $id): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update($id, ['preview_token' => $token]);
        return $token;
    }

    public function findByPreviewToken(string $token): ?object
    {
        return $this->where('preview_token', $token)->first();
    }

    public function getRecent(int $limit = 5): array
    {
        return $this->published()
            ->orderBy('published_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    public function getArchiveList(string $format = 'monthly'): array
    {
        $db = db_connect();

        if ($format === 'yearly') {
            $rows = $db->table('posts')
                ->select('YEAR(published_at) as year, COUNT(*) as count')
                ->where('status', 'published')
                ->where('deleted_at IS NULL')
                ->groupBy('YEAR(published_at)')
                ->orderBy('year', 'DESC')
                ->limit(10)
                ->get()->getResultObject();
            foreach ($rows as $r) {
                $r->url   = site_url('archive/' . $r->year . '/1');
                $r->label = $r->year;
            }
        } else {
            $rows = $db->table('posts')
                ->select('YEAR(published_at) as year, MONTH(published_at) as month, COUNT(*) as count')
                ->where('status', 'published')
                ->where('deleted_at IS NULL')
                ->groupBy(['YEAR(published_at)', 'MONTH(published_at)'])
                ->orderBy('year', 'DESC')
                ->orderBy('month', 'DESC')
                ->limit(12)
                ->get()->getResultObject();
            foreach ($rows as $r) {
                $r->url   = site_url('archive/' . $r->year . '/' . $r->month);
                $r->label = date('F Y', mktime(0, 0, 0, $r->month, 1, $r->year));
            }
        }

        return (array) $rows;
    }

    public function getRelated(?int $postId, int $limit = 4): array
    {
        if (! $postId) {
            return [];
        }

        $db = db_connect();

        $catIds = array_column(
            $db->table('posts_to_categories')
               ->where('post_id', $postId)
               ->get()->getResultArray(),
            'category_id'
        );
        $tagIds = array_column(
            $db->table('tags_to_posts')
               ->where('post_id', $postId)
               ->get()->getResultArray(),
            'tag_id'
        );

        $scores = [];

        // +2 per shared category
        if ($catIds) {
            $rows = $db->table('posts_to_categories ptc')
                       ->select('ptc.post_id, p.title, p.slug, p.featured_image, p.published_at')
                       ->join('posts p', 'p.id = ptc.post_id')
                       ->whereIn('ptc.category_id', $catIds)
                       ->where('ptc.post_id !=', $postId)
                       ->where('p.status', 'published')
                       ->get()->getResultArray();
            foreach ($rows as $row) {
                $id = $row['post_id'];
                if (! isset($scores[$id])) {
                    $scores[$id] = array_merge($row, ['score' => 0]);
                }
                $scores[$id]['score'] += 2;
            }
        }

        // +1 per shared tag
        if ($tagIds) {
            $rows = $db->table('tags_to_posts ttp')
                       ->select('ttp.post_id, p.title, p.slug, p.featured_image, p.published_at')
                       ->join('posts p', 'p.id = ttp.post_id')
                       ->whereIn('ttp.tag_id', $tagIds)
                       ->where('ttp.post_id !=', $postId)
                       ->where('p.status', 'published')
                       ->get()->getResultArray();
            foreach ($rows as $row) {
                $id = $row['post_id'];
                if (! isset($scores[$id])) {
                    $scores[$id] = array_merge($row, ['score' => 0]);
                }
                $scores[$id]['score'] += 1;
            }
        }

        if (! $scores) {
            return [];
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_values($scores), 0, $limit);
    }
}
