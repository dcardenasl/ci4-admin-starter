<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Admin section UI gating.
 *
 * `AdminFilter` lets a user enter the admin section if they hold ANY of the
 * permissions listed below. Per-route enforcement is handled by the
 * fine-grained `permission:<code>` filter (see app/Config/Routes.php).
 *
 * When a new admin-only module is added to the API:
 *  1. Define its permissions in the API's RbacBootstrapSeeder.
 *  2. Add the module's *.read permission code to $permissions here.
 *  3. Add the per-route permission filter on the route group.
 *
 * Centralizing this list here (instead of hardcoding in AdminFilter)
 * prevents the gate from drifting out of sync with the API.
 */
class AdminAccess extends BaseConfig
{
    /**
     * Permission codes whose presence grants entry to the admin section.
     * Override via the env var `ADMIN_PERMISSIONS` (comma-separated) when
     * a deployment adds custom admin-only modules.
     *
     * @var list<string>
     */
    public array $permissions = [
        'users.read',
        'audit.read',
        'apikeys.read',
        'metrics.read',
        'iam.admin-access',
    ];

    public function __construct()
    {
        parent::__construct();

        $envValue = (string) (env('ADMIN_PERMISSIONS', '') ?? '');
        if ($envValue === '') {
            return;
        }

        $codes = array_values(array_filter(array_map(
            static fn (string $code): string => trim($code),
            explode(',', $envValue)
        ), static fn (string $code): bool => $code !== ''));

        if ($codes !== []) {
            $this->permissions = $codes;
        }
    }
}
