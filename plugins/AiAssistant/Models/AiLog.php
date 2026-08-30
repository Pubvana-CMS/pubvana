<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Models;

/**
 * AiLog - AI Assistant audit entry (ai_logs table).
 *
 * One row per request to the /ai/* API, including authentication
 * failures and denied (ungranted) attempts. Key identity is snapshotted
 * by name so the audit trail survives key deletion.
 *
 * Schema:
 *   id          - Auto-increment primary key
 *   key_id      - FK to ai_keys.id (nullable on failures)
 *   key_name    - Snapshot of the key name (survives key deletion)
 *   method      - HTTP method (GET/POST)
 *   endpoint    - Request path under /ai/*
 *   entity_type - Entity acted on (post, page, comment, redirect, ...)
 *   entity_id   - Target entity id when applicable
 *   outcome     - 'ok', 'denied', or 'error'
 *   detail      - Human detail (denied permission, error message, ...)
 *   ip          - Client IP address
 *   created_at  - Request timestamp
 *
 * @package Pubvana\Plugins\AiAssistant\Models
 */
class AiLog extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'ai_logs', $config);
    }

    /**
     * @param int $limit
     * @return self[] Most recent log entries, newest first.
     */
    public function recent(int $limit = 200): array
    {
        $limit = max(1, $limit);
        $model = new self($this->getDatabaseConnection());
        return $model->order('id DESC')->limit($limit)->findAll();
    }
}