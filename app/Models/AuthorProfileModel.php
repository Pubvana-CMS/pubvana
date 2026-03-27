<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthorProfileModel extends Model
{
    protected $table            = 'author_profiles';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id', 'display_name', 'bio', 'avatar',
        'website', 'twitter', 'facebook', 'linkedin',
    ];

    public function getByUserId(int $userId): ?object
    {
        return $this->where('user_id', $userId)->first();
    }

    public function upsert(int $userId, array $data): void
    {
        $existing = $this->getByUserId($userId);
        if ($existing) {
            $this->where('user_id', $userId)->set($data)->update();
        } else {
            $data['user_id'] = $userId;
            $this->insert($data);
        }
    }

    public function getForCurrentPost(): ?object
    {
        // Only render on single-post pages: /post/{slug}
        $segments = service('uri')->getSegments(); // 0-indexed plain array
        $seg1     = $segments[0] ?? '';
        $slug     = $segments[1] ?? '';

        if ($seg1 !== 'post' || empty($slug)) {
            return null;
        }

        $post = (new PostModel())->published()->findBySlug($slug);
        if (! $post || empty($post->author_id)) {
            return null;
        }

        $profile = $this->getByUserId((int) $post->author_id);
        if (! $profile) {
            return null;
        }

        // Attach email/username for gravatar fallback
        $userRow = db_connect()->table('users u')
            ->select('u.username, ai.secret AS email')
            ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = \'email_password\'', 'left')
            ->where('u.id', $post->author_id)
            ->get()->getRowObject();

        if ($userRow) {
            $profile->username = $userRow->username;
            $profile->email    = $userRow->email;
        }

        return $profile;
    }
}
