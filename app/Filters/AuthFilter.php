<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $accessToken = $session->get('access_token');
        $expiresAt = (int) ($session->get('token_expires_at') ?? 0);

        if ($expiresAt > 0 && $expiresAt <= time()) {
            $session->remove(['access_token', 'refresh_token', 'token_expires_at', 'user']);
            log_message('debug', 'AuthFilter: token expired before request.');

            if ($request instanceof IncomingRequest && $request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['ok' => false, 'message' => lang('Auth.sessionExpired')]);
            }

            return redirect()->to(site_url('login'))->with('error', lang('Auth.sessionExpired'));
        }

        if ($accessToken === null || $accessToken === '') {
            log_message('debug', 'AuthFilter: no access_token in session.');

            if ($request instanceof IncomingRequest && $request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['ok' => false, 'message' => lang('Auth.sessionExpired')]);
            }

            return redirect()->to(site_url('login'))->with('error', lang('Auth.sessionExpired'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
