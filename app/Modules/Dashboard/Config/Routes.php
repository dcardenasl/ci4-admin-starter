<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/dashboard', '\App\Modules\Dashboard\Controllers\DashboardController::index', ['as' => 'dashboard']);
    $routes->get('/dashboard/widgets/stats', '\App\Modules\Dashboard\Controllers\DashboardController::widgetStats', ['as' => 'dashboard.widgets.stats']);
    $routes->get('/dashboard/widgets/health', '\App\Modules\Dashboard\Controllers\DashboardController::widgetHealth', ['as' => 'dashboard.widgets.health']);
    $routes->get('/dashboard/widgets/recent-files', '\App\Modules\Dashboard\Controllers\DashboardController::widgetRecentFiles', ['as' => 'dashboard.widgets.recent-files']);
    $routes->get('/dashboard/widgets/activity', '\App\Modules\Dashboard\Controllers\DashboardController::widgetActivity', ['as' => 'dashboard.widgets.activity']);
});
