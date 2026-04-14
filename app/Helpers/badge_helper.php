<?php

if (! function_exists('status_badge')) {
    function status_badge(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'active', 'approved', 'success' => 'bg-green-100 text-green-800',
            'pending', 'pending_approval', 'processing' => 'bg-yellow-100 text-yellow-800',
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
            'active'           => lang('App.yes'),
            'inactive'         => lang('App.no'),
            'pending'          => lang('App.pending'),
            'pending_approval' => lang('Users.pending_approval'),
            'invited'          => lang('Users.invited'),
            'processing'       => lang('App.info'),
            'approved'         => lang('App.success'),
            'rejected'         => lang('App.error'),
            'suspended'        => lang('App.warning'),
            'success'          => lang('App.success'),
            'failed'           => lang('App.error'),
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
            'login', 'login_success' => 'bg-brand-100 text-brand-800',
            'login_failure'   => 'bg-red-100 text-red-800',
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
            'create'  => lang('Audit.action_create'),
            'update'  => lang('Audit.action_update'),
            'delete'  => lang('Audit.action_delete'),
            'login'   => lang('Audit.action_login'),
            'logout'  => lang('Audit.action_logout'),
            'approve' => lang('Audit.action_approve'),
            'login_success' => lang('Audit.action_login_success'),
            'login_failure' => lang('Audit.action_login_failure'),
            default   => $raw,
        };
    }
}

if (! function_exists('audit_result_badge')) {
    function audit_result_badge(?string $result): string
    {
        $result = strtolower((string) $result);

        return match ($result) {
            'success' => 'bg-green-100 text-green-800',
            'failure' => 'bg-red-100 text-red-800',
            'denied'  => 'bg-orange-100 text-orange-800',
            default   => 'bg-gray-100 text-gray-700',
        };
    }
}

if (! function_exists('localized_audit_result')) {
    function localized_audit_result(?string $result): string
    {
        $raw = (string) $result;
        $result = strtolower($raw);

        return match ($result) {
            'success' => lang('Audit.result_success'),
            'failure' => lang('Audit.result_failure'),
            'denied'  => lang('Audit.result_denied'),
            default   => $raw,
        };
    }
}

if (! function_exists('audit_severity_badge')) {
    function audit_severity_badge(?string $severity): string
    {
        $severity = strtolower((string) $severity);

        return match ($severity) {
            'info'     => 'bg-blue-50 text-blue-700 border border-blue-200',
            'warning'  => 'bg-amber-50 text-amber-700 border border-amber-200',
            'critical' => 'bg-red-100 text-red-700 border border-red-300 font-bold',
            default    => 'bg-gray-100 text-gray-600 border border-gray-200',
        };
    }
}

if (! function_exists('localized_audit_severity')) {
    function localized_audit_severity(?string $severity): string
    {
        $raw = (string) $severity;
        $severity = strtolower($raw);

        return match ($severity) {
            'info'     => lang('Audit.severity_info'),
            'warning'  => lang('Audit.severity_warning'),
            'critical' => lang('Audit.severity_critical'),
            default    => $raw,
        };
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
            'admin' => lang('Users.admin_role'),
            'superadmin' => lang('Users.super_admin_role'),
            'user'  => lang('Users.user_role'),
            default => $raw,
        };
    }
}
