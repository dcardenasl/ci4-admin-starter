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
use Config\Services;

class AuthController extends BaseWebController
{
    protected AuthApiService $authService;

    public function initController(RequestInterface $request, ResponseInterface $response, \Psr\Log\LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->authService = service('authApiService');
    }

    public function login(): ResponseInterface|string
    {
        if ($this->session->has('access_token')) {
            return redirect()->to(site_url('dashboard'));
        }

        return $this->renderAuth('auth/login', [
            'title'          => lang('Auth.login_title'),
            'subtitle'       => lang('Auth.login_subtitle'),
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

        $response = $this->safeApiCall(fn() => $this->authService->login($request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Auth.login_failed'), null, true, ['email', 'password']);
        }

        $this->persistAuthSession($this->extractData($response));

        return redirect()->to(site_url('dashboard'))->with('success', lang('Auth.login_success'));
    }

    public function attemptGoogleLogin(): RedirectResponse
    {
        if ($this->session->has('access_token')) {
            return redirect()->to(site_url('dashboard'));
        }

        if (! $this->isGoogleLoginEnabled()) {
            return redirect()->to(site_url('login'))->with('error', lang('Auth.google_login_unavailable'));
        }

        /** @var GoogleLoginRequest $request */
        $request = service('formRequest', GoogleLoginRequest::class, false);
        $invalid = $this->validateRequest($request);
        if ($invalid !== null) {
            return redirect()->to(site_url('login'))->with('error', lang('Auth.google_login_failed'));
        }

        $payload = $request->payload();
        if (! $this->hasValidGoogleIdTokenClaims((string) ($payload['id_token'] ?? ''))) {
            return redirect()->to(site_url('login'))->with('error', lang('Auth.google_invalid_token'));
        }

        $payload['client_base_url'] = $this->clientBaseUrl();

        $response = $this->safeApiCall(fn() => $this->authService->googleLogin($payload));

        if (! $response['ok']) {
            return redirect()->to(site_url('login'))
                ->with('error', $this->firstMessage($response, lang('Auth.google_login_failed')));
        }

        $data = $this->extractData($response);

        // Handle 202 Accepted (Pending approval)
        if ($response['status'] === 202 || ! isset($data['access_token'])) {
            return redirect()->to(site_url('login'))
                ->with('error', $this->firstMessage($response, lang('Auth.google_login_pending_approval')));
        }

        $this->persistAuthSession($data);

        return redirect()->to(site_url('dashboard'))->with('success', lang('Auth.login_success'));
    }

    public function register(): string
    {
        return $this->renderAuth('auth/register', [
            'title'    => lang('Auth.register_title'),
            'subtitle' => lang('Auth.register_subtitle'),
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
                lang('Auth.register_failed'),
                null,
                true,
                ['first_name', 'last_name', 'email', 'password', 'password_confirmation'],
            );
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.register_success'));
    }

    public function forgotPassword(): string
    {
        return $this->renderAuth('auth/forgot_password', [
            'title'    => lang('Auth.forgot_title'),
            'subtitle' => lang('Auth.forgot_subtitle'),
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

        $payload = $request->payload();
        $payload['client_base_url'] = $this->clientBaseUrl();

        $response = $this->safeApiCall(fn() => $this->authService->forgotPassword($payload['email'], $payload['client_base_url']));

        return redirect()->to(site_url('login'))->with('success', lang('Auth.forgot_success'));
    }

    public function resetPassword(): string
    {
        return $this->renderAuth('auth/reset_password', [
            'title'    => lang('Auth.reset_title'),
            'subtitle' => lang('Auth.reset_subtitle'),
            'token'    => $this->request->getGet('token'),
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
            return $this->failApi($response, lang('Auth.reset_failed'), null, true, ['token', 'password', 'password_confirmation']);
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.reset_success'));
    }

    public function verifyEmail(): string
    {
        $token = (string) $this->request->getGet('token');
        $response = $this->safeApiCall(fn() => $this->authService->verifyEmail($token));

        return $this->renderAuth('auth/verify_email', [
            'title'    => lang('Auth.verify_title'),
            'subtitle' => lang('Auth.verify_subtitle'),
            'verified' => $response['ok'],
            'message'  => $this->firstMessage($response, $response['ok'] ? lang('Auth.verify_success') : lang('Auth.verify_failed')),
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

        return redirect()->to(site_url('login'))->with('success', lang('Auth.logout_success'));
    }

    protected function persistAuthSession(array $data): void
    {
        $this->session->regenerate(true);
        $this->session->set('access_token', $data['access_token'] ?? null);
        $this->session->set('refresh_token', $data['refresh_token'] ?? null);
        $this->session->set('token_expires_at', time() + (int) ($data['expires_in'] ?? 3600));
        $this->session->set('user', $data['user'] ?? []);
    }

    protected function isGoogleLoginEnabled(): bool
    {
        return trim((string) env('GOOGLE_CLIENT_ID', '')) !== '';
    }

    protected function hasValidGoogleIdTokenClaims(string $idToken): bool
    {
        $claims = $this->decodeJwtPayload($idToken);
        if ($claims === null) {
            return false;
        }

        $issuer = (string) ($claims['iss'] ?? '');
        if (! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            return false;
        }

        $audience = $claims['aud'] ?? null;
        $expectedAudience = trim((string) env('GOOGLE_CLIENT_ID', ''));
        if ($expectedAudience === '') {
            return false;
        }

        $matchesAudience = is_string($audience)
            ? hash_equals($expectedAudience, $audience)
            : (is_array($audience) && in_array($expectedAudience, $audience, true));
        if (! $matchesAudience) {
            return false;
        }

        $expiresAt = (int) ($claims['exp'] ?? 0);

        return $expiresAt > time();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJwtPayload(string $token): ?array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            return null;
        }

        $payload = $segments[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false) {
            return null;
        }

        $claims = json_decode($decoded, true);

        return is_array($claims) ? $claims : null;
    }
}
