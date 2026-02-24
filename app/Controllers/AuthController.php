<?php

namespace App\Controllers;

use App\Requests\Auth\ForgotPasswordRequest;
use App\Requests\Auth\GoogleLoginRequest;
use App\Requests\Auth\LoginRequest;
use App\Requests\Auth\RegisterRequest;
use App\Requests\Auth\ResetPasswordRequest;
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
            'title'          => lang('Auth.loginTitle'),
            'subtitle'       => lang('Auth.loginSubtitle'),
            'googleEnabled'  => $this->isGoogleLoginEnabled(),
            'googleClientId' => trim((string) env('GOOGLE_CLIENT_ID', '')),
        ]);
    }

    public function attemptLogin(): RedirectResponse
    {
        /** @var LoginRequest $request */
        $request = service('formRequest', LoginRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();

        $response = $this->safeApiCall(fn() => $this->authService->login($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Auth.loginFailed'), null, true, ['email', 'password']);
        }

        $data = $this->extractData($response);
        $this->persistAuthSession($data);

        return redirect()->to(site_url('dashboard'))->with('success', lang('Auth.loginSuccess'));
    }

    public function attemptGoogleLogin(): RedirectResponse
    {
        if ($this->session->has('access_token')) {
            return redirect()->to(site_url('dashboard'));
        }

        if (! $this->isGoogleLoginEnabled()) {
            return redirect()->to(site_url('login'))->with('error', lang('Auth.googleLoginUnavailable'));
        }

        /** @var GoogleLoginRequest $request */
        $request = service('formRequest', GoogleLoginRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return redirect()->to(site_url('login'))->with('error', lang('Auth.googleLoginFailed'));
        }

        $payload = $request->payload();
        $payload['client_base_url'] = $this->clientBaseUrl();

        $response = $this->safeApiCall(fn() => $this->authService->googleLogin($payload));

        if (! $response['ok']) {
            return redirect()->to(site_url('login'))
                ->with('error', $this->firstMessage($response, lang('Auth.googleLoginFailed')));
        }

        $data = $this->extractData($response);
        if (! isset($data['access_token']) || ! isset($data['refresh_token'])) {
            return redirect()->to(site_url('login'))
                ->with('error', $this->firstMessage($response, lang('Auth.googleLoginPendingApproval')));
        }

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
        /** @var RegisterRequest $request */
        $request = service('formRequest', RegisterRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();
        $payload['client_base_url'] = $this->clientBaseUrl();

        $response = $this->safeApiCall(fn() => $this->authService->register($payload));

        if (! $response['ok']) {
            return $this->failApi(
                $response,
                lang('Auth.registerFailed'),
                null,
                true,
                ['first_name', 'last_name', 'email', 'password', 'password_confirmation'],
            );
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
        /** @var ForgotPasswordRequest $request */
        $request = service('formRequest', ForgotPasswordRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $email = (string) ($request->payload()['email'] ?? '');
        $response = $this->safeApiCall(fn() => $this->authService->forgotPassword(
            $email,
            $this->clientBaseUrl()
        ));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Auth.forgotFailed'), null, true, ['email']);
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.forgotSuccess'));
    }

    public function resetPassword(): string
    {
        return $this->renderAuth('auth/reset_password', [
            'title'    => lang('Auth.resetTitle'),
            'subtitle' => lang('Auth.resetSubtitle'),
            'token'    => (string) $this->request->getGet('token'),
            'email'    => (string) $this->request->getGet('email'),
        ]);
    }

    public function attemptResetPassword(): RedirectResponse
    {
        /** @var ResetPasswordRequest $request */
        $request = service('formRequest', ResetPasswordRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return $invalid;
        }

        $payload = $request->payload();

        $response = $this->safeApiCall(fn() => $this->authService->resetPassword($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Auth.resetFailed'), null, true, ['token', 'password', 'password_confirmation']);
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.resetSuccess'));
    }

    public function verifyEmail(): string
    {
        $token = (string) $this->request->getGet('token');
        $response = $this->safeApiCall(fn() => $this->authService->verifyEmail($token));

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
            $this->safeApiCall(fn() => $this->authService->logout());
        }

        $this->session->remove([
            'access_token',
            'refresh_token',
            'token_expires_at',
            'user',
        ]);
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

    protected function isGoogleLoginEnabled(): bool
    {
        return trim((string) env('GOOGLE_CLIENT_ID', '')) !== '';
    }
}
