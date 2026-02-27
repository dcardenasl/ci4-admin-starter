<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', static fn() => redirect()->to(site_url('login')));
$routes->get('/language/set', 'LanguageController::set');

$routes->get('/debug-session', 'DebugController::index');

// Publicas
$routes->get('/login', 'AuthController::login');
$routes->post('/login', 'AuthController::attemptLogin');
$routes->post('/login/google', 'AuthController::attemptGoogleLogin');
$routes->get('/register', 'AuthController::register');
$routes->post('/register', 'AuthController::attemptRegister');
$routes->get('/forgot-password', 'AuthController::forgotPassword');
$routes->post('/forgot-password', 'AuthController::attemptForgotPassword');
$routes->get('/reset-password', 'AuthController::resetPassword');
$routes->post('/reset-password', 'AuthController::attemptResetPassword');
$routes->get('/verify-email', 'AuthController::verifyEmail');
$routes->get('/logout', 'AuthController::logout');

// Autenticadas
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/dashboard', 'DashboardController::index');
    $routes->get('/profile', 'ProfileController::index');
    $routes->post('/profile', 'ProfileController::update');
    $routes->post('/profile/request-password-reset', 'ProfileController::requestPasswordReset');
    $routes->post('/profile/resend-verification', 'ProfileController::resendVerification');
    $routes->get('/files', 'FileController::index');
    $routes->get('/files/data', 'FileController::data');
    $routes->post('/files/upload', 'FileController::upload');
    $routes->get('/files/(:segment)/download', 'FileController::download/$1');
    $routes->get('/files/(:segment)/view', 'FileController::view/$1');
    $routes->post('/files/(:segment)/delete', 'FileController::delete/$1');
});

// Admin
$routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    $routes->get('users', 'UserController::index');
    $routes->get('users/data', 'UserController::data');
    $routes->get('users/create', 'UserController::create');
    $routes->post('users', 'UserController::store');
    $routes->get('users/(:segment)', 'UserController::show/$1');
    $routes->get('users/(:segment)/edit', 'UserController::edit/$1');
    $routes->post('users/(:segment)', 'UserController::update/$1');
    $routes->post('users/(:segment)/delete', 'UserController::delete/$1');
    $routes->post('users/(:segment)/approve', 'UserController::approve/$1');

    $routes->get('audit', 'AuditController::index');
    $routes->get('audit/data', 'AuditController::data');
    $routes->get('audit/(:segment)', 'AuditController::show/$1');
    $routes->get('audit/entity/(:segment)/(:segment)', 'AuditController::byEntity/$1/$2');

    $routes->get('api-keys', 'ApiKeyController::index');
    $routes->get('api-keys/data', 'ApiKeyController::data');
    $routes->get('api-keys/create', 'ApiKeyController::create');
    $routes->post('api-keys', 'ApiKeyController::store');
    $routes->get('api-keys/(:segment)', 'ApiKeyController::show/$1');
    $routes->get('api-keys/(:segment)/edit', 'ApiKeyController::edit/$1');
    $routes->post('api-keys/(:segment)', 'ApiKeyController::update/$1');
    $routes->post('api-keys/(:segment)/delete', 'ApiKeyController::delete/$1');

    $routes->get('metrics', 'MetricsController::index');
});
