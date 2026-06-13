<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    /**
     * Web-side admin section gate. Anyone with at least one of the permission
     * codes listed in `Config\AdminAccess::$permissions` passes; finer-grained
     * access (per resource) is enforced by the per-route `permission:<code>`
     * filter or by the controller.
     *
     * Sidebar visibility is gated separately in `partials/sidebar.php`.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('auth');

        $allowedPermissions = config('AdminAccess')->permissions;

        $hasAny = false;
        foreach ($allowedPermissions as $code) {
            if (has_permission($code)) {
                $hasAny = true;
                break;
            }
        }

        if (! $hasAny) {
            log_message('debug', 'AdminFilter: actor has no admin-level permission.');

            if ($request instanceof IncomingRequest && $request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['ok' => false, 'message' => lang('Auth.noPermission')]);
            }

            return redirect()->to(site_url('dashboard'))->with('error', lang('Auth.noPermission'));
        }

        service('permissionsSessionRefresher')->refreshIfStale(60);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
