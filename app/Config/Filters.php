<?php

declare(strict_types=1);

namespace Config;

use App\Filters\AdminFilter;
use App\Filters\AuthFilter;
use App\Filters\LocaleFilter;
use App\Filters\MaintenanceFilter;
use App\Filters\RateLimitFilter;
use App\Filters\SecurityHeadersFilter;
use App\Filters\SuperAdminFilter;
use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'performance'   => PerformanceMetrics::class,
        'auth'          => AuthFilter::class,
        'admin'         => AdminFilter::class,
        'superadmin'    => SuperAdminFilter::class,
        'permission'    => \App\Filters\PermissionFilter::class,
        'locale'        => LocaleFilter::class,
        'ratelimit'     => RateLimitFilter::class,
        'securityheaders' => SecurityHeadersFilter::class,
        'maintenance'   => MaintenanceFilter::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
        ],
        'after' => [
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            'maintenance', // 503 short-circuit when MAINTENANCE_MODE=true (audit B10.4); /health etc. bypass internally.
            // 'honeypot',
            // Google Identity Services posts the credential directly to this endpoint from
            // Google's origin. CSRF stays enabled everywhere else, and the controller also
            // rejects malformed, expired, wrong-issuer, or wrong-audience ID tokens.
            'csrf' => ['except' => ['login/google']],
            'locale',
            // 'invalidchars',
        ],
        'after' => [
            // 'honeypot',
            'securityheaders', // App-defined: X-Frame-Options, X-CTO, Referrer-Policy, Permissions-Policy, HSTS in prod (audit B5.1).
            'secureheaders',   // CI4 native: emits headers from Config\Security::$secureHeaders if populated.
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        // Apply rate limiting to all authenticated routes.
        // Public auth routes (login, register, password reset) are excluded
        // because they are already protected by CSRF and have no session user.
        'ratelimit' => [
            'before' => [
                'dashboard',
                'profile',
                'profile/*',
                'files',
                'files/*',
                'admin/*',
                'language/*',
            ],
        ],
    ];
}
