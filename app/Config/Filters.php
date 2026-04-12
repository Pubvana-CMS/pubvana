<?php

namespace Config;

use App\Filters\AdminFilter;
use App\Filters\MaintenanceFilter;
use App\Filters\RedirectFilter;
use App\Filters\TotpFilter;
use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\Shield\Filters\SessionAuth;

class Filters extends BaseFilters
{
    /**
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'session'       => SessionAuth::class,
        'admin_auth'    => AdminFilter::class,
        'maintenance'   => MaintenanceFilter::class,
        'totp'          => TotpFilter::class,
        'redirects'     => RedirectFilter::class,
    ];

    /**
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps',
            'pagecache',
            'redirects',
        ],
        'after' => [
            'pagecache',
            'performance',
            'toolbar',
        ],
    ];

    /**
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            'csrf'        => ['except' => ['api/*', 'admin/*']],
            'maintenance' => ['except' => ['admin*', 'login', 'logout', 'register']],
        ],
        'after'  => [],
    ];

    /**
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        // Apply honeypot to public POST routes (comment submission and contact form).
        // Patterns cover both non-prefixed (blog/slug, contact) and locale-prefixed
        // (en/blog/slug, fr/contact, etc.) variants produced by the locale route group.
        'honeypot' => [
            'before' => ['blog/*', 'contact', 'actions/contact', '*/blog/*', '*/contact', '*/actions/contact'],
            'after'  => ['blog/*', 'contact', 'actions/contact', '*/blog/*', '*/contact', '*/actions/contact'],
        ],
    ];
}
