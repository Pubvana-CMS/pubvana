<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use PDO;

/**
 * Shared pages schema for Pages plugin tests.
 *
 * Mirrors the migration column shapes.
 */
final class PagesSchema
{
    private function __construct()
    {
    }

    public static function create(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE pages (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL UNIQUE,
                content        TEXT,
                status         TEXT NOT NULL DEFAULT 'draft',
                allow_comments INTEGER NOT NULL DEFAULT 0,
                ai_generated   INTEGER NOT NULL DEFAULT 0,
                created_by     INTEGER NOT NULL DEFAULT 0,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE pages_revisions (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                page_id        INTEGER NOT NULL,
                author_id      INTEGER NOT NULL,
                title          TEXT NOT NULL,
                content        TEXT,
                status         TEXT NOT NULL DEFAULT 'draft',
                allow_comments INTEGER NOT NULL DEFAULT 0,
                created_at     TEXT
            )"
        );
    }
}
