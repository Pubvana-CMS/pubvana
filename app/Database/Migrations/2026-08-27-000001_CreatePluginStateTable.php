<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreatePluginStateTable - Migration for the plugin manager's state store.
 *
 * One row per discovered plugin (local or vendor). This table is the single
 * source of truth for whether a plugin is enabled, its load priority, and
 * whether it is a required part of the core stack (sessions/shield/csrf).
 *
 * Plugin IDs are Composer-style names ('enlivenapp/flight-sessions',
 * 'pubvana/blog') so they contain '/' and '-' — they are stored here and never
 * used as settings keys.
 *
 * A disabled plugin runs nothing: no migrations, no seeds, no registration,
 * until an admin explicitly enables it on the Plugins admin page.
 *
 * Schema:
 *   id         - Auto-increment primary key
 *   plugin_id  - Composer-style plugin/package ID (unique)
 *   enabled    - Whether the plugin loads on the next request
 *   priority   - Load order (lower runs earlier), default 50
 *   required   - Hard lock: cannot be disabled (sessions/shield/csrf)
 *   created_at - Row creation time
 *   updated_at - Last write time
 *
 * @package Pubvana\Database\Migrations
 */
class CreatePluginStateTable extends Migration
{
    /**
     * Create the plugin_state table.
     *
     * UNIQUE constraint on `plugin_id` guarantees one row per plugin.
     * INDEX on `enabled` serves the boot-time enabled-set query.
     */
    public function change(): void
    {
        $this->table('plugin_state')
            ->addColumn('id', 'primary')
            ->addColumn('plugin_id', 'string', ['length' => 190])
            ->addColumn('enabled', 'boolean', ['default' => false])
            ->addColumn('priority', 'integer', ['default' => 50])
            ->addColumn('required', 'boolean', ['default' => false])
            ->addColumn('created_at', 'datetime', ['nullable' => true])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addIndex(['plugin_id'], ['unique' => true])
            ->addIndex(['enabled'])
            ->create();
    }
}