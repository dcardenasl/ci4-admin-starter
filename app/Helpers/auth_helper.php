<?php

declare(strict_types=1);

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

if (! function_exists('has_permission')) {
    /**
     * Check whether the authenticated user has a specific permission code
     * (e.g. 'iam.admin-access', 'users.write').
     *
     * Reads from `session('user.permissions')`, an array of permission codes
     * populated at login from the API's session response.
     */
    function has_permission(string $code): bool
    {
        $permissions = session('user.permissions');

        if (! is_array($permissions)) {
            return false;
        }

        return in_array($code, $permissions, true);
    }
}

if (! function_exists('is_superadmin')) {
    /**
     * True when the authenticated user holds `iam.superadmin-access`.
     *
     * SuperAdmin bypasses every hierarchical guardrail in the API except
     * self-modification (which is blocked for everyone, on purpose).
     */
    function is_superadmin(): bool
    {
        return has_permission('iam.superadmin-access');
    }
}

if (! function_exists('current_user_id')) {
    function current_user_id(): ?int
    {
        $id = session('user.id');

        return is_numeric($id) ? (int) $id : null;
    }
}

if (! function_exists('is_self')) {
    /**
     * True when the given subject id matches the authenticated user.
     */
    function is_self(int|string|null $subjectId): bool
    {
        if ($subjectId === null) {
            return false;
        }

        return current_user_id() === (int) $subjectId;
    }
}

if (! function_exists('can_act_on_user')) {
    /**
     * UI gating for "modify user X" flows. Returns false when:
     *   - the subject is the current user (self-edit blocked for everyone),
     *   - or the subject is a SuperAdmin and the actor is not.
     *
     * `subjectUser` is the user array returned by the API. If its
     * `permissions` field is not present, this helper falls back to checking
     * a `roles` array for the `superadmin` code — useful while the API still
     * rolls out per-user effective permissions on list endpoints.
     *
     * @param array<string, mixed> $subjectUser
     */
    function can_act_on_user(array $subjectUser): bool
    {
        $subjectId = isset($subjectUser['id']) ? (int) $subjectUser['id'] : null;
        if ($subjectId !== null && is_self($subjectId)) {
            return false;
        }

        if (is_superadmin()) {
            return true;
        }

        $perms = $subjectUser['permissions'] ?? null;
        if (is_array($perms) && in_array('iam.superadmin-access', $perms, true)) {
            return false;
        }

        $roles = $subjectUser['roles'] ?? null;
        if (is_array($roles)) {
            foreach ($roles as $role) {
                $code = is_array($role) ? ($role['code'] ?? null) : $role;
                if ($code === 'superadmin') {
                    return false;
                }
            }
        }

        return true;
    }
}

if (! function_exists('can_modify_role')) {
    /**
     * UI gating for "edit/delete role" buttons. Non-SuperAdmin actors cannot
     * touch roles flagged `is_system=1`.
     *
     * @param array<string, mixed> $role
     */
    function can_modify_role(array $role): bool
    {
        if (is_superadmin()) {
            return true;
        }

        return empty($role['is_system']);
    }
}

if (! function_exists('actor_owns_permission')) {
    /**
     * True when the current actor holds the given permission code (or all of
     * them when `$codes` is an array).
     *
     * Use when building selectors that must hide permissions the actor cannot
     * grant (anti-escalation in UI). The API enforces the same rule
     * authoritatively; this is purely UX.
     *
     * @param string|array<int, string> $codes
     */
    function actor_owns_permission(string|array $codes): bool
    {
        $list = is_array($codes) ? $codes : [$codes];
        foreach ($list as $code) {
            if (! has_permission((string) $code)) {
                return false;
            }
        }

        return true;
    }
}
