<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Module routes are auto-discovered via Config/Modules.php
 * All feature routes (auth, dashboard, profile, files, users, audit, api-keys, metrics)
 * are defined in their respective module Config/Routes.php files.
 */

// Root redirect
$routes->get('/', static fn() => redirect()->to(site_url('login')));
