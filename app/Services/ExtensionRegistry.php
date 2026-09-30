<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use flight\Engine;

/**
 * ExtensionRegistry - Central registry for all plugin extensions.
 *
 * Originally called "AdminExtend" in flight-school (hence the $app->adext()
 * accessor), this handles everything: UI metadata (menu items, nav items,
 * dashboard cards, CSS, JS), route definitions, and block registration.
 *
 * One registration, two consumers: templates read from adext, Flight's
 * router reads routes from adext. This is the one-stop-shop.
 *
 * Types use dot notation for scope clarity:
 *   - admin.menu, admin.dashboard, admin.css, admin.js  (admin area)
 *   - public.nav, public.css, public.js                 (public site)
 *   - block                                             (region system)
 *
 * This class is schema-validated. Every type, slot, and required key is
 * defined in TYPES. If a plugin registers something that doesn't match
 * the schema, the registration is refused with a clear error message,
 * register() returns false, and the reason is kept in errors(). No silent
 * failures.
 *
 * Callables: a contribution may carry a 'callable'. The registry invokes it
 * only for slots read with a non-empty $context, which today is
 * admin.dashboard and content.edit.panel. Every other type is read with no
 * context, so the consumer service invokes the callable itself.
 *
 * Usage from a plugin:
 *   $app->adext()->register('admin.menu', 'content', 'pubvana.blog', [
 *       'label'    => 'Blog',
 *       'icon'     => 'ti-pencil',
 *       'url'      => '/blog',
 *       'priority' => 20,
 *       'route'    => ['GET', '/blog', [BlogController::class, 'index']],
 *   ]);
 *
 * Usage from a template:
 *   $menuItems = $app->adext()->get('admin.menu', 'content');
 *   foreach ($menuItems as $item) { ... }
 *
 * @package Pubvana\Services
 */
class ExtensionRegistry
{
    /**
     * Allowed extension types, their valid slots, and required/optional keys.
     *
     * To add a new extension type:
     *   1. Add an entry here with slots, required keys, and optional keys
     *   2. Consume it in a template or controller with $app->adext()->get()
     *
     * @var array<string, array{slots: string[], required: string[], optional: string[]}>
     */
    public const TYPES = [
        /*
        |------------------------------------------------------------------
        | Admin Types
        |------------------------------------------------------------------
        | These are for the admin area. URLs and route paths auto-get /admin prefix.
        */
        'admin.menu' => [
            // A slot is either a top-level Nav Item in $topNav
            // (core-admin.php, e.g. 'tools' for standalone items) or a
            // dotted 'parent.label' slot registering a Link under a Label
            // declared in $topNav (e.g. 'tools.links'). Labels cannot nest.
            'slots'    => ['content', 'appearance', 'tools', 'settings', 'plugins'],
            'required' => ['label', 'url'],
            'optional' => ['icon', 'priority', 'submenu', 'route', 'middleware', 'core'],
        ],
        'admin.dashboard' => [
            // A card (slot 'cards') carries a 'value' plus optional icon,
            // tone and href. A section (slot 'sections') carries a 'title'
            // and an 'items' list, each item optionally carrying its own
            // 'href'. Both may supply a 'callable' returning the array
            // instead of inlining it.
            //
            // Every 'href' (card, section, and item) is a site-root-relative
            // path such as '/blog' or '/blog/12/edit'. The dashboard prepends
            // '/admin' before rendering (AdminController::normalizeDashboardUrls()),
            // so contributors must NOT include '/admin' themselves. A path
            // that already starts with '/admin' is left untouched, and
            // absolute http(s) URLs are never rewritten.
            'slots'    => ['cards', 'sections'],
            'required' => ['label'],
            'optional' => ['callable', 'output', 'value', 'icon', 'color', 'priority', 'href', 'items'],
        ],
        'admin.settings' => [
            // Settings pages. Each contribution becomes a tab on the
            // settings page (slot 'general', the tabbed page at
            // /admin/settings) or a standalone page owned by its
            // controller (slot 'email', the Settings > Email page). Its
            // fields[] entries declare the settings it owns (key must be
            // namespaced dot notation). Declared keys are the ONLY keys
            // savable through the settings UI - undeclared keys
            // (deployment values) can never enter the settings store.
            // Valid field types: text, email, number, textarea, select,
            // checkbox, password (SettingsService::FIELD_TYPES). Optional
            // per-field keys: default, options (select), fallback
            // (app-store key), autoload (bool, default true),
            // description.
            'slots'    => ['general', 'email', 'login_sec', 'captcha'],
            'required' => ['label', 'fields'],
            'optional' => ['description', 'priority'],
        ],
        'admin.css' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],
        'admin.js' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Public Types
        |------------------------------------------------------------------
        | These are for the public site. No URL prefix applied.
        */
        'public.nav' => [
            'slots'    => ['main', 'footer', 'sidebar'],
            'required' => ['label', 'url'],
            'optional' => ['icon', 'priority', 'submenu', 'route', 'middleware'],
        ],
        'public.css' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],
        'public.js' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],
        'public.head' => [
            'slots'    => ['other'],
            'required' => ['output'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Navigation Linkable Type
        |------------------------------------------------------------------
        | Plugins register items for the Quick Add dropdown in the
        | navigation admin. The callable returns an array of
        | ['label' => '...', 'url' => '...'] entries.
        */
        'nav.linkable' => [
            'slots'    => ['default'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Block Type (Region System)
        |------------------------------------------------------------------
        | Plugins register blocks that can be placed in theme regions.
        */
        'block' => [
            'slots'    => ['available'],
            'required' => ['label', 'template'],
            'optional' => ['description', 'provider', 'priority', 'options'],
        ],

        /*
        |------------------------------------------------------------------
        | Search Type
        |------------------------------------------------------------------
        | Plugins register their content as searchable. The Search plugin
        | lists registered sources in the admin (to enable/disable them)
        | and invokes each provider's callable with a raw query string.
        | The provider returns normalized content matches; SearchService owns
        | all ranking/scoring.
        */
        'search' => [
            'slots'    => ['provider'],
            'required' => ['label', 'callable'],
            'optional' => ['description', 'priority', 'content_type'],
        ],

        /*
        |------------------------------------------------------------------
        | Comments Host Type
        |------------------------------------------------------------------
        | Content plugins register themselves as comment hosts so the Comments
        | plugin can associate stored comments with their content and enrich
        | displays (recent-comments block). The callable enumerates the host's
        | commentable content items.
        |
        | Each enumerated item:
        |   type            - the commentable_type token that matches comments
        |                     stored against this plugin (e.g. 'blog')
        |   id              - the content record id
        |   title/label     - human-readable title
        |   url             - public URL to the item
        |   allow_comments  - (bool) whether comments are open on this item
        |
        | The Comments service reads these hosts to resolve a comment's target
        | content. Rendering a comment thread for a specific item is done by the
        | host's own controller via CommentService::render() / dataFor().
        */
        'comments.host' => [
            'slots'    => ['content'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Homepage Provider Type
        |------------------------------------------------------------------
        | Content plugins register themselves as candidates for the site
        | front page. The admin picks one in Settings > Site (the Homepage
        | select) and its token is what lands in CMS.homepageType. Core knows
        | nothing about which plugins can serve "/".
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('homepage', 'provider', 'pubvana.blog', [
        |       'label'    => 'Blog Feed',
        |       'token'    => 'blog',
        |       'callable' => fn(): bool => $this->serve(),
        |   ]);
        |
        | The callable takes no arguments and returns true when it rendered
        | the front page, or false to decline so the next provider in
        | priority order gets its turn. PluginLoader::dispatchHomepage()
        | walks them in that order and renders the themed 404 only when every
        | one declines.
        |
        | token defaults to the contributor key. It is the value stored in
        | CMS.homepageType, so keep it URL-safe and in step with the plugin's
        | routePrepend ('blog' for Blog, 'page' for Pages). Deriving it from
        | PluginLoader::routePrefix() is the convention.
        |
        | A provider may declare 'fields' for the settings it owns, using the
        | same shape as admin.settings fields. SettingsController renders them
        | directly under the Homepage select, so a provider can keep its own
        | selector (Pages keeps the page picker) without core declaring it.
        */
        'homepage' => [
            'slots'    => ['provider'],
            'required' => ['label', 'callable'],
            'optional' => ['token', 'description', 'fields', 'priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Content Render Type
        |------------------------------------------------------------------
        | Plugins register content-transform callables that run on rich
        | text bodies (pages, posts) before they reach the template. Each
        | callable receives ['content' => string] and returns the modified
        | string. ContentService::render() chains them in priority order.
        */
        'content.render' => [
            'slots'    => ['default'],
            'required' => ['callable'],
            'optional' => ['label', 'priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Content Edit Panel Type
        |------------------------------------------------------------------
        | Plugins contribute panels rendered inside other plugins' content
        | edit forms (e.g. the SEO panel in the post/page editor). The
        | callable receives ['content_type' => ..., 'content_id' => ...]
        | as context and returns an HTML string to embed.
        */
        'content.edit.panel' => [
            'slots'    => ['default'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Health Check Type
        |------------------------------------------------------------------
        | Site Health extension point. Plugins register additional health
        | checks here. Each contribution's callable is invoked with no
        | arguments and must return a CheckResult (or an array with an 'id').
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('health', 'checks', 'vendor.plugin-name', [
        |       'priority' => 50,
        |       'callable' => fn() => (new YourCustomCheck())->run(),
        |   ]);
        |
        | The HealthService reads them via $app->adext()->get('health', 'checks')
        | and merges them into the check results.
        */
        'health' => [
            'slots'    => ['checks'],
            'required' => ['callable'],
            'optional' => ['label', 'priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Broken Links Source Type
        |------------------------------------------------------------------
        | Content plugins register their content as scan sources for the
        | Broken Links plugin. Each contribution's callable returns an
        | array of content items to scan:
        |   type      - the source type token (e.g. 'post', 'page')
        |   id        - the content record id
        |   title     - human-readable title
        |   content   - the content body to scan for outbound links
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('brokenlinks', 'source', 'pubvana.myplugin', [
        |       'label'    => 'My Plugin Items',
        |       'callable' => fn() => [[
        |           'type'    => 'item',
        |           'id'      => 1,
        |           'title'   => 'Item title',
        |           'content' => '<a href="https://example.com">Example</a>',
        |       ]],
        |   ]);
        |
        | The BrokenLinksService reads them via $app->adext()->get('brokenlinks', 'source').
        */
        'brokenlinks' => [
            'slots'    => ['source'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Cron Task Type
        |------------------------------------------------------------------
        | Plugins register callables that run on a fixed interval. There are
        | three slots, each driven by a system crontab line calling the root
        | `cron` script: 1m (every minute), 4h, and 24h. No web routes are
        | involved; the script is CLI-only and not web-exposed.
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $app->adext()->register('cron', '1m', 'pubvana.blog', [
        |       'label'    => 'Ping feeds',
        |       'callable' => fn() => $this->pingFeeds(),
        |   ]);
        |
        | CronService reads these via $app->adext()->get('cron', $interval)
        | and runs each callable in priority order. One failing task never
        | blocks the rest.
        */
        'cron' => [
            'slots'    => ['1m', '4h', '24h'],
            'required' => ['callable'],
            'optional' => ['label', 'priority', 'run_result'],
        ],

        /*
        |------------------------------------------------------------------
        | Captcha Area Type
        |------------------------------------------------------------------
        | Plugins (and core) register the areas of the site that can be
        | protected by captcha. Each area can be switched on or off in
        | Settings > Captcha; the CaptchaService reads these registrations
        | to list the switches and to validate the stored list.
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('captcha.area', 'default', 'myform', [
        |       'label'       => 'My form',
        |       'description' => 'Requires a completed captcha before submission.',
        |   ]);
        |
        | The area key is a short unique token owned by the registering
        | plugin (e.g. 'comments', 'forms'); core registers 'login' for
        | the sign-in form. Consumers call CaptchaService::enforcedFor()
        | with that same token before trusting a submission, and public
        | templates print the widget with {% captcha 'token' %}.
        */
        'captcha.area' => [
            'slots'    => ['default'],
            'required' => ['label'],
            'optional' => ['description', 'priority'],
        ],

        /*
        |------------------------------------------------------------------
        | CSRF Exempt Type
        |------------------------------------------------------------------
        | Plugins register URL path prefixes whose state-changing requests
        | skip CSRF validation, e.g. sessionless webhook and API endpoints
        | that authenticate with per-request gateway signatures or bearer
        | keys and cannot present a session token. The core CSRF gate in
        | app/config/services.php reads these prefixes from the registry
        | instead of a hardcoded path list.
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('csrf.exempt', 'default', 'pubvana.mystore', [
        |       'prefix' => '/store/webhook/',
        |       'label'  => 'Digital Store webhooks',
        |   ]);
        |
        | The prefix must be a path prefix ending in '/' unless the
        | exemption targets one exact path. Admin child pages must not be
        | registered here; they start with /admin and stay protected.
        |
        | A prefix cannot isolate a route whose variable sits mid-path, e.g.
        | /profile/{id}/avatar. Those register a 'pattern' instead: a PCRE
        | the gate matches against the request path. When both keys are
        | given, pattern wins. Anchor the pattern yourself.
        |
        |   $adext->register('csrf.exempt', 'default', 'pubvana.profiles.avatar', [
        |       'pattern' => '#^/profile/\d+/avatar$#',
        |       'label'   => 'Profile avatar uploads',
        |   ]);
        */
        'csrf.exempt' => [
            'slots'    => ['default'],
            'required' => [],
            'optional' => ['prefix', 'pattern', 'label', 'description', 'priority'],
        ],
    ];

    /**
     * All registered extensions, keyed by type, then slot, then contributor key.
     *
     * @var array<string, array<string, array<string, array<string, mixed>>>>
     */
    protected array $extensions = [];
    /**
     * Registrations refused since boot, in the order they were refused.
     *
     * register() reports every refusal through error_log() and returns false.
     * The same refusal is recorded here so a test can assert on it, and a
     * consumer can surface it in the UI, without parsing the error log.
     *
     * @var list<array{type: string, slot: string, key: string, reason: string}>
     */
    protected array $errors = [];
    /**
     * Route definitions collected from registrations.
     *
     * Each entry: [method, path, handler, middleware, scope, source, isCore]
     * scope is 'admin' or 'public' - determines /admin prefix.
     * source is the registering key (e.g. 'pubvana.users', 'pubvana.blog').
     * isCore marks routes that cannot be overridden by plugins.
     *
     * @var array<int, array{method: string, path: string, handler: callable|array{0: class-string, 1: string}, middleware: array<int, mixed>, scope: string, source: string, isCore: bool}>
     */
    protected array $routes = [];

    /**
     * Set of route signatures that are core (cannot be overridden).
     *
     * Format: 'METHOD /path' (after /admin prefix applied)
     *
     * @var array<string, string> Signature => source key
     */
    protected array $coreRouteSignatures = [];

    /**
     * Register one or more contributions to a typed extension point.
     *
     * Two calling conventions:
     *
     *   // Single item (original)
     *   $adext->register('admin.menu', 'settings', 'pubvana.users', ['label' => 'Users', ...]);
     *
     *   // Batch: $key is an array of key => config pairs, $config omitted
     *   $adext->register('admin.menu', 'settings', [
     *       'pubvana.users'        => ['label' => 'Users', ...],
     *       'pubvana.groups'       => ['label' => 'Groups', ...],
     *   ]);
     *
     * Validates against the TYPES schema. Refuses unknown types, unknown slots,
     * missing required keys, unknown keys, duplicate contributor keys, and
     * values of a type register() itself cannot use. Every refusal is logged
     * through error_log() and recorded in errors(), so a caller that cares can
     * see the problem without reading the error log.
     *
     * Nothing is stored until the config passes, so a refused registration
     * never leaves a half-applied entry or a route behind.
     *
     * If a 'route' key is provided (for menu/nav types), the route is
     * automatically stored for later registration with Flight's router.
     *
     * URL auto-prefixing:
     *   - admin.* types: auto-prefixes /admin
     *   - public.* types: no prefix
     *
     * In batch mode the return value is true only when every entry in the
     * batch was stored.
     *
     * @param string               $type   The extension type (must exist in TYPES)
     * @param string               $slot   The slot name (must exist in TYPES[$type]['slots'])
     * @param string|array<string, mixed> $key    Single key string, or array of key => config pairs
     * @param array<string, mixed> $config Data being registered (single mode only)
     *
     * @return bool True when the contribution was stored
     */
    public function register(string $type, string $slot, string|array $key, array $config = []): bool
    {
        if (!isset(self::TYPES[$type])) {
            $allowed = implode(', ', array_keys(self::TYPES));
            return $this->reject($type, $slot, $key, "unknown type '{$type}'. Allowed: {$allowed}");
        }

        $schema = self::TYPES[$type];

        $slotOk = in_array($slot, $schema['slots'], true);

        // admin.menu also accepts dotted 'parent.label' slots; the parent
        // must be a known Nav Item and the label must be non-empty. Labels
        // themselves are validated against $topNav when the menu is built.
        if (!$slotOk && $type === 'admin.menu' && str_contains($slot, '.')) {
            $parts = explode('.', $slot, 2);
            if (in_array($parts[0], $schema['slots'], true) && $parts[1] !== '') {
                $slotOk = true;
            }
        }

        if (!$slotOk) {
            $allowed = implode(', ', $schema['slots']);
            return $this->reject($type, $slot, $key, "unknown slot '{$slot}' for type '{$type}'. Allowed: {$allowed}");
        }

        // Batch mode: $key is [contributorKey => config, ...]
        if (is_array($key)) {
            $stored = true;
            foreach ($key as $batchKey => $batchConfig) {
                // A non-array entry is cast rather than thrown at, so one bad
                // entry is refused by the recursive call instead of aborting
                // the whole batch with a TypeError.
                $stored = $this->register($type, $slot, (string) $batchKey, (array) $batchConfig) && $stored;
            }
            return $stored;
        }

        // Single mode
        $missing = array_diff($schema['required'], array_keys($config));
        if (!empty($missing)) {
            $keys = implode(', ', $missing);
            return $this->reject($type, $slot, $key, "missing required keys [{$keys}] for '{$type}.{$slot}' (key: '{$key}')");
        }

        $allowedKeys = array_merge($schema['required'], $schema['optional']);
        $unknown = array_diff(array_keys($config), $allowedKeys);
        if (!empty($unknown)) {
            $keys = implode(', ', $unknown);
            $allowed = implode(', ', $allowedKeys);
            return $this->reject($type, $slot, $key, "unknown keys [{$keys}] for '{$type}.{$slot}' (key: '{$key}'). Allowed: {$allowed}");
        }

        if (isset($this->extensions[$type][$slot][$key])) {
            return $this->reject($type, $slot, $key, "duplicate key '{$key}' in [{$type}][{$slot}] - rejected.");
        }

        // register() reads these values itself (URL prefixing, the asset-URL
        // rewrite, and the path/handler/middleware it hands to addRoute()), so
        // a wrong type reaches addRoute() and throws a TypeError mid-boot.
        // 'callable' is deliberately not checked: handling a non-callable is
        // the consumer's job (CronService counts it as a failed task,
        // PluginLoader::dispatchHomepage skips it).
        $problems = $this->valueErrors($config);
        if ($problems !== []) {
            $list = implode('; ', $problems);
            return $this->reject($type, $slot, $key, "invalid value for '{$type}.{$slot}' (key: '{$key}'): {$list}");
        }

        // Auto-prefix /admin URLs for admin types
        if (str_starts_with($type, 'admin.')) {
            if (isset($config['url'])) {
                $config['url'] = '/admin/' . ltrim($config['url'], '/');
            }
            if (!empty($config['submenu'])) {
                foreach ($config['submenu'] as $subKey => $sub) {
                    if (isset($sub['url'])) {
                        $config['submenu'][$subKey]['url'] = '/admin/' . ltrim($sub['url'], '/');
                    }
                }
            }
        }

        // Transform plugin asset URLs for CSS/JS types
        // /plugins/{Plugin}/assets/{path} → /assets/plugin/{Plugin}/{path}
        if (in_array($type, ['admin.css', 'admin.js', 'public.css', 'public.js'], true)) {
            if (isset($config['url']) && preg_match('#^/plugins/([^/]+)/assets/(.+)$#', $config['url'], $matches)) {
                $pluginName = $matches[1];
                $assetPath = $matches[2];
                $config['url'] = '/assets/plugin/' . $pluginName . '/' . $assetPath;
            }
        }

        // Store before collecting the route so a registration is never
        // half-applied: the entry is present with its route, or not at all.
        $this->extensions[$type][$slot][$key] = $config;

        // Collect route if provided
        if (isset($config['route']) && is_array($config['route'])) {
            $scope = str_starts_with($type, 'admin.') ? 'admin' : 'public';
            $this->addRoute(
                $config['route'][0] ?? 'GET',
                $config['route'][1] ?? $config['url'],
                $config['route'][2] ?? null,
                $config['middleware'] ?? [],
                $scope,
                $key
            );
        }

        return true;
    }

    /**
     * Refuse a registration: log it, record it, and report failure.
     *
     * The log line keeps the 'ExtensionRegistry: ' prefix it has always
     * carried; the recorded reason is the message without that prefix.
     *
     * @param string               $type   Extension type the registration targeted
     * @param string               $slot   Slot the registration targeted
     * @param string|array<string, mixed> $key Contributor key, or the whole batch map
     * @param string               $reason Why it was refused
     *
     * @return bool Always false, so callers can `return $this->reject(...)`.
     */
    private function reject(string $type, string $slot, string|array $key, string $reason): bool
    {
        $label = is_array($key) ? implode(', ', array_map('strval', array_keys($key))) : $key;

        error_log("ExtensionRegistry: {$reason}");
        $this->errors[] = [
            'type'   => $type,
            'slot'   => $slot,
            'key'    => $label,
            'reason' => $reason,
        ];

        return false;
    }

    /**
     * Type-check the values register() reads for itself.
     *
     * URL prefixing, the asset-URL rewrite, and the path/handler/middleware
     * list handed to addRoute() all read $config without a guard, so a wrong
     * type there is a TypeError out of addRoute() mid-boot. Keys checked here
     * are exactly the ones register() consumes; the rest are stored as given
     * for the consuming service to interpret.
     *
     * @param array<string, mixed> $config Registration being validated
     *
     * @return list<string> Problems found; empty when the config is usable
     */
    private function valueErrors(array $config): array
    {
        $problems = [];

        foreach (['url', 'prefix', 'pattern'] as $key) {
            if (isset($config[$key]) && !is_string($config[$key])) {
                $problems[] = "'{$key}' must be a string";
            }
        }

        foreach (['middleware', 'submenu', 'fields'] as $key) {
            if (isset($config[$key]) && !is_array($config[$key])) {
                $problems[] = "'{$key}' must be an array";
            }
        }

        if (isset($config['priority']) && !is_int($config['priority'])) {
            $problems[] = "'priority' must be an integer";
        }

        if (isset($config['route'])) {
            if (!is_array($config['route'])) {
                $problems[] = "'route' must be an array";

                return $problems;
            }

            $route = array_values($config['route']);
            $path = $route[1] ?? $config['url'] ?? null;
            $handler = $route[2] ?? null;

            if (!is_string($path)) {
                $problems[] = "'route' needs a path in element 1, or a 'url' key";
            }

            if (!is_callable($handler) && !is_array($handler)) {
                $problems[] = "'route' element 2 must be a callable or a [class, method] array";
            }

            if (isset($route[0]) && !is_string($route[0])) {
                $problems[] = "'route' element 0 must be an HTTP method string";
            }
        }

        return $problems;
    }

    /**
     * Get all contributions for a typed extension slot, sorted by priority.
     *
     * When context is provided and a contribution has a 'callable' key,
     * the callable is invoked with the context and its return value is
     * merged into the contribution.
     *
     * An empty $context means callables are NOT invoked: the raw
     * contributions come back and the consumer calls them itself. The check
     * is on the context, not on the type, so a slot read without a context
     * never triggers a callable. Only admin.dashboard and content.edit.panel
     * are read with a context today.
     *
     * @param string                                 $type    The extension type (e.g. 'admin.menu', 'public.nav')
     * @param string                                 $slot    The slot name (e.g. 'content', 'main')
     * @param array<string, mixed>                   $context Optional context passed to callable contributors
     *
     * @return array<string, array<string, mixed>> Contributions sorted by priority
     */
    public function get(string $type, string $slot, array $context = []): array
    {
        $items = $this->extensions[$type][$slot] ?? [];

        if (!empty($context)) {
            foreach ($items as $key => &$item) {
                if (isset($item['callable']) && is_callable($item['callable'])) {
                    $result = call_user_func($item['callable'], $context);
                    if (is_array($result)) {
                        $item = array_merge($item, $result);
                    } elseif (is_string($result)) {
                        $item['output'] = $result;
                    }
                }
            }
            unset($item);
        }

        uasort($items, fn($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));

        foreach ($items as &$item) {
            if (!empty($item['submenu']) && is_array($item['submenu'])) {
                uasort($item['submenu'], fn($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Whether a request path is exempt from the core CSRF gate.
     *
     * A registration carries either a 'pattern' (PCRE, for routes whose
     * variable sits mid-path) or a 'prefix' (matched with str_starts_with).
     * Pattern wins when both are present. A registration with neither is
     * ignored rather than treated as a match-everything.
     *
     * @param string $requestPath Path portion of the request URL
     *
     * @return bool True when the path is exempt
     */
    public function isCsrfExempt(string $requestPath): bool
    {
        foreach ($this->get('csrf.exempt', 'default') as $exempt) {
            $pattern = $exempt['pattern'] ?? '';
            if (is_string($pattern) && $pattern !== '') {
                if (preg_match($pattern, $requestPath) === 1) {
                    return true;
                }
                continue;
            }

            $prefix = $exempt['prefix'] ?? '';
            if (is_string($prefix) && $prefix !== '' && str_starts_with($requestPath, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if any contributions exist for a typed extension slot.
     *
     * @param string $type The extension type
     * @param string $slot The slot name
     *
     * @return bool
     */
    public function has(string $type, string $slot): bool
    {
        return !empty($this->extensions[$type][$slot]);
    }

    /**
     * Registrations refused since boot.
     *
     * @return list<array{type: string, slot: string, key: string, reason: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Store a single route definition for later registration.
     *
     * @param string         $method     HTTP method (GET, POST, etc.)
     * @param string                    $path       URL path (e.g. '/blog', '/users/@id/edit')
     * @param callable|array{0: class-string, 1: string} $handler    Route handler (closure, [Controller::class, 'method'], etc.)
     * @param array<int, mixed>         $middleware  Middleware instances to apply
     * @param string                    $scope      'admin' or 'public' - determines /admin prefix
     * @param string                    $source     Registering key (e.g. 'pubvana.users', 'pubvana.blog')
     * @param bool                      $isCore     Whether this is a core route (cannot be overridden)
     *
     * @return int Number of routes successfully added (0 or 1)
     */
    public function addRoute(string $method, string $path, callable|array $handler, array $middleware = [], string $scope = 'public', string $source = 'unknown', bool $isCore = false): int
    {
        return $this->addRoutes($scope, [[$method, $path, $handler, $middleware]], $source, $isCore);
    }

    /**
     * Store multiple route definitions for later registration.
     *
     * Processes all routes in a single call, applying the same source
     * and isCore flags to all routes in the batch.
     *
     * Each route in $routes is an array: [method, path, handler, middleware?]
     * middleware is optional and defaults to [].
     *
     * Admin-scope routes are gated automatically on the admin.access
     * permission (see registerRoutes). Use the middleware slot only for
     * extra per-route gates such as PermissionMiddleware.
     *
     * Conflict resolution:
     *   - Core routes (isCore=true) always win over plugin routes
     *   - First registered route wins on conflict
     *   - Rejected routes are logged with the conflicting source
     *
     * @param string               $scope   'admin' or 'public' - determines /admin prefix
     * @param array<int, array<int|string, mixed>> $routes  Array of route definitions: [method, path, handler, middleware?]
     * @param string               $source  Registering key (e.g. 'pubvana.core', 'pubvana.blog')
     * @param bool                 $isCore  Whether these are core routes (cannot be overridden)
     *
     * @return int Number of routes successfully added
     */
    public function addRoutes(string $scope, array $routes, string $source = 'unknown', bool $isCore = false): int
    {
        $added = 0;

        foreach ($routes as $route) {
            $method = strtoupper($route[0]);
            $path = $route[1];
            $handler = $route[2];
            $middleware = $route[3] ?? [];

            // Build the signature with /admin prefix applied
            $fullPath = $scope === 'admin'
                ? '/admin/' . ltrim($path, '/')
                : $path;
            $signature = $method . ' ' . $fullPath;

            // Check for conflict with core routes
            if (isset($this->coreRouteSignatures[$signature])) {
                error_log("ExtensionRegistry: route conflict - '{$source}' rejected '{$signature}' (core route from '{$this->coreRouteSignatures[$signature]}' cannot be overridden)");
                continue;
            }

            // Check for conflict with existing routes
            $conflict = false;
            foreach ($this->routes as $existing) {
                $existingSig = strtoupper($existing['method']) . ' ' . (
                    $existing['scope'] === 'admin'
                        ? '/admin/' . ltrim($existing['path'], '/')
                        : $existing['path']
                );

                if ($existingSig === $signature) {
                    if ($existing['isCore']) {
                        error_log("ExtensionRegistry: route conflict - '{$source}' rejected '{$signature}' (core route from '{$existing['source']}' cannot be overridden)");
                    } else {
                        error_log("ExtensionRegistry: route conflict - '{$source}' rejected '{$signature}' (first registered by '{$existing['source']}' wins)");
                    }
                    $conflict = true;
                    break;
                }
            }

            if ($conflict) {
                continue;
            }

            // No conflict - add the route
            $this->routes[] = [
                'method'     => $method,
                'path'       => $path,
                'handler'    => $handler,
                'middleware'  => $middleware,
                'scope'      => $scope,
                'source'     => $source,
                'isCore'     => $isCore,
            ];

            // Track core route signatures
            if ($isCore) {
                $this->coreRouteSignatures[$signature] = $source;
            }

            $added++;
        }

        return $added;
    }

    /**
     * Register all collected routes with Flight's router.
     *
     * Call this after all plugins have loaded. For admin routes, the /admin
     * prefix is automatically prepended to the path.
     *
     * @param Engine<object> $app The FlightPHP app instance
     *
     * @return void
     */
    public function registerRoutes(Engine $app): void
    {
        $router = $app->router();

        foreach ($this->routes as $route) {
            $path = $route['scope'] === 'admin'
                ? '/admin/' . ltrim($route['path'], '/')
                : $route['path'];

            $fullPath = $route['method'] . ' ' . $path;
            $flightRoute = $router->map($fullPath, $route['handler']);

            // Admin-scope routes are gated on the admin.access permission
            // automatically, so plugin authors don't have to pass an auth
            // middleware. A route's own middleware runs after the gate.
            // Non-object entries (null placeholders, class strings) are skipped
            // on purpose; only middleware instances are applied.
            $routeMiddleware = array_values(array_filter(
                $route['middleware'],
                static fn (mixed $mw): bool => is_object($mw)
            ));

            if ($route['scope'] === 'admin') {
                array_unshift($routeMiddleware, new PermissionMiddleware($app, 'admin.access'));
            }

            foreach ($routeMiddleware as $mw) {
                $flightRoute->addMiddleware($mw);
            }
        }
    }

    /**
     * Get all stored routes.
     *
     * @return array<int, array{method: string, path: string, handler: callable|array{0: class-string, 1: string}, middleware: array<int, mixed>, scope: string, source: string, isCore: bool}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
