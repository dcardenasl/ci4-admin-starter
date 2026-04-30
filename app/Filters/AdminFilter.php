<?php

declare(strict_types=1);

namespace App\Filters;

use App\Support\SessionKeys;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get(SessionKeys::USER->value);
        $role = null;

        if (is_array($user)) {
            $role = $user['role'] ?? null;
        } elseif (is_object($user)) {
            $role = $user->role ?? null;
        }

        $roleValue = is_scalar($role) ? strtolower((string) $role) : '';

        /** @var \Config\Auth $authConfig */
        $authConfig = config('Auth');
        if (! in_array($roleValue, $authConfig->adminRoles, true)) {
            log_message('debug', 'AdminFilter: insufficient role for admin route.');

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
