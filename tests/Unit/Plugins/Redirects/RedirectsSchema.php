<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Redirects;

use PDO;

/**
 * Shared redirects schema for Redirects plugin tests.
 *
 * Mirrors the migration column shapes.
 */
final class RedirectsSchema
{
    private function __construct()
    {
    }

    public static function create(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE redirects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                source_path TEXT NOT NULL,
                target_url TEXT NOT NULL,
                status_code INTEGER NOT NULL DEFAULT 301,
                enabled INTEGER NOT NULL DEFAULT 1,
                notes TEXT,
                hit_count INTEGER NOT NULL DEFAULT 0,
                last_hit_at TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
        $pdo->exec('CREATE UNIQUE INDEX redirects_source_path ON redirects (source_path)');

        $pdo->exec(
            'CREATE TABLE redirects_links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                source_path TEXT NOT NULL,
                hit_count INTEGER NOT NULL DEFAULT 0,
                last_query_string TEXT,
                last_referrer TEXT,
                last_user_agent TEXT,
                ignored INTEGER NOT NULL DEFAULT 0,
                resolved_redirect_id INTEGER,
                resolved_at TEXT,
                first_seen_at TEXT,
                last_seen_at TEXT
            )'
        );
        $pdo->exec('CREATE UNIQUE INDEX redirects_links_source_path ON redirects_links (source_path)');
    }
}
