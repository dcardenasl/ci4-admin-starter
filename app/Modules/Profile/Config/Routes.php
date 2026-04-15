<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/profile', '\App\Modules\Profile\Controllers\ProfileController::index', ['as' => 'profile']);
    $routes->post('/profile', '\App\Modules\Profile\Controllers\ProfileController::update', ['as' => 'profile.update']);
    $routes->post('/profile/request-password-reset', '\App\Modules\Profile\Controllers\ProfileController::requestPasswordReset', ['as' => 'profile.requestPasswordReset']);
    $routes->post('/profile/resend-verification', '\App\Modules\Profile\Controllers\ProfileController::resendVerification', ['as' => 'profile.resendVerification']);
});
