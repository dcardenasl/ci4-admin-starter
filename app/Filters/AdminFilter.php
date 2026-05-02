<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('auth');

        if (! has_permission('iam.admin-access')) {
            log_message('debug', 'AdminFilter: missing iam.admin-access permission.');

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
