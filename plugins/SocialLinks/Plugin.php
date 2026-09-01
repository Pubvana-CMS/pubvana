<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SocialLinks;

use Pubvana\Plugins\SocialLinks\Controllers\SocialLinksAdminController;
use Pubvana\Plugins\SocialLinks\Services\SocialLinksService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Social Links Plugin
 *
 * Central management of social profile links, admin screen under
 * Settings, and a public block rendered with self-hosted Font Awesome
 * 7 Free icons.
 *
 * @package Pubvana\Plugins\SocialLinks
 */
class Plugin implements PluginInterface
{
    /**
     * @param array<string, mixed> $config Merged plugin config (defaults + app overrides).
     */
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $app->map('socialLinks', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new SocialLinksService($app->db(), $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/social-links',                [SocialLinksAdminController::class, 'index'],   [$authMiddleware]],
            ['POST', '/social-links/store',          [SocialLinksAdminController::class, 'store'],   [$authMiddleware]],
            ['POST', '/social-links/@id/toggle',     [SocialLinksAdminController::class, 'toggle'],  [$authMiddleware]],
            ['POST', '/social-links/@id/delete',     [SocialLinksAdminController::class, 'delete'],  [$authMiddleware]],
            ['POST', '/social-links/@id/reorder',    [SocialLinksAdminController::class, 'reorder'], [$authMiddleware]],
        ], 'pubvana.social-links');

        // ─── Public Block ──────────────────────────────────────────────

        $adext->register('block', 'available', 'pubvana.social-links', [
            'label'       => 'Social Links',
            'description' => 'Your social profile links, managed in Settings',
            'provider'    => fn (array $options) => $app->socialLinks()->socialLinksBlock($options),
            'template'    => 'pubvana/social-links/public/blocks/social-links',
            'priority'    => 10,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Follow Us'],
            ],
        ]);

        // Font Awesome 7 Free icons (self-hosted) and block styles.
        // Base, brands and solid load in priority order, then the
        // plugin's own stylesheet on top.
        $adext->register('public.css', 'default', 'pubvana.social-links.fontawesome', [
            'url'      => '/assets/plugin/SocialLinks/css/fontawesome.min.css',
            'priority' => 5,
        ]);
        $adext->register('public.css', 'default', 'pubvana.social-links.brands', [
            'url'      => '/assets/plugin/SocialLinks/css/brands.min.css',
            'priority' => 10,
        ]);
        $adext->register('public.css', 'default', 'pubvana.social-links.solid', [
            'url'      => '/assets/plugin/SocialLinks/css/solid.min.css',
            'priority' => 15,
        ]);
        $adext->register('public.css', 'default', 'pubvana.social-links.block', [
            'url'      => '/assets/plugin/SocialLinks/css/social-links.css',
            'priority' => 20,
        ]);

        $adext->register('admin.css', 'default', 'pubvana.social-links.fontawesome', [
            'url'      => '/assets/plugin/SocialLinks/css/fontawesome.min.css',
            'priority' => 5,
        ]);
        $adext->register('admin.css', 'default', 'pubvana.social-links.brands', [
            'url'      => '/assets/plugin/SocialLinks/css/brands.min.css',
            'priority' => 10,
        ]);
        $adext->register('admin.css', 'default', 'pubvana.social-links.solid', [
            'url'      => '/assets/plugin/SocialLinks/css/solid.min.css',
            'priority' => 15,
        ]);
    }
}