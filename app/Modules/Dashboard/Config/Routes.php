<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/dashboard', 'App\Modules\Dashboard\Controllers\DashboardController::index', ['as' => 'dashboard']);
});
