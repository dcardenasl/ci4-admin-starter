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
        try {
            $response = $this->authService->me();
        } catch (\Throwable $e) {
            // A Hub timeout/connection error here must not crash whatever page
            // triggered the refresh (e.g. AdminFilter on every gated request) —
            // degrade by keeping the stale session permissions and log for
            // visibility instead of propagating the exception.
            log_message('warning', 'PermissionsSessionRefresher: Hub unavailable, keeping stale session permissions. {message}', [
                'message' => $e->getMessage(),
            ]);

            return;
        }

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
