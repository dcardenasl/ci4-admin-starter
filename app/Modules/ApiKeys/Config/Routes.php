<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    $routes->get('api-keys', 'App\Modules\ApiKeys\Controllers\ApiKeyController::index', ['as' => 'admin.api_keys']);
    $routes->get('api-keys/data', 'App\Modules\ApiKeys\Controllers\ApiKeyController::data', ['as' => 'admin.api_keys.data']);
    $routes->get('api-keys/create', 'App\Modules\ApiKeys\Controllers\ApiKeyController::create', ['as' => 'admin.api_keys.create']);
    $routes->post('api-keys', 'App\Modules\ApiKeys\Controllers\ApiKeyController::store', ['as' => 'admin.api_keys.store']);
    $routes->get('api-keys/(:segment)', 'App\Modules\ApiKeys\Controllers\ApiKeyController::show/$1', ['as' => 'admin.api_keys.show']);
    $routes->get('api-keys/(:segment)/edit', 'App\Modules\ApiKeys\Controllers\ApiKeyController::edit/$1', ['as' => 'admin.api_keys.edit']);
    $routes->post('api-keys/(:segment)', 'App\Modules\ApiKeys\Controllers\ApiKeyController::update/$1', ['as' => 'admin.api_keys.update']);
    $routes->post('api-keys/(:segment)/delete', 'App\Modules\ApiKeys\Controllers\ApiKeyController::delete/$1', ['as' => 'admin.api_keys.delete']);
});
