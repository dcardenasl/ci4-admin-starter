<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Public health endpoint — no auth, no admin filter. Suitable for load
// balancer probes (HTTP 200 if healthy, 503 if degraded). The CSRF global
// filter only enforces on state-changing verbs, so a GET passes through.
$routes->get('/health', '\App\Modules\System\Controllers\HealthController::index', ['as' => 'system.health']);

// Development component showcase
$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    if (ENVIRONMENT === 'development') {
        $routes->get('system/components', '\App\Modules\System\Controllers\ComponentDemoController::index', [
            'as' => 'admin.system.components',
        ]);
    }
});
