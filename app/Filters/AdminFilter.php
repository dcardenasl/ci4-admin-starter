<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('user');
        $role = null;

        if (is_array($user)) {
            $role = $user['role'] ?? null;
        } elseif (is_object($user)) {
            $role = $user->role ?? null;
        }

        $roleValue = is_scalar($role) ? strtolower((string) $role) : '';

        if (! in_array($roleValue, ['admin', 'superadmin'], true)) {
            log_message('debug', 'AdminFilter: Redirecting user to dashboard. Role found: ' . $roleValue . '. User in session: ' . (is_null($user) ? 'NULL' : gettype($user)));
            return redirect()->to(site_url('dashboard'))->with('error', 'No tienes permisos para esta seccion.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
