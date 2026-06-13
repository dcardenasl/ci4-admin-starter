<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Modules\Auth\Services\AuthApiServiceInterface;

class PermissionsSessionRefresher
{
    public function __construct(private readonly AuthApiServiceInterface $auth)
    {
    }

    public function refreshIfStale(int $ttlSeconds = 60): void
    {
        $lastRefresh = (int) (session('permissions_refreshed_at') ?? 0);
        if ($lastRefresh > 0 && time() - $lastRefresh < $ttlSeconds) {
            return;
        }

        $this->forceRefresh();
    }

    public function forceRefresh(): void
    {
        $response = $this->auth->me();
        if (! ($response['ok'] ?? false)) {
            return;
        }

        $data = $response['data'] ?? [];
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if ($data !== []) {
            session()->set('user', $data);
            session()->set('permissions_refreshed_at', time());
        }
    }
}
