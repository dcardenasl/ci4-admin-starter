<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', static fn() => redirect()->to(site_url('login')));
$routes->get('/language/set', 'LanguageController::set');

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
$routes->post('/logout', 'AuthController::logout');

// Autenticadas
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('/dashboard', 'DashboardController::index', ['as' => 'dashboard']);
    $routes->get('/profile', 'ProfileController::index', ['as' => 'profile']);
    $routes->post('/profile', 'ProfileController::update', ['as' => 'profile.update']);
    $routes->post('/profile/request-password-reset', 'ProfileController::requestPasswordReset', ['as' => 'profile.requestPasswordReset']);
    $routes->post('/profile/resend-verification', 'ProfileController::resendVerification', ['as' => 'profile.resendVerification']);
    $routes->get('/files', 'FileController::index', ['as' => 'files']);
    $routes->get('/files/data', 'FileController::data', ['as' => 'files.data']);
    $routes->post('/files/upload', 'FileController::upload', ['as' => 'files.upload']);
    $routes->get('/files/(:segment)/download', 'FileController::download/$1', ['as' => 'files.download']);
    $routes->get('/files/(:segment)/view', 'FileController::view/$1', ['as' => 'files.view']);
    $routes->post('/files/(:segment)/delete', 'FileController::delete/$1', ['as' => 'files.delete']);
});

// Admin
$routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    $routes->get('users', 'UserController::index', ['as' => 'admin.users']);
    $routes->get('users/data', 'UserController::data', ['as' => 'admin.users.data']);
    $routes->get('users/create', 'UserController::create', ['as' => 'admin.users.create']);
    $routes->post('users', 'UserController::store', ['as' => 'admin.users.store']);
    $routes->get('users/(:segment)', 'UserController::show/$1', ['as' => 'admin.users.show']);
    $routes->get('users/(:segment)/edit', 'UserController::edit/$1', ['as' => 'admin.users.edit']);
    $routes->post('users/(:segment)', 'UserController::update/$1', ['as' => 'admin.users.update']);
    $routes->post('users/(:segment)/delete', 'UserController::delete/$1', ['as' => 'admin.users.delete']);
    $routes->post('users/(:segment)/approve', 'UserController::approve/$1', ['as' => 'admin.users.approve']);

    $routes->get('audit', 'AuditController::index', ['as' => 'admin.audit']);
    $routes->get('audit/data', 'AuditController::data', ['as' => 'admin.audit.data']);
    $routes->get('audit/(:segment)', 'AuditController::show/$1', ['as' => 'admin.audit.show']);
    $routes->get('audit/entity/(:segment)/(:segment)', 'AuditController::byEntity/$1/$2', ['as' => 'admin.audit.byEntity']);

    $routes->get('api-keys', 'ApiKeyController::index', ['as' => 'admin.api_keys']);
    $routes->get('api-keys/data', 'ApiKeyController::data', ['as' => 'admin.api_keys.data']);
    $routes->get('api-keys/create', 'ApiKeyController::create', ['as' => 'admin.api_keys.create']);
    $routes->post('api-keys', 'ApiKeyController::store', ['as' => 'admin.api_keys.store']);
    $routes->get('api-keys/(:segment)', 'ApiKeyController::show/$1', ['as' => 'admin.api_keys.show']);
    $routes->get('api-keys/(:segment)/edit', 'ApiKeyController::edit/$1', ['as' => 'admin.api_keys.edit']);
    $routes->post('api-keys/(:segment)', 'ApiKeyController::update/$1', ['as' => 'admin.api_keys.update']);
    $routes->post('api-keys/(:segment)/delete', 'ApiKeyController::delete/$1', ['as' => 'admin.api_keys.delete']);

    $routes->get('metrics', 'MetricsController::index', ['as' => 'admin.metrics']);
});
