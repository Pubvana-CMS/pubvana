<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Profiles;

use PDO;

/**
 * Shared profiles schema for Profiles plugin tests.
 *
 * Mirrors the migration column shapes.
 */
final class ProfilesSchema
{
    private function __construct()
    {
    }

    public static function create(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE profiles (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER NOT NULL UNIQUE,
                display_name TEXT,
                bio          TEXT,
                avatar       TEXT,
                website      TEXT,
                twitter      TEXT,
                facebook     TEXT,
                linkedin     TEXT,
                job_title    TEXT,
                works_for    TEXT,
                created_at   TEXT,
                updated_at   TEXT
            )'
        );
    }
}
