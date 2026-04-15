<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/dashboard', '\App\Modules\Dashboard\Controllers\DashboardController::index', ['as' => 'dashboard']);
});
