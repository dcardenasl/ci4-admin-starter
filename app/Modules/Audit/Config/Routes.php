<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    $routes->get('audit', '\App\Modules\Audit\Controllers\AuditController::index', ['as' => 'admin.audit']);
    $routes->get('audit/data', '\App\Modules\Audit\Controllers\AuditController::data', ['as' => 'admin.audit.data']);
    $routes->get('audit/(:segment)', '\App\Modules\Audit\Controllers\AuditController::show/$1', ['as' => 'admin.audit.show']);
    $routes->get('audit/entity/(:segment)/(:segment)', '\App\Modules\Audit\Controllers\AuditController::byEntity/$1/$2', ['as' => 'admin.audit.byEntity']);
});
