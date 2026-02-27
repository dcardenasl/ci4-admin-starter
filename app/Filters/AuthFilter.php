<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $accessToken = session()->get('access_token');

        if ($accessToken === null || $accessToken === '') {
            log_message('debug', 'AuthFilter: No access_token found in session. Current session keys: ' . implode(', ', array_keys(session()->get() ?? [])));
            return redirect()->to(site_url('login'))->with('error', 'Tu sesion expiro. Inicia sesion.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
