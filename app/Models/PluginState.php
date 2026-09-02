<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * PluginState - ActiveRecord model for the plugin_state table.
 *
 * One row per discovered plugin (local or vendor). This is the single source
 * of truth for enable/disable, load priority, and the required flag.
 *
 * Plugin IDs are Composer-style names ('enlivenapp/flight-sessions',
 * 'pubvana/blog') — they contain '/' and '-', so they are stored here and
 * must never be used as settings keys.
 *
 * A disabled plugin runs nothing (no migrations, seeds, or registration code)
 * until an admin explicitly enables it on the Plugins admin page.
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 *
 * @package Pubvana\Models
 */
class PluginState extends AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'plugin_state', $config);
    }

    public int $id;
    public string $plugin_id;
    public bool $enabled = false;
    public int $priority = 50;
    public bool $required = false;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Find one state row by its plugin ID.
     *
     * @param string $pluginId Composer-style plugin/package ID
     * @return self|null Hydrated row, or null when no row exists
     */
    public function findByPluginId(string $pluginId): ?self
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('plugin_id', $pluginId)->find();

        return $model->isHydrated() ? $model : null;
    }

    /**
     * Load every plugin_state row in a single query, indexed by plugin_id.
     *
     * Replaces the per-plugin findByPluginId() loop in PluginLoader::syncPluginStates()
     * with one boot-time bulk fetch. Saves (plugin_count - 1) queries per request.
     *
     * @return array<string, self> Hydrated rows keyed by plugin_id
     */
    public function getAllByPluginId(): array
    {
        $model = new self($this->getDatabaseConnection());
        $rows = $model->findAll();
        $byId = [];
        foreach ($rows as $row) {
            $byId[$row->plugin_id] = $row;
        }
        return $byId;
    }
}