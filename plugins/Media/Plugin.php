<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Media;

use Pubvana\Plugins\Media\Controllers\MediaAdminController;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $app->map('media', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $publicPath = PROJECT_ROOT . '/public';
                $instance = new Services\MediaService(
                    $app->db(),
                    $config,
                    $publicPath
                );
            }
            return $instance;
        });

        $adext = $app->adext();
        // Media routes gate on the `media.manage` permission. Middleware is
        // null for development (auth off). To reinstate protection:
        //   use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
        //   $authMiddleware = new SessionAuthMiddleware($app) ...
        //   [new PermissionMiddleware($app, 'media.manage')]
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/media',                  [MediaAdminController::class, 'index'],       [$authMiddleware]],
            ['GET',  '/media/json',             [MediaAdminController::class, 'json'],        [$authMiddleware]],
            ['GET',  '/media/capabilities',     [MediaAdminController::class, 'capabilities'],[$authMiddleware]],
            ['GET',  '/media/@id/editor',       [MediaAdminController::class, 'editor'],      [$authMiddleware]],
            ['POST', '/media/upload/image',     [MediaAdminController::class, 'uploadImage'], [$authMiddleware]],
            ['POST', '/media/upload/video',     [MediaAdminController::class, 'uploadVideo'], [$authMiddleware]],
            ['POST', '/media/embed',            [MediaAdminController::class, 'storeEmbed'],  [$authMiddleware]],
            ['POST', '/media/@id/poster',       [MediaAdminController::class, 'uploadPoster'],[$authMiddleware]],
            ['POST', '/media/@id/update',       [MediaAdminController::class, 'update'],      [$authMiddleware]],
            ['POST', '/media/@id/delete',       [MediaAdminController::class, 'destroy'],     [$authMiddleware]],
            ['POST', '/media/@id/edit',         [MediaAdminController::class, 'applyEdit'],   [$authMiddleware]],
            ['POST', '/media/@id/revert',       [MediaAdminController::class, 'revert'],      [$authMiddleware]],
        ], 'pubvana.media');

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.media', [
            'label'    => 'Media',
            'priority' => 40,
            'callable' => function (array $context) use ($app): array {
                $total = $app->media()->countAll();

                return [[
                    'id'          => 'media-items',
                    'label'       => 'Media Items',
                    'value'       => $total,
                    'icon'        => 'ti-photo',
                    'tone'        => 'secondary',
                    'group'       => 'media',
                    'href'        => '/media',
                    'description' => 'Assets available in the media library.',
                ]];
            },
        ]);

        $adext->register('admin.dashboard', 'sections', 'pubvana.media', [
            'label'    => 'Media',
            'priority' => 50,
            'callable' => function (array $context) use ($app): array {
                $items = [];
                foreach ($app->media()->recent(5) as $media) {
                    $format = trim((string) ($media->mime_type ?? '')) ?: ucfirst((string) $media->type);
                    $thumb  = null;
                    $thumbType = null;
                    if ($media->path) {
                        $abs = PROJECT_ROOT . '/public/' . $media->path;
                        if ($media->type === 'image') {
                            $dir   = dirname($media->path);
                            $hex   = pathinfo($media->path, PATHINFO_FILENAME);
                            $thumbAbs = PROJECT_ROOT . '/public/' . $dir . '/thumbs/' . $hex . '.webp';
                            if (file_exists($thumbAbs)) {
                                $thumb = '/' . $dir . '/thumbs/' . $hex . '.webp';
                            } elseif (file_exists($abs)) {
                                $thumb = '/' . $media->path;
                            }
                        } elseif ($media->type === 'video' && $media->poster_path
                                  && file_exists(PROJECT_ROOT . '/public/' . $media->poster_path)) {
                            $thumb = '/' . $media->poster_path;
                        }
                    }
                    if ($thumb === null) {
                        $thumbType = 'icon';
                    }
                    $items[] = [
                        'label'     => $media->title ?: $media->filename,
                        'sub'       => $format . ' · ' . date('M j, Y g:ia', strtotime((string) $media->created_at)),
                        'href'      => '/media',
                        'thumb'     => $thumb,
                        'thumb_type' => $thumbType,
                    ];
                }

                return [[
                    'id'          => 'recent-media',
                    'title'       => 'Recent Uploads',
                    'type'        => 'list',
                    'icon'        => 'ti-photo-up',
                    'group'       => 'media',
                    'href'        => '/media',
                    'empty_state' => 'The media library is still empty.',
                    'items'       => $items,
                ]];
            },
        ]);

    }
}
