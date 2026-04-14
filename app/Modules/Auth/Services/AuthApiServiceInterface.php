<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface AuthApiServiceInterface
{
    /** @return ApiResponse */
    public function login(array $credentials): array;

    /** @return ApiResponse */
    public function googleLogin(array $payload): array;

    /** @return ApiResponse */
    public function register(array $payload): array;

    /** @return ApiResponse */
    public function forgotPassword(string $email, ?string $clientBaseUrl = null): array;

    /** @return ApiResponse */
    public function resetPassword(array $payload): array;

    /** @return ApiResponse */
    public function verifyEmail(string $token): array;

    /** @return ApiResponse */
    public function logout(): array;

    /** @return ApiResponse */
    public function me(): array;

    /** @return ApiResponse */
    public function resendVerification(array $payload = []): array;
}
