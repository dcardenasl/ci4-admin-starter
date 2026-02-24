<?php

namespace App\Services;

class AuthApiService extends BaseApiService
{
    public function login(array $credentials): array
    {
        return $this->apiClient->publicPost('/auth/login', $credentials);
    }

    public function googleLogin(array $payload): array
    {
        return $this->apiClient->publicPost('/auth/google-login', $payload);
    }

    public function register(array $payload): array
    {
        return $this->apiClient->publicPost('/auth/register', $payload);
    }

    public function forgotPassword(string $email, ?string $clientBaseUrl = null): array
    {
        $payload = ['email' => $email];
        if ($clientBaseUrl !== null && $clientBaseUrl !== '') {
            $payload['client_base_url'] = $clientBaseUrl;
        }

        return $this->apiClient->publicPost('/auth/forgot-password', $payload);
    }

    public function resetPassword(array $payload): array
    {
        return $this->apiClient->publicPost('/auth/reset-password', $payload);
    }

    public function verifyEmail(string $token): array
    {
        return $this->apiClient->publicGet('/auth/verify-email', ['token' => $token]);
    }

    public function logout(): array
    {
        return $this->apiClient->post('/auth/revoke');
    }

    public function me(): array
    {
        return $this->apiClient->get('/auth/me');
    }

    public function resendVerification(array $payload = []): array
    {
        return $this->apiClient->post('/auth/resend-verification', $payload);
    }
}
