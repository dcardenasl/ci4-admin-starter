<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('admin/iam', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    // Role
    $routes->get('roles', '\\App\\Modules\\Iam\\Controllers\\RoleController::index', ['as' => 'admin.iam.roles']);
    $routes->get('roles/data', '\\App\\Modules\\Iam\\Controllers\\RoleController::data', ['as' => 'admin.iam.roles.data']);
    $routes->get('roles/create', '\\App\\Modules\\Iam\\Controllers\\RoleController::create', ['as' => 'admin.iam.roles.create']);
    $routes->post('roles', '\\App\\Modules\\Iam\\Controllers\\RoleController::store', ['as' => 'admin.iam.roles.store']);
    $routes->get('roles/(:segment)', '\\App\\Modules\\Iam\\Controllers\\RoleController::show/$1', ['as' => 'admin.iam.roles.show']);
    $routes->get('roles/(:segment)/edit', '\\App\\Modules\\Iam\\Controllers\\RoleController::edit/$1', ['as' => 'admin.iam.roles.edit']);
    $routes->post('roles/(:segment)', '\\App\\Modules\\Iam\\Controllers\\RoleController::update/$1', ['as' => 'admin.iam.roles.update']);
    $routes->post('roles/(:segment)/delete', '\\App\\Modules\\Iam\\Controllers\\RoleController::delete/$1', ['as' => 'admin.iam.roles.delete']);
});
