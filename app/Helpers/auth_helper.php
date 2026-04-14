<?php

if (! function_exists('is_email_verified')) {
    /**
     * Determine email verification from common API field variants.
     *
     * @param array<string, mixed> $user
     */
    function is_email_verified(array $user): bool
    {
        if (! empty($user['email_verified_at'])) {
            return true;
        }

        foreach (['email_verified', 'is_email_verified', 'verified'] as $key) {
            if (! array_key_exists($key, $user)) {
                continue;
            }

            $value = $user[$key];

            if (is_bool($value)) {
                return $value;
            }

            if (is_int($value) || is_float($value)) {
                return (int) $value === 1;
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));

                if (in_array($normalized, ['1', 'true', 'yes', 'y', 'verified'], true)) {
                    return true;
                }

                if (in_array($normalized, ['0', 'false', 'no', 'n', 'pending', 'unverified'], true)) {
                    return false;
                }
            }
        }

        return false;
    }
}

if (! function_exists('has_admin_access')) {
    function has_admin_access(?string $role): bool
    {
        /** @var \Config\Auth $authConfig */
        $authConfig = config('Auth');
        return in_array(strtolower((string) $role), $authConfig->adminRoles, true);
    }
}
