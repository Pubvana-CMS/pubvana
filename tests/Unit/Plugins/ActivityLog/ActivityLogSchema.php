<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\ActivityLog;

use PDO;

/**
 * Shared activity_logs schema for ActivityLog plugin tests.
 *
 * Mirrors the migration column shapes.
 */
final class ActivityLogSchema
{
    private function __construct()
    {
    }

    public static function create(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                user_name TEXT NOT NULL,
                action TEXT NOT NULL,
                entity_type TEXT NOT NULL,
                entity_id INTEGER,
                entity_name TEXT NOT NULL,
                details TEXT,
                ip TEXT NOT NULL,
                user_agent TEXT,
                created_at TEXT
            )'
        );
    }
}
