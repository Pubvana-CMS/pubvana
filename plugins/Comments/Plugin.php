<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Comments;

use Pubvana\Plugins\Comments\Controllers\CommentsAdminController;
use Pubvana\Plugins\Comments\Controllers\CommentsPublicController;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Comments Plugin - Registers the comment service, admin routes (moderation
 * queue + combined settings/host manager), dashboard, and block.
 *
 * Other plugins register their content as commentable by implementing the
 * 'comments.host' adext slot (see ExtensionRegistry::TYPES) and render the
 * thread via CommentService::render() / dataFor().
 *
 * @package Pubvana\Plugins\Comments
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        // Map the comments service as a singleton.
        $app->map('comments', function () use ($app) {
            static $instance = null;
            if ($instance === null) {
                $instance = new Services\CommentService($app->db(), $app);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  '/comments',             [CommentsAdminController::class, 'index'],            [$authMiddleware]],
            ['GET',  '/comments/settings',    [CommentsAdminController::class, 'settingsIndex'],    [$authMiddleware]],
            ['POST', '/comments/settings',    [CommentsAdminController::class, 'settingsSave'],     [$authMiddleware]],
            ['GET',  '/comments/@id',         [CommentsAdminController::class, 'show'],             [$authMiddleware]],
            ['POST', '/comments/@id/approve', [CommentsAdminController::class, 'approve'],          [$authMiddleware]],
            ['POST', '/comments/@id/reject',  [CommentsAdminController::class, 'reject'],           [$authMiddleware]],
            ['POST', '/comments/@id/delete',  [CommentsAdminController::class, 'delete'],           [$authMiddleware]],
        ], 'pubvana.comments');

        // ─── Public Routes ─────────────────────────────────────────────

        $prefix = $app->pluginLoader()->routePrefix('pubvana/comments');

        $adext->addRoutes('public', [
            ['GET',  $prefix . '/@type/@id', [CommentsPublicController::class, 'index'], []],
            ['POST', $prefix . '/@type/@id', [CommentsPublicController::class, 'store'], []],
        ], 'pubvana.comments');

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.comments', [
            'label'    => 'Comments',
            'priority' => 30,
            'callable' => function (array $context) use ($app): array {
                $pending = $app->comments()->countByStatus('pending');
                return [[
                    'id'          => 'pending-comments',
                    'label'       => 'Pending Comments',
                    'value'       => $pending,
                    'icon'        => 'ti-message-circle',
                    'tone'        => $pending > 0 ? 'warning' : 'secondary',
                    'group'       => 'content',
                    'href'        => '/comments?status=pending',
                    'description' => $pending > 0
                        ? 'Comments waiting for moderation.'
                        : 'No comments are waiting for review.',
                ]];
            },
        ]);

        $adext->register('admin.dashboard', 'sections', 'pubvana.comments', [
            'label'    => 'Comments',
            'priority' => 10,
            'callable' => function (array $context) use ($app): array {
                $pending = $app->comments()->list(1, 5, 'pending');
                $items = [];

                foreach ($pending as $comment) {
                    $author = $comment->guest_name ?: ($comment->user_id ? 'User #' . $comment->user_id : 'Anonymous');
                    $host = $app->comments()->hostItem((string) $comment->commentable_type, (int) $comment->commentable_id);
                    $label = ($host['title'] ?? $comment->commentable_type . ' #' . $comment->commentable_id);

                    $ts = strtotime((string) $comment->created_at);
                    $items[] = [
                        'label'    => $author . ' on ' . $label,
                        'meta'     => $comment->created_at && $ts !== false ? date('M j, Y g:ia', $ts) : '',
                        'href'     => '/comments/' . (int) $comment->id,
                        'emphasis' => 'warning',
                    ];
                }

                return [[
                    'id'          => 'comments-awaiting-review',
                    'title'       => 'Comments Awaiting Review',
                    'type'        => 'list',
                    'icon'        => 'ti-message-2-exclamation',
                    'tone'        => 'warning',
                    'group'       => 'content',
                    'href'        => '/comments?status=pending',
                    'empty_state' => 'No comments are waiting for review.',
                    'items'       => $items,
                ]];
            },
        ]);

        // ─── Blocks ─────────────────────────────────────────────────────

        $adext->register('block', 'available', 'pubvana.comments.recent', [
            'label'       => 'Recent Comments',
            'description' => 'Latest approved comments across the site',
            'provider'    => fn(array $options) => $app->comments()->recentCommentsBlock($options),
            'template'    => 'pubvana/comments/public/blocks/recent-comments',
            'priority'    => 10,
            'options'     => [
                'title' => ['type' => 'input', 'label' => 'Title', 'default' => 'Recent Comments'],
                'count' => ['type' => 'input', 'label' => 'Number of comments', 'default' => '5'],
            ],
        ]);
    }
}
