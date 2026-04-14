<?php

declare(strict_types=1);

namespace App\Modules\Profile\Services;

use App\Services\BaseApiService;

/**
 * Profile API Service
 *
 * Wraps API endpoints for user profile operations.
 * Consolidates authentication and user data endpoints used by ProfileController.
 */
class ProfileApiService extends BaseApiService implements ProfileApiServiceInterface
{
    /**
     * Get authenticated user profile
     */
    public function me(): array
    {
        return $this->apiClient->get('/auth/me');
    }

    /**
     * Update user profile (name, etc)
     */
    public function update(string $userId, array $payload): array
    {
        return $this->apiClient->put('/users/' . $userId, $payload);
    }

    /**
     * Request password reset email
     */
    public function forgotPassword(string $email, string $clientBaseUrl): array
    {
        return $this->apiClient->publicPost('/auth/forgot-password', [
            'email'            => $email,
            'client_base_url'  => $clientBaseUrl,
        ]);
    }

    /**
     * Resend email verification
     */
    public function resendVerification(array $payload = []): array
    {
        return $this->apiClient->post('/auth/resend-verification', $payload);
    }
}
