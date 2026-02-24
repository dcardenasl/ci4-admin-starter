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
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        $roleValue = is_scalar($role) ? strtolower((string) $role) : '';
        if (! in_array($roleValue, ['admin', 'superadmin'], true)) {
            return redirect()->to(site_url('dashboard'))->with('error', 'No tienes permisos para esta seccion.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
