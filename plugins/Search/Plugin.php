<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Search;

use Pubvana\Plugins\Search\Controllers\SearchPublicController;
use Pubvana\Plugins\Search\Controllers\SearchAdminController;
use Pubvana\Plugins\Search\Services\SearchService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Search Plugin - Registers routes, the search service, and the search
 * form block.
 *
 * @package Pubvana\Plugins\Search
 */
class Plugin implements PluginInterface
{
    /**
     * Register the plugin's routes and services.
     *
     * @param Engine $app    Flight application
     * @param Router $router Flight router (unused, routes go through adext)
     * @param array  $config Plugin configuration
     */
    public function register(Engine $app, Router $router, array $config = []): void
    {
        // Search service facade
        $app->map('search', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new SearchService($app);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null; // Disabled for development

        // Admin route — adext auto-prefixes with /admin
        $adext->addRoutes('admin', [
            ['GET',    '/search',       [SearchAdminController::class, 'index'],  [$authMiddleware]],
            ['POST',   '/search',       [SearchAdminController::class, 'save'],   [$authMiddleware]],
        ], 'pubvana.search');

        // Public route — root-level /search
        $adext->addRoutes('public', [
            ['GET', '/search', [SearchPublicController::class, 'search']],
        ], 'pubvana.search');

        // Search form block - placeable in any theme region
        $adext->register('block', 'available', 'pubvana.search.form', [
            'label'       => 'Search Form',
            'description' => 'Site search form',
            'template'    => 'pubvana/search/public/blocks/search',
            'priority'    => 10,
            'options'     => [
                'action'      => ['type' => 'input', 'label' => 'Form Action URL', 'default' => '/search'],
                'label'       => ['type' => 'input', 'label' => 'Label', 'default' => 'Search'],
                'placeholder' => ['type' => 'input', 'label' => 'Placeholder', 'default' => 'Search...'],
                'button_text' => ['type' => 'input', 'label' => 'Button Text', 'default' => 'Go'],
            ],
        ]);
    }
}
