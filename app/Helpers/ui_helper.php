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

if (! function_exists('role_badge')) {
    function role_badge(?string $role): string
    {
        return strtolower((string) $role) === 'admin'
            ? 'bg-brand-100 text-brand-800'
            : 'bg-gray-100 text-gray-700';
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
            'metrics'   => 'bar-chart-3',
            'reports'   => 'file-text',
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
