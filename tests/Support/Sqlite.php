<?php

declare(strict_types=1);

namespace Pubvana\Tests\Support;

use PDO;

/**
 * SQLite in-memory database for DB-backed tests.
 *
 * ActiveRecord talks to whatever PDO it is handed, so an in-memory SQLite
 * connection works for model integration tests without touching the
 * MySQL-only migration runner. The schema is created directly here from
 * the same table shapes the migrations define.
 *
 * The connection is shared as a singleton so every model in a given test
 * run operates on the same in-memory database. Tests that need a clean
 * slate should recreate it via recreate() in setUp().
 */
final class Sqlite
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    /**
     * Get (creating on first use) the shared in-memory PDO connection
     * with its schema in place.
     */
    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::createSchema(self::$pdo);
        }

        return self::$pdo;
    }

    /**
     * Drop every table and recreate the schema, giving tests a clean
     * database. Existing table data is discarded.
     */
    public static function recreate(): PDO
    {
        $pdo = self::connection();

        self::$pdo->exec('PRAGMA foreign_keys = OFF');
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
        }

        self::createSchema($pdo);
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * Build the schema for every core table. Mirrors the column shapes
     * defined in app/Database/Migrations/*.
     */
    private static function createSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE settings (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                key         TEXT NOT NULL UNIQUE,
                value       TEXT,
                type        TEXT NOT NULL DEFAULT \'string\',
                autoload    INTEGER NOT NULL DEFAULT 1,
                created_at  TEXT,
                updated_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE themes (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                name             TEXT NOT NULL,
                folder           TEXT NOT NULL,
                description      TEXT,
                version          TEXT,
                author           TEXT,
                screenshot       TEXT,
                is_active        INTEGER NOT NULL DEFAULT 0,
                disabled         INTEGER,
                disabled_reason  TEXT,
                installed_at     TEXT,
                created_at       TEXT,
                updated_at       TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE theme_options (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                theme_id      INTEGER NOT NULL,
                option_key    TEXT NOT NULL,
                option_value  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE navigation (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                label       TEXT NOT NULL,
                url         TEXT NOT NULL,
                parent_id   INTEGER,
                sort_order  INTEGER NOT NULL DEFAULT 0,
                target      TEXT NOT NULL DEFAULT \'_self\',
                nav_group   TEXT NOT NULL DEFAULT \'primary\',
                created_at  TEXT,
                updated_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE block_placements (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                region_id   TEXT NOT NULL,
                block_key   TEXT NOT NULL,
                sort_order  INTEGER NOT NULL DEFAULT 0,
                options     TEXT,
                created_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE mail_logs (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                to_address    TEXT NOT NULL,
                subject       TEXT NOT NULL,
                from_address  TEXT,
                transport     TEXT NOT NULL DEFAULT \'smtp\',
                status        TEXT NOT NULL DEFAULT \'sent\',
                error         TEXT,
                sent_at       TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE plugin_state (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                plugin_id   TEXT NOT NULL UNIQUE,
                enabled     INTEGER NOT NULL DEFAULT 0,
                priority    INTEGER NOT NULL DEFAULT 50,
                required    INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT,
                updated_at  TEXT
            )'
        );
    }
}
