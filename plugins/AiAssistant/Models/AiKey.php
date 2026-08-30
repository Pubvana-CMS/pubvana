<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Models;

/**
 * AiKey - AI Assistant API key (ai_keys table).
 *
 * Only an HMAC hash of the key is stored at rest; the plaintext token is
 * shown once at creation and never stored. The key_prefix is a short,
 * non-secret display hint so admins can tell keys apart in the UI.
 *
 * Schema:
 *   id               - Auto-increment primary key
 *   name             - Human label for the key
 *   key_hash         - HMAC-SHA256 hash of the raw token (unique)
 *   key_prefix       - Display prefix of the raw token (non-secret)
 *   enabled          - 1 when the key can authenticate
 *   failed_attempts  - Consecutive failed attempts with this key
 *   blocked_until    - When set, the key is rejected until this moment
 *   last_used_at     - Most recent successful authentication
 *   created_at       - Creation timestamp
 *   updated_at       - Last update timestamp
 *
 * @package Pubvana\Plugins\AiAssistant\Models
 */
class AiKey extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'ai_keys', $config);
    }

    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->find();
        return $this->isHydrated() ? $this : null;
    }

    public function findByHash(string $hash): ?self
    {
        $this->reset();
        $this->eq('key_hash', $hash)->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * @return self[] All keys, newest first.
     */
    public function allOrdered(): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->order('created_at DESC, id DESC')->findAll();
    }

    public function isEnabled(): bool
    {
        return (int) $this->enabled === 1;
    }

    public function isBlocked(): bool
    {
        if ($this->blocked_until === null || (string) $this->blocked_until === '') {
            return false;
        }
        $until = strtotime((string) $this->blocked_until);
        return $until !== false && $until > time();
    }
}