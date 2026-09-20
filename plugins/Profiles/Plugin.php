<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles;

use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController;
use Pubvana\Plugins\Profiles\Controllers\ProfilesPublicController;
use Pubvana\Plugins\Profiles\Services\ProfileBlockService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/profiles');
        $config['route_prefix'] = $prefix;

        $app->map('profiles', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new Models\Profile($app->db(), $config);
            }
            return $instance;
        });

        $app->map('profileBlock', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new ProfileBlockService($app);
            }
            return $instance;
        });

        $adext = $app->adext();

        // Admin routes
        $adext->addRoutes('admin', [
            ['GET',  $prefix,                  [ProfilesAdminController::class, 'index'],  []],
            ['GET',  $prefix . '/@userId',     [ProfilesAdminController::class, 'show'],   []],
            ['POST', $prefix . '/@userId/update', [ProfilesAdminController::class, 'update'], []],
        ], 'pubvana.profiles');

        // Public routes
        $adext->addRoutes('public', [
            ['GET',  $prefix . '/@username',       [ProfilesPublicController::class, 'show']],
            ['GET',  $prefix . '/@username/edit',  [ProfilesPublicController::class, 'edit']],
            ['POST', $prefix . '/@username/update', [ProfilesPublicController::class, 'update']],
        ], 'pubvana.profiles');

        // Public CSS
        $adext->register('public.css', 'default', 'pubvana.profiles', [
            'url'      => '/assets/plugin/Profiles/css/profiles.css',
            'priority' => 50,
        ]);

        // ─── Blocks ─────────────────────────────────────────────────────

        $adext->register('block', 'available', 'pubvana.profiles.author-card', [
            'label'       => 'Author Card',
            'description' => 'Profile card for the current post or page author',
            'provider'    => fn(array $options) => $app->profileBlock()->provide($options),
            'template'    => 'author-card.tpl',
            'priority'    => 50,
            'options'     => [
                'show_on_blog'  => ['type' => 'toggle', 'label' => 'Show on blog posts', 'default' => 1],
                'show_on_pages' => ['type' => 'toggle', 'label' => 'Show on pages', 'default' => 0],
                'title'         => ['type' => 'input', 'label' => 'Title', 'default' => 'About the Author'],
                'show_avatar'   => ['type' => 'toggle', 'label' => 'Show avatar', 'default' => 1],
                'show_socials'  => ['type' => 'toggle', 'label' => 'Show social links', 'default' => 1],
            ],
        ]);
    }
}
