<?php

if (! function_exists('get_field_error')) {
    function get_field_error(string $field): string
    {
        $fieldErrors = session('fieldErrors');

        if (is_array($fieldErrors) && isset($fieldErrors[$field]) && is_scalar($fieldErrors[$field])) {
            return (string) $fieldErrors[$field];
        }

        return '';
    }
}

if (! function_exists('has_field_error')) {
    function has_field_error(string $field): bool
    {
        return get_field_error($field) !== '';
    }
}

if (! function_exists('field_error_class')) {
    function field_error_class(string $field, string $errorClass = 'border-red-500 focus:border-red-500 focus:ring-red-500'): string
    {
        return has_field_error($field) ? $errorClass : '';
    }
}

if (! function_exists('render_field_error')) {
    function render_field_error(string $field): string
    {
        $message = get_field_error($field);

        if ($message === '') {
            return '';
        }

        return '<p class="mt-1 text-sm text-red-600">' . esc($message) . '</p>';
    }
}
