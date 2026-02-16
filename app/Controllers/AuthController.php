<?php

namespace App\Controllers;

use App\Services\AuthApiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuthController extends BaseWebController
{
    protected AuthApiService $authService;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->authService = service('authApiService');
    }

    public function login(): string|RedirectResponse
    {
        if ($this->session->has('access_token')) {
            return redirect()->to(site_url('dashboard'));
        }

        return $this->renderAuth('auth/login', [
            'title'    => lang('Auth.loginTitle'),
            'subtitle' => lang('Auth.loginSubtitle'),
        ]);
    }

    public function attemptLogin(): RedirectResponse
    {
        if (! $this->validate([
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ])) {
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
        }

        $payload = [
            'email'    => (string) $this->request->getPost('email'),
            'password' => (string) $this->request->getPost('password'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->login($payload));

        if (! $response['ok']) {
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Auth.loginFailed')));
        }

        $data = $this->extractData($response);
        $this->persistAuthSession($data);

        return redirect()->to(site_url('dashboard'))->with('success', lang('Auth.loginSuccess'));
    }

    public function register(): string
    {
        return $this->renderAuth('auth/register', [
            'title'    => lang('Auth.registerTitle'),
            'subtitle' => lang('Auth.registerSubtitle'),
        ]);
    }

    public function attemptRegister(): RedirectResponse
    {
        if (! $this->validate([
            'first_name'            => 'required|min_length[2]|max_length[100]',
            'last_name'             => 'required|min_length[2]|max_length[100]',
            'email'                 => 'required|valid_email',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
        }

        $payload = [
            'first_name'            => (string) $this->request->getPost('first_name'),
            'last_name'             => (string) $this->request->getPost('last_name'),
            'email'                 => (string) $this->request->getPost('email'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->register($payload));

        if (! $response['ok']) {
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Auth.registerFailed')));
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.registerSuccess'));
    }

    public function forgotPassword(): string
    {
        return $this->renderAuth('auth/forgot_password', [
            'title'    => lang('Auth.forgotTitle'),
            'subtitle' => lang('Auth.forgotSubtitle'),
        ]);
    }

    public function attemptForgotPassword(): RedirectResponse
    {
        if (! $this->validate([
            'email' => 'required|valid_email',
        ])) {
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
        }

        $email = (string) $this->request->getPost('email');
        $response = $this->safeApiCall(fn () => $this->authService->forgotPassword($email));

        if (! $response['ok']) {
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Auth.forgotFailed')));
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.forgotSuccess'));
    }

    public function resetPassword(): string
    {
        return $this->renderAuth('auth/reset_password', [
            'title'    => lang('Auth.resetTitle'),
            'subtitle' => lang('Auth.resetSubtitle'),
            'token'    => (string) $this->request->getGet('token'),
        ]);
    }

    public function attemptResetPassword(): RedirectResponse
    {
        if (! $this->validate([
            'token'                 => 'required',
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('fieldErrors', $this->validator->getErrors());
        }

        $payload = [
            'token'                 => (string) $this->request->getPost('token'),
            'password'              => (string) $this->request->getPost('password'),
            'password_confirmation' => (string) $this->request->getPost('password_confirmation'),
        ];

        $response = $this->safeApiCall(fn () => $this->authService->resetPassword($payload));

        if (! $response['ok']) {
            $fieldErrors = $this->getFieldErrors($response);

            if (! empty($fieldErrors)) {
                return $this->withFieldErrors($fieldErrors);
            }

            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Auth.resetFailed')));
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.resetSuccess'));
    }

    public function verifyEmail(): string
    {
        $token = (string) $this->request->getGet('token');
        $response = $this->safeApiCall(fn () => $this->authService->verifyEmail($token));

        return $this->renderAuth('auth/verify_email', [
            'title'    => lang('Auth.verifyTitle'),
            'subtitle' => lang('Auth.verifySubtitle'),
            'verified' => $response['ok'],
            'message'  => $this->firstMessage($response, $response['ok'] ? lang('Auth.verifySuccess') : lang('Auth.verifyFailed')),
        ]);
    }

    public function logout(): RedirectResponse
    {
        if ($this->session->has('access_token')) {
            $this->safeApiCall(fn () => $this->authService->logout());
        }

        $this->session->destroy();

        return redirect()->to(site_url('login'))->with('success', lang('Auth.logoutSuccess'));
    }

    protected function persistAuthSession(array $data): void
    {
        $this->session->set('access_token', $data['access_token'] ?? null);
        $this->session->set('refresh_token', $data['refresh_token'] ?? null);
        $this->session->set('token_expires_at', time() + (int) ($data['expires_in'] ?? 3600));
        $this->session->set('user', $data['user'] ?? []);
    }
}
