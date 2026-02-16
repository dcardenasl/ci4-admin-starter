<?php

namespace App\Services;

use App\Libraries\ApiClient;

class AuthApiService
{
    public function __construct(protected ApiClient $apiClient)
    {
    }

    public function login(array $credentials): array
    {
        return $this->apiClient->publicPost('/auth/login', $credentials);
    }

    public function register(array $payload): array
    {
        return $this->apiClient->publicPost('/auth/register', $payload);
    }

    public function forgotPassword(string $email): array
    {
        return $this->apiClient->publicPost('/auth/forgot-password', ['email' => $email]);
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
        return $this->apiClient->post('/auth/logout');
    }

    public function me(): array
    {
        return $this->apiClient->get('/auth/me');
    }

    public function resendVerification(): array
    {
        return $this->apiClient->post('/auth/resend-verification');
    }

    public function updateProfile(array $payload): array
    {
        return $this->apiClient->put('/auth/profile', $payload);
    }

    public function changePassword(array $payload): array
    {
        return $this->apiClient->post('/auth/change-password', $payload);
    }
}
