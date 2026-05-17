<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Controllers\BaseWebController;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\GoogleLoginRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Services\AuthApiServiceInterface;
use App\Support\SessionKeys;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseWebController
{
    protected AuthApiServiceInterface $authService;

    public function initController(RequestInterface $request, ResponseInterface $response, \Psr\Log\LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->authService = service('authApiService');
    }

    public function login(): ResponseInterface|string
    {
        if ($this->session->has(SessionKeys::ACCESS_TOKEN->value)) {
            return redirect()->to(route_to('dashboard'));
        }

        return $this->renderAuth('auth/login', [
            'title'          => lang('Auth.login_title'),
            'subtitle'       => lang('Auth.login_subtitle'),
            'googleEnabled'  => $this->isGoogleLoginEnabled(),
            'googleClientId' => trim(is_string(env('GOOGLE_CLIENT_ID')) ? env('GOOGLE_CLIENT_ID') : ''),
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

        $response = $this->safeApiCall(fn () => $this->authService->login($request->payload()));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Auth.login_failed'), null, true, ['email', 'password']);
        }

        $this->persistAuthSession($this->extractData($response));

        return redirect()->to(route_to('dashboard'))->with('success', lang('Auth.login_success'));
    }

    public function attemptGoogleLogin(): RedirectResponse
    {
        if ($this->session->has(SessionKeys::ACCESS_TOKEN->value)) {
            return redirect()->to(route_to('dashboard'));
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

        $response = $this->safeApiCall(fn () => $this->authService->googleLogin($payload));

        if (! $response['ok']) {
            return redirect()->to(site_url('login'))
                ->with('error', $this->firstMessage($response, lang('Auth.google_login_failed')));
        }

        $data = $this->extractData($response);

        // Handle 202 Accepted (Pending approval)
        if ($response['status'] === 202 || ! isset($data[SessionKeys::ACCESS_TOKEN->value])) {
            return redirect()->to(site_url('login'))
                ->with('error', $this->firstMessage($response, lang('Auth.google_login_pending_approval')));
        }

        $this->persistAuthSession($data);

        return redirect()->to(route_to('dashboard'))->with('success', lang('Auth.login_success'));
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

        $response = $this->safeApiCall(fn () => $this->authService->register($payload));

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

        $response = $this->safeApiCall(fn () => $this->authService->forgotPassword($payload['email'], $payload['client_base_url']));

        if (! $response['ok']) {
            return redirect()->back()->withInput()->with('error', $this->firstMessage($response, lang('Auth.forgot_failed')));
        }

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

        $response = $this->safeApiCall(fn () => $this->authService->resetPassword($payload));

        if (! $response['ok']) {
            return $this->failApi($response, lang('Auth.reset_failed'), null, true, ['token', 'password', 'password_confirmation']);
        }

        return redirect()->to(site_url('login'))->with('success', lang('Auth.reset_success'));
    }

    public function verifyEmail(): string
    {
        $rawToken = $this->request->getGet('token');
        $token = is_string($rawToken) ? $rawToken : '';
        $response = $this->safeApiCall(fn () => $this->authService->verifyEmail($token));

        return $this->renderAuth('auth/verify_email', [
            'title'    => lang('Auth.verify_title'),
            'subtitle' => lang('Auth.verify_subtitle'),
            'verified' => $response['ok'],
            'message'  => $this->firstMessage($response, $response['ok'] ? lang('Auth.verify_success') : lang('Auth.verify_failed')),
        ]);
    }

    public function logout(): RedirectResponse
    {
        if ($this->session->has(SessionKeys::ACCESS_TOKEN->value)) {
            $this->revokeTokenWithRetry();
        }

        $this->apiClient->clearSessionAuth();
        $this->session->destroy();

        return redirect()->to(site_url('login'))->with('success', lang('Auth.logout_success'));
    }

    /**
     * Best-effort token revocation against the API. Audit B8.5 (2026-05-06):
     * the previous implementation called the logout endpoint exactly once and
     * silently dropped any failure — leaving the access token live on the API
     * even after the local session was destroyed. Now we retry once with a
     * short backoff and surface persistent failures to the audit log.
     *
     * Why one retry, not many: we don't want to keep the user staring at a
     * spinner because the API is having a bad minute. One retry covers the
     * common case (transient network blip) without compounding latency.
     * Persistent failures get logged so an operator can intervene; the local
     * session is destroyed regardless to keep the user-facing logout snappy.
     */
    private function revokeTokenWithRetry(): void
    {
        $maxAttempts = 2;
        $backoffMs = 250;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->safeApiCall(fn () => $this->authService->logout());

            if (($response['ok'] ?? false) === true) {
                return;
            }

            if ($attempt < $maxAttempts) {
                usleep($backoffMs * 1000);
            }
        }

        // Both attempts failed. Log so the operator can detect a stuck-token
        // pattern (often a sign of API/network issues). The local session is
        // destroyed by the caller regardless to keep logout snappy.
        $userId = $this->session->get(SessionKeys::USER->value);
        $userIdSnippet = (is_array($userId) && isset($userId['id'])) ? (string) $userId['id'] : 'unknown';

        log_message(
            'warning',
            sprintf(
                'logout: token revocation failed after %d attempts. user_id=%s. local session destroyed regardless.',
                $maxAttempts,
                $userIdSnippet
            )
        );
    }

    /** @param array<string, mixed> $data */
    protected function persistAuthSession(array $data): void
    {
        $this->session->regenerate(true);
        $this->session->set(SessionKeys::ACCESS_TOKEN->value, $data[SessionKeys::ACCESS_TOKEN->value] ?? null);
        $this->session->set(SessionKeys::REFRESH_TOKEN->value, $data['refresh_token'] ?? null);
        $this->session->set(SessionKeys::EXPIRES_AT->value, time() + (int) ($data['expires_in'] ?? 3600));
        $this->session->set(SessionKeys::USER->value, $data['user'] ?? []);
    }

    protected function isGoogleLoginEnabled(): bool
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        return is_string($clientId) && trim($clientId) !== '';
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
        $rawAudience = env('GOOGLE_CLIENT_ID');
        $expectedAudience = is_string($rawAudience) ? trim($rawAudience) : '';
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
