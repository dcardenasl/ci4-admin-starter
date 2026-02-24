<?php

if (! function_exists('active_nav')) {
    function active_nav(string $uri, string $class = 'bg-brand-50 text-brand-700'): string
    {
        return url_is($uri) ? $class : '';
    }
}

if (! function_exists('format_date')) {
    function format_date(mixed $date, string $format = 'd/m/Y H:i'): string
    {
        if (is_array($date)) {
            $date = $date['date'] ?? $date[0] ?? null;
        }

        if (empty($date) || ! is_string($date)) {
            return '-';
        }

        try {
            return (new DateTime($date))->format($format);
        } catch (Throwable) {
            return $date;
        }
    }
}

if (! function_exists('is_email_verified')) {
    /**
     * Determine email verification from common API field variants.
     *
     * @param array<string, mixed> $user
     */
    function is_email_verified(array $user): bool
    {
        if (! empty($user['email_verified_at']) || ! empty($user['verified_at'])) {
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

if (! function_exists('status_badge')) {
    function status_badge(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'active', 'approved', 'success' => 'bg-green-100 text-green-800',
            'pending', 'processing' => 'bg-yellow-100 text-yellow-800',
            'suspended', 'rejected', 'failed' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}

if (! function_exists('localized_status')) {
    function localized_status(?string $status): string
    {
        $raw = (string) $status;
        $status = strtolower($raw);

        return match ($status) {
            'active'           => lang('App.active'),
            'inactive'         => lang('App.inactive'),
            'pending'          => lang('App.pending'),
            'pending_approval' => lang('Users.pendingApproval'),
            'processing'       => lang('App.processing'),
            'approved'         => lang('App.approved'),
            'rejected'         => lang('App.rejected'),
            'suspended'        => lang('App.suspended'),
            'success'          => lang('App.success'),
            'failed'           => lang('App.failed'),
            default            => $raw,
        };
    }
}

if (! function_exists('audit_action_badge')) {
    function audit_action_badge(?string $action): string
    {
        $action = strtolower((string) $action);

        return match ($action) {
            'create'          => 'bg-green-100 text-green-800',
            'update'          => 'bg-blue-100 text-blue-800',
            'delete'          => 'bg-red-100 text-red-800',
            'login'           => 'bg-brand-100 text-brand-800',
            'logout'          => 'bg-gray-100 text-gray-800',
            'approve'         => 'bg-emerald-100 text-emerald-800',
            default           => 'bg-gray-100 text-gray-700',
        };
    }
}

if (! function_exists('localized_audit_action')) {
    function localized_audit_action(?string $action): string
    {
        $raw = (string) $action;
        $action = strtolower($raw);

        return match ($action) {
            'create'  => lang('Audit.actionCreate'),
            'update'  => lang('Audit.actionUpdate'),
            'delete'  => lang('Audit.actionDelete'),
            'login'   => lang('Audit.actionLogin'),
            'logout'  => lang('Audit.actionLogout'),
            'approve' => lang('Audit.actionApprove'),
            default   => $raw,
        };
    }
}

if (! function_exists('has_admin_access')) {
    function has_admin_access(?string $role): bool
    {
        return in_array(strtolower((string) $role), ['admin', 'superadmin'], true);
    }
}

if (! function_exists('role_badge')) {
    function role_badge(?string $role): string
    {
        return has_admin_access($role)
            ? 'bg-brand-100 text-brand-800'
            : 'bg-gray-100 text-gray-700';
    }
}

if (! function_exists('localized_role')) {
    function localized_role(?string $role): string
    {
        $raw = (string) $role;
        $role = strtolower($raw);

        return match ($role) {
            'admin' => lang('Users.adminRole'),
            'superadmin' => lang('Users.superAdminRole'),
            'user'  => lang('Users.userRole'),
            default => $raw,
        };
    }
}

if (! function_exists('filter_label_class')) {
    function filter_label_class(): string
    {
        return 'mb-1 block text-xs font-medium text-gray-600';
    }
}

if (! function_exists('filter_input_class')) {
    function filter_input_class(): string
    {
        return 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200';
    }
}

if (! function_exists('filter_panel_class')) {
    function filter_panel_class(): string
    {
        return 'mt-4 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-4';
    }
}

if (! function_exists('filter_submit_button_class')) {
    function filter_submit_button_class(bool $fullWidth = false): string
    {
        $base = 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500';

        return $fullWidth ? ('w-full ' . $base) : $base;
    }
}

if (! function_exists('query_without_page')) {
    /**
     * @return array<string, mixed>
     */
    function query_without_page(): array
    {
        $query = request()->getGet();

        if (! is_array($query)) {
            return [];
        }

        return array_filter(
            $query,
            static fn($key): bool => $key !== 'page' && $key !== 'cursor',
            ARRAY_FILTER_USE_KEY,
        );
    }
}

if (! function_exists('has_active_filters')) {
    /**
     * Determine whether there are active filter values in a query payload.
     *
     * @param array<string, mixed>|null $query
     * @param array<string, scalar|null> $defaults
     * @param array<int, string> $ignoredKeys
     */
    function has_active_filters(?array $query = null, array $defaults = [], array $ignoredKeys = ['sort', 'page', 'cursor']): bool
    {
        if ($query === null) {
            $currentQuery = request()->getGet();
            $query = is_array($currentQuery) ? $currentQuery : [];
        }

        $ignored = [];
        foreach ($ignoredKeys as $key) {
            if (is_string($key) && $key !== '') {
                $ignored[$key] = true;
            }
        }

        $keys = [];
        foreach (array_keys($defaults) as $key) {
            if (is_string($key) && $key !== '') {
                $keys[$key] = true;
            }
        }
        foreach (array_keys($query) as $key) {
            if (is_string($key) && $key !== '') {
                $keys[$key] = true;
            }
        }

        foreach (array_keys($keys) as $key) {
            if (isset($ignored[$key])) {
                continue;
            }

            $default = array_key_exists($key, $defaults) ? trim((string) $defaults[$key]) : '';
            $current = $default;

            if (array_key_exists($key, $query)) {
                $value = $query[$key];
                if (is_scalar($value) || $value === null) {
                    $current = trim((string) $value);
                } else {
                    continue;
                }
            }

            if ($current !== $default) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('table_wrapper_class')) {
    function table_wrapper_class(): string
    {
        return 'mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ring-1 ring-gray-100';
    }
}

if (! function_exists('table_scroll_class')) {
    function table_scroll_class(): string
    {
        return 'overflow-x-auto';
    }
}

if (! function_exists('table_class')) {
    function table_class(): string
    {
        return 'min-w-full text-sm';
    }
}

if (! function_exists('table_head_class')) {
    function table_head_class(): string
    {
        return 'bg-gradient-to-b from-gray-50 to-gray-100 text-left text-gray-500';
    }
}

if (! function_exists('table_th_class')) {
    function table_th_class(): string
    {
        return 'py-3.5 px-4 text-[11px] font-bold uppercase tracking-wider';
    }
}

if (! function_exists('table_body_class')) {
    function table_body_class(): string
    {
        return 'divide-y divide-gray-100';
    }
}

if (! function_exists('table_td_class')) {
    function table_td_class(string $tone = 'default'): string
    {
        $base = 'py-3.5 px-4 align-middle';

        return match ($tone) {
            'primary' => $base . ' text-gray-800 font-medium',
            'muted'   => $base . ' text-gray-600',
            'subtle'  => $base . ' text-gray-500',
            default   => $base . ' text-gray-700',
        };
    }
}

if (! function_exists('table_row_class')) {
    function table_row_class(): string
    {
        return 'odd:bg-white even:bg-gray-50/45 hover:bg-brand-50/40 transition-colors';
    }
}

if (! function_exists('action_button_class')) {
    function action_button_class(string $variant = 'neutral'): string
    {
        $base = 'inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold shadow-sm transition focus:outline-none focus:ring-2';

        return match ($variant) {
            'primary' => $base . ' bg-brand-600 text-white hover:bg-brand-700 focus:ring-brand-500',
            'danger'  => $base . ' bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            default   => $base . ' border border-gray-200 bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-brand-500',
        };
    }
}

if (! function_exists('ui_icon')) {
    function ui_icon(string $name, string $class = 'h-4 w-4'): string
    {
        $icons = [
            'dashboard' => 'layout-dashboard',
            'profile'   => 'user-round',
            'files'     => 'files',
            'users'     => 'users',
            'audit'     => 'clipboard-list',
            'apiKeys'   => 'key-round',
            'metrics'   => 'bar-chart-3',
            'search'    => 'search',
            'plus'      => 'plus',
            'eye'       => 'eye',
            'edit'      => 'pencil',
            'download'  => 'download',
            'trash'     => 'trash-2',
        ];

        $icon = $icons[$name] ?? $icons['search'];

        return '<i data-lucide="' . esc($icon) . '" class="' . esc($class) . '" aria-hidden="true"></i>';
    }
}
