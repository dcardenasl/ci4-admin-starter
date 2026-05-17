<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gates routes that should only be reachable by accounts holding
 * `iam.superadmin-access`. Used for the IAM module so a regular admin cannot
 * even enumerate the permission graph.
 */
class SuperAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('auth');

        if (! has_permission('iam.superadmin-access')) {
            log_message('debug', 'SuperAdminFilter: missing iam.superadmin-access permission.');

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
