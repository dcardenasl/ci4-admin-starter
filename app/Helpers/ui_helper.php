<?php

if (! function_exists('active_nav')) {
    function active_nav(string $uri, string $class = 'bg-brand-50 text-brand-700'): string
    {
        return url_is($uri) ? $class : '';
    }
}

if (! function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd/m/Y H:i'): string
    {
        if (empty($date)) {
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

if (! function_exists('role_badge')) {
    function role_badge(?string $role): string
    {
        return strtolower((string) $role) === 'admin'
            ? 'bg-brand-100 text-brand-800'
            : 'bg-gray-100 text-gray-700';
    }
}
