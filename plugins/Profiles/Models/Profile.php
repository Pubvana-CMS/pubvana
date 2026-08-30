<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Models;

class Profile extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'profiles', $config);
    }

    public function findByUserId(int $userId): ?self
    {
        $this->reset();
        $this->eq('user_id', $userId)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findOrCreate(int $userId): self
    {
        $profile = $this->findByUserId($userId);
        if ($profile !== null) {
            return $profile;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $new = new self($this->getDatabaseConnection());
        $new->user_id    = $userId;
        $new->created_at = $now;
        $new->updated_at = $now;
        $new->insert();

        return $new;
    }

    public function updateProfile(int $userId, array $data): self
    {
        $profile = $this->findOrCreate($userId);
        $profile->updateFromArray($data);
        return $profile;
    }

    public function updateFromArray(array $data): void
    {
        $allowed = ['display_name', 'bio', 'avatar', 'website', 'twitter', 'facebook', 'linkedin', 'job_title', 'works_for'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $this->$field = trim($data[$field]) ?: null;
            }
        }
        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }
}
