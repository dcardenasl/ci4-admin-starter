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

        if ($role !== 'admin') {
            return redirect()->to(site_url('dashboard'))->with('error', 'No tienes permisos para esta seccion.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
