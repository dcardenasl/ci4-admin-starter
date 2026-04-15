<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/files', '\App\Modules\Files\Controllers\FileController::index', ['as' => 'files']);
    $routes->get('/files/data', '\App\Modules\Files\Controllers\FileController::data', ['as' => 'files.data']);
    $routes->post('/files/upload', '\App\Modules\Files\Controllers\FileController::upload', ['as' => 'files.upload']);
    $routes->get('/files/(:segment)/download', '\App\Modules\Files\Controllers\FileController::download/$1', ['as' => 'files.download']);
    $routes->get('/files/(:segment)/view', '\App\Modules\Files\Controllers\FileController::view/$1', ['as' => 'files.view']);
    $routes->post('/files/(:segment)/delete', '\App\Modules\Files\Controllers\FileController::delete/$1', ['as' => 'files.delete']);
});
