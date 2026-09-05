<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Forms;

use Pubvana\Plugins\Forms\Controllers\FormsAdminController;
use Pubvana\Plugins\Forms\Controllers\FormSubmissionsAdminController;
use Pubvana\Plugins\Forms\Controllers\FormsPublicController;
use Pubvana\Plugins\Forms\Services\FormsService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Forms Plugin - Form builder, public embeds, and submission review.
 *
 * @package Pubvana\Plugins\Forms
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $app->map('forms', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new FormsService($app->db(), $app, $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;
        $prefix = $app->pluginLoader()->routePrefix('pubvana/forms');

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/forms',                       [FormsAdminController::class, 'index'],            [$authMiddleware]],
            ['GET',  '/forms/create',                [FormsAdminController::class, 'create'],           [$authMiddleware]],
            ['POST', '/forms/store',                 [FormsAdminController::class, 'store'],            [$authMiddleware]],
            ['GET',  '/forms/@id/edit',              [FormsAdminController::class, 'edit'],             [$authMiddleware]],
            ['POST', '/forms/@id/update',            [FormsAdminController::class, 'update'],           [$authMiddleware]],
            ['POST', '/forms/@id/delete',            [FormsAdminController::class, 'delete'],           [$authMiddleware]],
            ['GET',  '/forms/submissions',           [FormSubmissionsAdminController::class, 'index'],  [$authMiddleware]],
            ['GET',  '/forms/@formId/submissions',   [FormSubmissionsAdminController::class, 'index'],  [$authMiddleware]],
            ['GET',  '/forms/submissions/@id',       [FormSubmissionsAdminController::class, 'show'],   [$authMiddleware]],
        ], 'pubvana.forms');

        // ─── Public Routes ──────────────────────────────────────────────

        $adext->addRoutes('public', [
            ['POST', $prefix . '/submit/@id', [FormsPublicController::class, 'submit']],
        ], 'pubvana.forms');

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.forms', [
            'label'    => 'Forms',
            'priority' => 40,
            'callable' => fn(array $context) => $app->forms()->dashboardCards(),
        ]);

        // ─── Content Embeds (shortcodes) ────────────────────────────────

        $adext->register('content.render', 'default', 'pubvana.forms', [
            'label'    => 'Form shortcodes',
            'priority' => 40,
            'callable' => fn(array $context) => $app->forms()->renderContentEmbeds((string) ($context['content'] ?? '')),
        ]);

        // ─── Public CSS ─────────────────────────────────────────────────

        // Served from assets/css/forms.css
        $adext->register('public.css', 'default', 'pubvana.forms', [
            'url'      => '/assets/plugin/Forms/css/forms.css',
            'priority' => 50,
        ]);

        // ─── Block ──────────────────────────────────────────────────────

        $adext->register('block', 'available', 'pubvana.forms.form', [
            'label'       => 'Form',
            'description' => 'Embed a published form',
            'provider'    => function (array $options) use ($app) {
                return [
                    'title'   => $options['title'] ?? '',
                    'content' => $app->forms()->renderBlock(
                        $options['form_id'] ?? null,
                        $options['form_slug'] ?? null
                    ),
                ];
            },
            'template'    => 'pubvana/forms/public/blocks/form',
            'priority'    => 40,
            'options'     => [
                'title'     => ['type' => 'input', 'label' => 'Title Override', 'default' => ''],
                'form_id'   => ['type' => 'input', 'label' => 'Form ID', 'default' => ''],
                'form_slug' => ['type' => 'input', 'label' => 'Form Slug', 'default' => ''],
            ],
        ]);
    }
}
