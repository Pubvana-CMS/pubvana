<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Blog;

use PDO;

/**
 * Shared blog schema for Blog plugin tests.
 *
 * Mirrors the migration column shapes. The blog tables live outside the
 * shared Sqlite schema (AiAssistant defines its own narrower posts table),
 * so Blog suites create these per test.
 */
final class BlogSchema
{
    private function __construct()
    {
    }

    public static function create(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                title          TEXT NOT NULL,
                slug           TEXT NOT NULL UNIQUE,
                content        TEXT,
                excerpt        TEXT,
                status         TEXT NOT NULL DEFAULT 'draft',
                featured_image TEXT,
                media_id       INTEGER,
                author_id      INTEGER NOT NULL,
                published_at   TEXT,
                views          INTEGER NOT NULL DEFAULT 0,
                is_featured    INTEGER NOT NULL DEFAULT 0,
                allow_comments INTEGER NOT NULL DEFAULT 1,
                ai_generated   INTEGER NOT NULL DEFAULT 0,
                preview_token  TEXT UNIQUE,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE categories (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                name        TEXT NOT NULL,
                slug        TEXT NOT NULL UNIQUE,
                description TEXT,
                parent_id   INTEGER,
                created_at  TEXT,
                updated_at  TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE tags (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT NOT NULL,
                slug       TEXT NOT NULL UNIQUE,
                created_at TEXT,
                updated_at TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE posts_to_categories (
                post_id     INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                UNIQUE (post_id, category_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE tags_to_posts (
                tag_id  INTEGER NOT NULL,
                post_id INTEGER NOT NULL,
                UNIQUE (tag_id, post_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE post_revisions (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id    INTEGER NOT NULL,
                author_id  INTEGER NOT NULL,
                title      TEXT,
                content    TEXT,
                excerpt    TEXT,
                status     TEXT,
                created_at TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE profiles (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER NOT NULL,
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
            )"
        );
    }
}
