<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Marketplace;

use PDO;

/**
 * Shared marketplace_installs schema for Marketplace plugin tests.
 *
 * Mirrors the migration column shapes (incl. package_id).
 */
final class MarketplaceSchema
{
    private function __construct()
    {
    }

    public static function create(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE marketplace_installs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_product_id INTEGER NOT NULL UNIQUE,
                package_id TEXT,
                product_name TEXT NOT NULL,
                slug TEXT,
                item_type TEXT NOT NULL DEFAULT "plugin",
                folder TEXT,
                installed_version TEXT,
                license_key TEXT,
                license_scope TEXT NOT NULL DEFAULT "single_site",
                license_valid INTEGER NOT NULL DEFAULT 0,
                license_last_checked TEXT,
                expires_at TEXT,
                renews_at TEXT,
                is_subscription INTEGER NOT NULL DEFAULT 0,
                registered_domain TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
    }
}
