<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * RateLimitFilter defaults — overridable per deployment without touching
 * the filter itself.
 */
class RateLimit extends BaseConfig
{
    /**
     * Default request budget per window (overridden per-route via
     * `ratelimit:100,30` filter arguments). Override via `ADMIN_RATE_LIMIT_REQUESTS`.
     */
    public int $maxRequests = 200;

    /**
     * Window length in seconds. Override via `ADMIN_RATE_LIMIT_WINDOW`.
     */
    public int $windowSeconds = 60;

    public function __construct()
    {
        parent::__construct();

        $maxRequests = env('ADMIN_RATE_LIMIT_REQUESTS');
        if (is_numeric($maxRequests)) {
            $this->maxRequests = max(1, (int) $maxRequests);
        }

        $windowSeconds = env('ADMIN_RATE_LIMIT_WINDOW');
        if (is_numeric($windowSeconds)) {
            $this->windowSeconds = max(1, (int) $windowSeconds);
        }
    }
}
