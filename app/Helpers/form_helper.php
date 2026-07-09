<?php

declare(strict_types=1);

if (! function_exists('get_field_error')) {
    /**
     * @return list<string>
     */
    function normalize_field_error_keys(string $field): array
    {
        $keys = [$field];

        $dotKey = preg_replace('/\[(.*?)\]/', '.$1', $field);
        if (is_string($dotKey)) {
            $dotKey = trim(preg_replace('/\.+/', '.', $dotKey) ?? $dotKey, '.');
            if ($dotKey !== '' && $dotKey !== $field) {
                $keys[] = $dotKey;
            }
        }

        $normalizedDot = $dotKey ?? $field;
        if ($normalizedDot !== '') {
            $parts = explode('.', $normalizedDot);
            $bracketKey = array_shift($parts);
            foreach ($parts as $part) {
                $bracketKey .= '[' . $part . ']';
            }

            if ($bracketKey !== '' && $bracketKey !== $field) {
                $keys[] = $bracketKey;
            }
        }

        return array_values(array_unique(array_filter($keys, static fn (string $key): bool => $key !== '')));
    }

    function get_field_error(string $field): string
    {
        $fieldErrors = session('fieldErrors');

        if (is_array($fieldErrors) && isset($fieldErrors[$field]) && is_scalar($fieldErrors[$field])) {
            return (string) $fieldErrors[$field];
        }

        if (is_array($fieldErrors)) {
            foreach (normalize_field_error_keys($field) as $candidate) {
                if ($candidate === $field) {
                    continue;
                }

                if (isset($fieldErrors[$candidate]) && is_scalar($fieldErrors[$candidate])) {
                    return (string) $fieldErrors[$candidate];
                }
            }
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

if (! function_exists('input_class')) {
    function input_class(string $field): string
    {
        $base   = 'mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2';
        $normal = 'border-gray-300 focus:border-brand-500 focus:ring-brand-500';
        $error  = 'border-red-500 focus:border-red-500 focus:ring-red-500';

        return $base . ' ' . (has_field_error($field) ? $error : $normal);
    }
}

if (! function_exists('field_error_id')) {
    /**
     * Stable DOM id for the error message span tied to a form field.
     * View authors use it via `aria-describedby` so screen readers
     * announce the error when the input gets focus (audit B8.4).
     */
    function field_error_id(string $field): string
    {
        // Replace anything not an HTML id-safe character with `-`.
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $field) ?? $field;

        return 'field-error-' . trim((string) $safe, '-');
    }
}

if (! function_exists('field_aria_attrs')) {
    /**
     * Emit ARIA attributes for a form input. Audit B8.4 (2026-05-06):
     * meets WCAG 2.1 AA expectations for form error announcement.
     *
     * Returns a pre-escaped attribute string suitable for direct echo:
     *
     *   <input ... <?= field_aria_attrs('email', required: true) ?>>
     *
     * Emits:
     *   - `aria-invalid="true"` only when the field has a stored error.
     *     (Omitting it on a clean field is preferable to `aria-invalid="false"`,
     *      because some screen readers verbalize the latter on every focus.)
     *   - `aria-describedby="field-error-<safe>"` only when there's an error
     *     to point at.
     *   - `aria-required="true"` when the caller asserts the field is required.
     */
    function field_aria_attrs(string $field, bool $required = false): string
    {
        $attrs = [];

        if (has_field_error($field)) {
            $attrs[] = 'aria-invalid="true"';
            $attrs[] = 'aria-describedby="' . esc(field_error_id($field), 'attr') . '"';
        }

        if ($required) {
            $attrs[] = 'aria-required="true"';
        }

        return implode(' ', $attrs);
    }
}

if (! function_exists('render_field_error')) {
    function render_field_error(string $field): string
    {
        $message = get_field_error($field);

        if ($message === '') {
            return '';
        }

        // `id` lets the input reference us via aria-describedby.
        // `role="alert"` makes screen readers announce dynamically when
        // the message appears after a server round-trip.
        $id = field_error_id($field);

        return '<p id="' . esc($id, 'attr') . '" role="alert" class="mt-1 text-sm text-red-600">'
            . esc($message)
            . '</p>';
    }
}

if (! function_exists('render_extra_attrs')) {
    /**
     * Render additional HTML attributes passed by form components.
     *
     * @param array<string, scalar|null> $attributes
     */
    function render_extra_attrs(array $attributes = []): string
    {
        $rendered = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $rendered[] = esc((string) $name, 'attr');
                continue;
            }

            $rendered[] = esc((string) $name, 'attr') . '="' . esc((string) $value, 'attr') . '"';
        }

        return implode(' ', $rendered);
    }
}
