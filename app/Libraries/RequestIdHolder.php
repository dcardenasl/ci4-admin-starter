<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Request ID Holder — admin-starter (audit B10.1, 2026-05-07)
 *
 * Static registry for the per-request correlation ID. The
 * `ApiClient::resolveRequestId()` reads/generates a value and stores it
 * here; downstream code (logger handler, error pages) can pick it up
 * without threading it through every method signature.
 *
 * Safe as static state because PHP-FPM serves one request per worker
 * process. Tests must call `flush()` between assertions.
 */
final class RequestIdHolder
{
    private static ?string $id = null;

    public static function set(string $id): void
    {
        self::$id = $id;
    }

    public static function get(): ?string
    {
        return self::$id;
    }

    public static function flush(): void
    {
        self::$id = null;
    }
}
