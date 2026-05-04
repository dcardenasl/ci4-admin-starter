<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    // Read access
    $routes->get('api-keys', '\App\Modules\ApiKeys\Controllers\ApiKeyController::index', [
        'as' => 'admin.api_keys', 'filter' => 'permission:apikeys.read',
    ]);
    $routes->get('api-keys/data', '\App\Modules\ApiKeys\Controllers\ApiKeyController::data', [
        'as' => 'admin.api_keys.data', 'filter' => 'permission:apikeys.read',
    ]);
    $routes->get('api-keys/(:segment)', '\App\Modules\ApiKeys\Controllers\ApiKeyController::show/$1', [
        'as' => 'admin.api_keys.show', 'filter' => 'permission:apikeys.read',
    ]);

    // Write access — superadmin-only by default (apikeys.write is not granted to admin).
    $routes->get('api-keys/create', '\App\Modules\ApiKeys\Controllers\ApiKeyController::create', [
        'as' => 'admin.api_keys.create', 'filter' => 'permission:apikeys.write',
    ]);
    $routes->post('api-keys', '\App\Modules\ApiKeys\Controllers\ApiKeyController::store', [
        'as' => 'admin.api_keys.store', 'filter' => 'permission:apikeys.write',
    ]);
    $routes->get('api-keys/(:segment)/edit', '\App\Modules\ApiKeys\Controllers\ApiKeyController::edit/$1', [
        'as' => 'admin.api_keys.edit', 'filter' => 'permission:apikeys.write',
    ]);
    $routes->post('api-keys/(:segment)', '\App\Modules\ApiKeys\Controllers\ApiKeyController::update/$1', [
        'as' => 'admin.api_keys.update', 'filter' => 'permission:apikeys.write',
    ]);
    $routes->post('api-keys/(:segment)/delete', '\App\Modules\ApiKeys\Controllers\ApiKeyController::delete/$1', [
        'as' => 'admin.api_keys.delete', 'filter' => 'permission:apikeys.write',
    ]);
});
