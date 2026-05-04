<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Permission filter for admin web routes.
 *
 * Usage in route declarations:
 *   ['filter' => 'permission:apikeys.read']
 *
 * Permission codes use the same dot-separator convention as the API
 * (`users.write`, `audit.read`, `apikeys.write`, `iam.superadmin-access`).
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('auth');

        $required = is_array($arguments) ? (string) ($arguments[0] ?? '') : '';

        if ($required === '' || ! has_permission($required)) {
            log_message('debug', "PermissionFilter: missing '{$required}' permission.");

            if ($request instanceof IncomingRequest && $request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['ok' => false, 'message' => lang('Auth.noPermission')]);
            }

            return redirect()->to(site_url('dashboard'))->with('error', lang('Auth.noPermission'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
