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

    // Role ↔ Permission relations
    $routes->post('roles/(:segment)/permissions/attach', '\\App\\Modules\\Iam\\Controllers\\RoleController::attachPermissions/$1', ['as' => 'admin.iam.roles.permissions.attach']);
    $routes->post('roles/(:segment)/permissions/(:segment)/detach', '\\App\\Modules\\Iam\\Controllers\\RoleController::detachPermission/$1/$2', ['as' => 'admin.iam.roles.permissions.detach']);

    // Permission
    $routes->get('permissions', '\App\Modules\Iam\Controllers\PermissionController::index', ['as' => 'admin.iam.permissions']);
    $routes->get('permissions/data', '\App\Modules\Iam\Controllers\PermissionController::data', ['as' => 'admin.iam.permissions.data']);
    $routes->get('permissions/create', '\App\Modules\Iam\Controllers\PermissionController::create', ['as' => 'admin.iam.permissions.create']);
    $routes->post('permissions', '\App\Modules\Iam\Controllers\PermissionController::store', ['as' => 'admin.iam.permissions.store']);
    $routes->get('permissions/(:segment)', '\App\Modules\Iam\Controllers\PermissionController::show/$1', ['as' => 'admin.iam.permissions.show']);
    $routes->get('permissions/(:segment)/edit', '\App\Modules\Iam\Controllers\PermissionController::edit/$1', ['as' => 'admin.iam.permissions.edit']);
    $routes->post('permissions/(:segment)', '\App\Modules\Iam\Controllers\PermissionController::update/$1', ['as' => 'admin.iam.permissions.update']);
    $routes->post('permissions/(:segment)/delete', '\App\Modules\Iam\Controllers\PermissionController::delete/$1', ['as' => 'admin.iam.permissions.delete']);

    // AppUserMembership
    $routes->get('memberships', '\App\Modules\Iam\Controllers\AppUserMembershipController::index', ['as' => 'admin.iam.memberships']);
    $routes->get('memberships/data', '\App\Modules\Iam\Controllers\AppUserMembershipController::data', ['as' => 'admin.iam.memberships.data']);
    $routes->get('memberships/create', '\App\Modules\Iam\Controllers\AppUserMembershipController::create', ['as' => 'admin.iam.memberships.create']);
    $routes->post('memberships', '\App\Modules\Iam\Controllers\AppUserMembershipController::store', ['as' => 'admin.iam.memberships.store']);
    $routes->get('memberships/(:segment)', '\App\Modules\Iam\Controllers\AppUserMembershipController::show/$1', ['as' => 'admin.iam.memberships.show']);
    $routes->get('memberships/(:segment)/edit', '\App\Modules\Iam\Controllers\AppUserMembershipController::edit/$1', ['as' => 'admin.iam.memberships.edit']);
    $routes->post('memberships/(:segment)', '\App\Modules\Iam\Controllers\AppUserMembershipController::update/$1', ['as' => 'admin.iam.memberships.update']);
    $routes->post('memberships/(:segment)/delete', '\App\Modules\Iam\Controllers\AppUserMembershipController::delete/$1', ['as' => 'admin.iam.memberships.delete']);
});
