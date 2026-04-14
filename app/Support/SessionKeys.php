<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Session Key Constants
 *
 * Centralizes session key strings used for authentication state.
 * Prevents magic strings scattered throughout the codebase.
 *
 * @deprecated 2.0.0 Modernize to PHP 8.1 enum when refactoring session handling.
 *             Currently uses class constants for compatibility. Change to:
 *             enum SessionKeys: string { case ACCESS_TOKEN = 'access_token'; ... }
 */
final class SessionKeys
{
    public const ACCESS_TOKEN = 'access_token';
    public const REFRESH_TOKEN = 'refresh_token';
    public const EXPIRES_AT = 'token_expires_at';
    public const USER = 'user';
    public const LOCALE = 'locale';

    private function __construct()
    {
        // This class should not be instantiated
    }
}
