<?php

declare(strict_types=1);

namespace Pubvana\Plugins\ActivityLog\Services;

use Pubvana\Plugins\ActivityLog\Models\ActivityLog;
use flight\Engine;
use flight\net\Route;

/**
 * ActivityLogService - Core service for the Activity Log plugin.
 *
 * Provides explicit logging API and auto-tracking from admin routes.
 *
 * @package Pubvana\Plugins\ActivityLog\Services
 */
class ActivityLogService
{
    private \PDO $pdo;
    /** @var array<string, mixed> */
    private array $config;
    /** @var \flight\Engine<object>|null */
    private $app = null;

    /**
     * @param \PDO $pdo
     * @param array<string, mixed> $config
     */
    public function __construct(\PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /**
     * Set the app instance (for accessing auth, request, etc.)
     * @param \flight\Engine<object> $app
     */
    public function setApp($app): void
    {
        $this->app = $app;
    }

    /**
     * Explicit log entry.
     *
     * @param array<string, mixed> $data
     *   - action: string (required) - create, update, delete, publish, settings_change, etc.
     *   - entity_type: string (required) - blog_post, page, redirect, user, setting, form, etc.
     *   - entity_id: int|null
     *   - entity_name: string (required) - human-readable name
     *   - details: array|null - additional context, will be JSON-encoded
     *   - user_id: int|null - defaults to current authenticated user
     *   - user_name: string|null - defaults to current authenticated user name
     *   - ip: string|null - defaults to current request IP
     *   - user_agent: string|null - defaults to current request user agent
     */
    public function log(array $data): void
    {
        if ($this->app === null) {
            return;
        }

        try {
            $user = $this->app->auth()->user();
            $request = $this->app->request();

            // Auth can be absent (cron, CLI, an unauthenticated action). Only
            // read the properties when a user object came back, so a null user
            // writes a system row instead of raising a warning on null.
            $currentUserId = $user !== null && isset($user->id) ? (int) $user->id : null;
            $currentUserName = $user !== null && isset($user->username) ? (string) $user->username : 'system';

            $log = new ActivityLog($this->pdo);
            $log->user_id = $data['user_id'] ?? $currentUserId;
            $log->user_name = $data['user_name'] ?? $currentUserName;
            $log->action = $data['action'] ?? 'unknown';
            $log->entity_type = $data['entity_type'] ?? 'unknown';
            $log->entity_id = $data['entity_id'] ?? null;
            $log->entity_name = $data['entity_name'] ?? '';
            $encoded = isset($data['details']) ? json_encode($data['details']) : null;
            $log->details = $encoded === false ? null : $encoded;
            $log->ip = $data['ip'] ?? $this->getClientIp($request);
            $log->user_agent = $data['user_agent'] ?? $this->getUserAgent($request);
            $log->created_at = date('Y-m-d H:i:s');
            $log->save();
        } catch (\Throwable $e) {
            error_log('ActivityLog: failed to write log entry: ' . $e->getMessage());
        }
    }

    /**
     * Auto-extract log data from an admin route.
     */
    public function logFromRoute(Route $route): void
    {
        if ($this->app === null) {
            return;
        }

        // Check if tracking is enabled
        if (($this->config['track_admin_actions'] ?? true) === false) {
            return;
        }

        // Skip non-mutating methods. Route has no $method property (it is
        // $methods, an array), so read the actual request method.
        $method = $this->app->request()->method;
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return;
        }

        // Skip non-admin routes
        $pattern = $route->pattern ?? '';
        if (!str_starts_with($pattern, '/admin/')) {
            return;
        }

        // Skip certain routes (login, logout, asset endpoints, etc.)
        if ($this->shouldSkipRoute($pattern)) {
            return;
        }

        // Infer action and entity from the route path
        $inferred = $this->inferFromRoute($pattern);
        if ($inferred === null) {
            return;
        }

        $params = $route->params;

        $this->log([
            'action'      => $inferred['action'],
            'entity_type' => $inferred['entity_type'],
            'entity_id'   => $this->extractEntityId($params),
            'entity_name' => $this->extractEntityName($params, $inferred['entity_type']),
            'details'     => ['route' => $pattern, 'method' => $method, 'params' => $params],
        ]);
    }

    /**
     * List log entries with filtering and pagination.
     *
     * @param array<string, mixed> $filters
     * @return array<int, ActivityLog>
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $model = new ActivityLog($this->pdo);
        return $model->filtered($filters, $page, $perPage);
    }

    /**
     * Count filtered log entries.
     *
     * @param array<string, mixed> $filters
     */
    public function count(array $filters = []): int
    {
        $model = new ActivityLog($this->pdo);
        return $model->countFiltered($filters);
    }

    /**
     * Get count of entries in the last 24 hours (for dashboard card).
     */
    public function countRecent24h(): int
    {
        $model = new ActivityLog($this->pdo);
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        return $model->countSince($since);
    }

    /**
     * Get distinct actions for filter dropdown.
     *
     * @return string[]
     */
    public function getActions(): array
    {
        $model = new ActivityLog($this->pdo);
        return $model->distinctActions();
    }

    /**
     * Get distinct entity types for filter dropdown.
     *
     * @return string[]
     */
    public function getEntityTypes(): array
    {
        $model = new ActivityLog($this->pdo);
        return $model->distinctEntityTypes();
    }

    /**
     * Get distinct users for filter dropdown.
     *
     * @return array<int, array{user_id: int, user_name: string}>
     */
    public function getUsers(): array
    {
        $model = new ActivityLog($this->pdo);
        return $model->distinctUsers();
    }

    /**
     * Determine if a route should be skipped from auto-tracking.
     */
    private function shouldSkipRoute(string $pattern): bool
    {
        $skipPatterns = [
            '/admin/auth/',        // login/logout
            '/admin/assets/',      // asset serving
            '/admin/api/',         // API endpoints
            '/admin' . rtrim((string) ($this->config['route_prefix'] ?? ''), '/'), // self-reference
        ];

        foreach ($skipPatterns as $skip) {
            if (str_starts_with($pattern, $skip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin route prefixes mapped to the entity type they act on.
     *
     * Longest prefix wins, so a specific sub-resource sits before its
     * parent. Keys are route paths with the /admin prefix stripped.
     *
     * @var array<string, string>
     */
    private const ENTITY_BY_PREFIX = [
        '/themes/regions'        => 'region',
        '/blog/categories'       => 'blog_category',
        '/blog/tags'             => 'blog_tag',
        '/redirects/404-manager' => 'redirect_link',
        '/forms/submissions'     => 'form_submission',
        '/comments/settings'     => 'comment_setting',
        '/ai/manage/keys'        => 'ai_key',
        '/ai/manage'             => 'ai_setting',
        '/ai/fact-checks'        => 'ai_fact_check',
        '/themes'                => 'theme',
        '/blog'                  => 'blog_post',
        '/page'                  => 'page',
        '/media'                 => 'media',
        '/redirects'             => 'redirect',
        '/forms'                 => 'form',
        '/users'                 => 'user',
        '/groups'                => 'group',
        '/permissions'           => 'permission',
        '/settings'              => 'setting',
        '/login-sec'             => 'login_setting',
        '/captcha'               => 'captcha_setting',
        '/email'                 => 'email_setting',
        '/navigation'            => 'navigation_item',
        '/plugins'               => 'plugin',
        '/seo'                   => 'seo',
        '/comments'              => 'comment',
        '/profile'               => 'profile',
        '/backups'               => 'backup',
        '/analytics'             => 'analytics',
        '/social-links'          => 'social_link',
        '/search'                => 'search_setting',
        '/ai'                    => 'ai',
        '/broken-links'          => 'broken_link',
        '/marketplace'           => 'marketplace',
        '/site-health'           => 'site_health',
        '/updates'               => 'update',
    ];

    /**
     * Path segments that map to a canonical action. Any other segment is
     * used as the action verbatim (dashes become underscores), so a new
     * route is tracked without editing this list.
     *
     * @var array<string, string>
     */
    private const ACTION_ALIASES = [
        'store'    => 'create',
        'upload'   => 'create',
        'embed'    => 'create',
        'keys'     => 'create',
        'update'   => 'update',
        'edit'     => 'update',
        'options'  => 'update',
        'values'   => 'update',
        'poster'   => 'update',
        'revert'   => 'update',
        'avatar'   => 'update',
        'grants'   => 'update',
        'terms'    => 'update',
        'delete'   => 'delete',
        'destroy'  => 'delete',
        'save'     => 'settings_change',
        'settings' => 'settings_change',
        'meta'     => 'settings_change',
        'author'   => 'settings_change',
        'tracking' => 'toggle',
    ];

    /**
     * Infer action and entity type from a route pattern.
     *
     * Admin mutations in this app are POST requests with the verb in the
     * path (store, update, delete, toggle, ...), so the action comes from
     * the path, not the HTTP method.
     *
     * @return array{action: string, entity_type: string, entity_id: int|null, entity_name: string}|null
     */
    private function inferFromRoute(string $pattern): ?array
    {
        // Strip the /admin prefix, keep the leading slash (map keys are /-prefixed)
        $path = substr($pattern, strlen('/admin'));

        $prefix = $this->matchEntityPrefix($path);
        if ($prefix === null) {
            return null;
        }

        return [
            'action'      => $this->resolveAction($path, $prefix),
            'entity_type' => self::ENTITY_BY_PREFIX[$prefix],
            'entity_id'   => null,
            'entity_name' => '',
        ];
    }

    /**
     * Return the longest entity prefix that matches the path, or null.
     */
    private function matchEntityPrefix(string $path): ?string
    {
        foreach (array_keys(self::ENTITY_BY_PREFIX) as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }

    /**
     * Pick the action from the literal path segments after the prefix.
     *
     * Scans right to left and skips route parameters (@id), so
     * /users/@id/update resolves to update and /backups/restore/@filename
     * resolves to restore. A known alias wins over the trailing segment, so
     * /media/upload/image resolves to create.
     */
    private function resolveAction(string $path, string $prefix): string
    {
        $remainder = trim(substr($path, strlen($prefix)), '/');

        // When the whole path was the prefix, look at the path itself so a
        // POST to a sub-resource root such as /ai/manage/keys still finds
        // the "keys" segment.
        $segments = array_filter(
            explode('/', $remainder === '' ? $path : $remainder),
            static fn (string $segment): bool => $segment !== ''
        );

        $trailing = null;
        foreach (array_reverse($segments) as $segment) {
            if (str_starts_with($segment, '@')) {
                continue;
            }
            if (isset(self::ACTION_ALIASES[$segment])) {
                return self::ACTION_ALIASES[$segment];
            }
            $trailing ??= $segment;
        }

        // No action segment. A POST to a settings root (for example /seo)
        // is a settings change; a POST to a bare parameter is an update.
        if ($remainder === '') {
            return 'settings_change';
        }

        return $trailing === null ? 'update' : str_replace('-', '_', $trailing);
    }

    /**
     * Extract entity ID from route params.
     *
     * Flight's Route::$params PHPDoc claims int keys, but matchUrl() fills
     * named params under string keys. Accept either so both are provable.
     *
     * @param array<int|string, string|null> $params
     */
    private function extractEntityId(array $params): ?int
    {
        foreach (['id', 'entity_id', 'userId', 'post_id', 'page_id', 'redirect_id', 'form_id', 'user_id', 'group_id', 'permission_id', 'comment_id', 'media_id', 'category_id', 'tag_id', 'submission_id'] as $key) {
            if (isset($params[$key]) && is_numeric($params[$key])) {
                return (int) $params[$key];
            }
        }
        return null;
    }

    /**
     * Extract entity name from route params.
     *
     * @param array<int|string, string|null> $params
     */
    private function extractEntityName(array $params, string $entityType): string
    {
        foreach (['name', 'title', 'slug', 'label', 'source_path', 'filename', 'email', 'username'] as $key) {
            if (isset($params[$key]) && $params[$key] !== '') {
                return $params[$key];
            }
        }
        return $entityType;
    }

    /**
     * Get client IP from request.
     *
     * Only the connection address is used. Proxy headers such as
     * X-Forwarded-For and X-Real-IP are client-controlled, so trusting them
     * would let any visitor forge the IP written to the audit log.
     *
     * @param \flight\net\Request $request The request (unused, kept for the
     *                                     internal call shape)
     */
    private function getClientIp($request): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent from request.
     *
     * @param \flight\net\Request $request
     */
    private function getUserAgent($request): string
    {
        $ua = $request->getHeader('User-Agent');
        return $ua !== '' ? $ua : 'unknown';
    }
}