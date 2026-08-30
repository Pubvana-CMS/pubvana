<?php

/**
 * PluginInterface - Contract for plugin registration.
 *
 * Any plugin (local or vendor) that needs custom setup beyond config
 * and routes should implement this interface in Plugin.php.
 *
 * The PluginLoader calls register() after config and routes are loaded.
 * At this point, routes are already registered with the router, so
 * register() can assume the full application context is available.
 *
 * @package Pubvana\Services
 */

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;
use flight\net\Router;

interface PluginInterface
{
    /**
     * Called by the PluginLoader after config and routes are loaded.
     *
     * Use this for:
     *   - Registering services with the app ($app->map(), $app->set())
     *   - Registering hooks with adext ($app->adext()->register())
     *   - Loading helper functions
     *   - Any other custom setup
     *
     * @param Engine $app    The FlightPHP application instance
     * @param Router $router The FlightPHP router instance
     * @param array  $config Merged config (plugin defaults + app overrides)
     * @return void
     */
    public function register(Engine $app, Router $router, array $config = []): void;
}
