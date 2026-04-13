<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Authentication and authorization configuration.
 */
class Auth extends BaseConfig
{
    /**
     * Roles with access to protected admin routes.
     *
     * @var list<string>
     */
    public array $adminRoles = ['admin', 'superadmin'];
}
