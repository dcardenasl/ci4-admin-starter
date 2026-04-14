<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Public routes
$routes->get('/login', 'App\Modules\Auth\Controllers\AuthController::login');
$routes->post('/login', 'App\Modules\Auth\Controllers\AuthController::attemptLogin');
$routes->post('/login/google', 'App\Modules\Auth\Controllers\AuthController::attemptGoogleLogin');

$routes->get('/register', 'App\Modules\Auth\Controllers\AuthController::register');
$routes->post('/register', 'App\Modules\Auth\Controllers\AuthController::attemptRegister');

$routes->get('/forgot-password', 'App\Modules\Auth\Controllers\AuthController::forgotPassword');
$routes->post('/forgot-password', 'App\Modules\Auth\Controllers\AuthController::attemptForgotPassword');

$routes->get('/reset-password', 'App\Modules\Auth\Controllers\AuthController::resetPassword');
$routes->post('/reset-password', 'App\Modules\Auth\Controllers\AuthController::attemptResetPassword');

$routes->get('/verify-email', 'App\Modules\Auth\Controllers\AuthController::verifyEmail');

$routes->post('/logout', 'App\Modules\Auth\Controllers\AuthController::logout');
