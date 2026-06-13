<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// IAM management is restricted to superadmins. Admins assign roles to users
// directly from the Users module via the form's role multi-select.
$routes->group('admin/iam', ['filter' => ['auth', 'superadmin']], static function (RouteCollection $routes): void {
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
    $routes->get('role-permissions', '\\App\\Modules\\Iam\\Controllers\\RolePermissionsController::index', ['as' => 'admin.iam.role_permissions']);
    $routes->post('role-permissions/(:segment)', '\\App\\Modules\\Iam\\Controllers\\RolePermissionsController::save/$1', ['as' => 'admin.iam.role_permissions.save']);

    // Permission
    $routes->get('permissions', '\App\Modules\Iam\Controllers\PermissionController::index', ['as' => 'admin.iam.permissions']);
    $routes->get('permissions/data', '\App\Modules\Iam\Controllers\PermissionController::data', ['as' => 'admin.iam.permissions.data']);
    $routes->get('permissions/create', '\App\Modules\Iam\Controllers\PermissionController::create', ['as' => 'admin.iam.permissions.create']);
    $routes->post('permissions', '\App\Modules\Iam\Controllers\PermissionController::store', ['as' => 'admin.iam.permissions.store']);
    $routes->get('permissions/(:segment)/edit', '\App\Modules\Iam\Controllers\PermissionController::edit/$1', ['as' => 'admin.iam.permissions.edit']);
    $routes->post('permissions/(:segment)', '\App\Modules\Iam\Controllers\PermissionController::update/$1', ['as' => 'admin.iam.permissions.update']);
    $routes->post('permissions/(:segment)/delete', '\App\Modules\Iam\Controllers\PermissionController::delete/$1', ['as' => 'admin.iam.permissions.delete']);
    $routes->get('permissions/(:segment)', '\App\Modules\Iam\Controllers\PermissionController::show/$1', ['as' => 'admin.iam.permissions.show']);

    // Application (read-only — managed server-side via `php spark apps:bootstrap`)
    $routes->get('applications', '\App\Modules\Iam\Controllers\ApplicationController::index', ['as' => 'admin.iam.applications']);
    $routes->get('applications/data', '\App\Modules\Iam\Controllers\ApplicationController::data', ['as' => 'admin.iam.applications.data']);
    $routes->get('applications/(:segment)', '\App\Modules\Iam\Controllers\ApplicationController::show/$1', ['as' => 'admin.iam.applications.show']);
});
