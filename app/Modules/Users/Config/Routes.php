<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('users', '\App\Modules\Users\Controllers\UserController::index', [
        'as' => 'admin.users', 'filter' => 'permission:users.read',
    ]);
    $routes->get('users/data', '\App\Modules\Users\Controllers\UserController::data', [
        'as' => 'admin.users.data', 'filter' => 'permission:users.read',
    ]);
    $routes->get('users/create', '\App\Modules\Users\Controllers\UserController::create', [
        'as' => 'admin.users.create', 'filter' => 'permission:users.write',
    ]);
    $routes->post('users', '\App\Modules\Users\Controllers\UserController::store', [
        'as' => 'admin.users.store', 'filter' => 'permission:users.write',
    ]);
    $routes->get('users/(:segment)/edit', '\App\Modules\Users\Controllers\UserController::edit/$1', [
        'as' => 'admin.users.edit', 'filter' => 'permission:users.write',
    ]);
    $routes->get('users/(:segment)', '\App\Modules\Users\Controllers\UserController::show/$1', [
        'as' => 'admin.users.show', 'filter' => 'permission:users.read',
    ]);
    $routes->post('users/(:segment)', '\App\Modules\Users\Controllers\UserController::update/$1', [
        'as' => 'admin.users.update', 'filter' => 'permission:users.write',
    ]);
    $routes->post('users/(:segment)/delete', '\App\Modules\Users\Controllers\UserController::delete/$1', [
        'as' => 'admin.users.delete', 'filter' => 'permission:users.write',
    ]);
    $routes->post('users/(:segment)/approve', '\App\Modules\Users\Controllers\UserController::approve/$1', [
        'as' => 'admin.users.approve', 'filter' => 'permission:users.write',
    ]);
});
