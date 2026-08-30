<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreateSettingsTable - Migration for the runtime settings store.
 *
 * Database-backed settings are the strongest source in the settings
 * precedence chain, superseding config files. Only DECLARED settings
 * (registered via adext type 'admin.settings') may exist here; secrets
 * and deployment-level values are never declared, so they can never
 * be stored or edited through the admin UI.
 *
 * Rows materialize lazily: a row is written the first time a setting is
 * saved through the admin UI or SettingsService::set(). Until then,
 * values resolve down the chain (.env > config.php > declaration default).
 *
 * Schema:
 *   id         - Auto-increment primary key
 *   key        - Namespaced key, e.g. 'CMS.siteName' or 'blog.postsPerPage'
 *   value      - Stored value. Scalars stored human-readable; arrays/objects JSON
 *   type       - PHP type recorded for cast-back on read (gettype())
 *   autoload   - When true, the row loads in the boot-time bulk query
 *   created_at - Row creation time
 *   updated_at - Last write time
 *
 * @package Pubvana\Database\Migrations
 */
class CreateSettingsTable extends Migration
{
    /**
     * Create the settings table.
     *
     * UNIQUE constraint on `key` guarantees one row per setting - the
     * structural guarantee against duplicate settings. INDEX on autoload
     * serves the single boot-time bulk query.
     */
    public function change(): void
    {
        $this->table('settings')
            ->addColumn('id', 'primary')
            ->addColumn('key', 'string', ['length' => 190])
            ->addColumn('value', 'text', ['nullable' => true])
            ->addColumn('type', 'string', ['length' => 31, 'default' => 'string'])
            ->addColumn('autoload', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['nullable' => true])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addIndex(['key'], ['unique' => true])
            ->addIndex(['autoload'])
            ->create();
    }
}
