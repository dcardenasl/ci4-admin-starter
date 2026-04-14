<?php

declare(strict_types=1);

namespace App\Filters;

use App\Support\SessionKeys;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $accessToken = $session->get(SessionKeys::ACCESS_TOKEN);
        $expiresAt = (int) ($session->get(SessionKeys::EXPIRES_AT) ?? 0);

        if ($expiresAt > 0 && $expiresAt <= time()) {
            $session->remove([SessionKeys::ACCESS_TOKEN, SessionKeys::REFRESH_TOKEN, SessionKeys::EXPIRES_AT, SessionKeys::USER]);
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
