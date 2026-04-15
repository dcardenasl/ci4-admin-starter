<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->post('/language/set', '\App\Modules\Language\Controllers\LanguageController::set', ['as' => 'language.set']);
