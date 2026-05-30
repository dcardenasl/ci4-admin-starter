<?php

declare(strict_types=1);

namespace App\Modules\Universal\Config;

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('admin/universal', ['filter' => 'auth', 'namespace' => 'App\Modules\Universal\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('(:segment)', 'UniversalController::index/$1', ['as' => 'admin.universal.index']);
    $routes->get('(:segment)/data', 'UniversalController::data/$1', ['as' => 'admin.universal.data']);
    $routes->get('(:segment)/create', 'UniversalController::create/$1', ['as' => 'admin.universal.create']);
    $routes->post('(:segment)', 'UniversalController::store/$1', ['as' => 'admin.universal.store']);
    $routes->get('(:segment)/(:segment)/edit', 'UniversalController::edit/$1/$2', ['as' => 'admin.universal.edit']);
    $routes->post('(:segment)/(:segment)', 'UniversalController::update/$1/$2', ['as' => 'admin.universal.update']);
    $routes->post('(:segment)/(:segment)/delete', 'UniversalController::delete/$1/$2', ['as' => 'admin.universal.delete']);
});
