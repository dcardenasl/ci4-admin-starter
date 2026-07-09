<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Modules\Auth\Services\AuthApiServiceInterface;
use App\Support\SessionKeys;

final class PermissionsSessionRefresher
{
    private const SESSION_REFRESHED_AT = 'permissions_refreshed_at';

    public function __construct(private readonly AuthApiServiceInterface $authService)
    {
    }

    public function refreshIfStale(int $ttlSeconds = 60): void
    {
        $lastRefresh = session(self::SESSION_REFRESHED_AT);
        if (is_int($lastRefresh) && $lastRefresh > time() - $ttlSeconds) {
            return;
        }

        $this->forceRefresh();
    }

    public function forceRefresh(): void
    {
        $response = $this->authService->me();
        if (! ($response['ok'] ?? false)) {
            return;
        }

        $data = $response['data']['data'] ?? $response['data'] ?? [];
        if (! is_array($data) || $data === []) {
            return;
        }

        session()->set(SessionKeys::USER->value, $data);
        session()->set(self::SESSION_REFRESHED_AT, time());
    }
}
