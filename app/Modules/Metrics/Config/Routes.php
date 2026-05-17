<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('admin', ['filter' => ['auth', 'permission:metrics.read']], static function (RouteCollection $routes): void {
    $routes->get('metrics', '\App\Modules\Metrics\Controllers\MetricsController::index', ['as' => 'admin.metrics']);
});
