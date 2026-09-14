<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\SocialLinks;

use PDO;

/**
 * Shared social_links schema for SocialLinks plugin tests.
 *
 * Mirrors the migration column shapes.
 */
final class SocialLinksSchema
{
    private function __construct()
    {
    }

    public static function create(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE social_links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                platform TEXT NOT NULL,
                label TEXT NOT NULL,
                url TEXT NOT NULL,
                icon TEXT NOT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT,
                updated_at TEXT
            )'
        );
    }
}
