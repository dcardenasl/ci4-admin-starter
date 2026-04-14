<?php

declare(strict_types=1);

namespace App\Modules\Profile\Services;

/**
 * @phpstan-import-type ApiResponse from \App\Libraries\ApiClientInterface
 */
interface ProfileApiServiceInterface
{
    /** @return ApiResponse */
    public function me(): array;

    /** @return ApiResponse */
    public function update(string $userId, array $payload): array;

    /** @return ApiResponse */
    public function forgotPassword(string $email, string $clientBaseUrl): array;

    /** @return ApiResponse */
    public function resendVerification(array $payload = []): array;
}
