<?php

declare(strict_types=1);

if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

foreach ([
    'APPPATH' => __DIR__ . '/app/',
    'ROOTPATH' => __DIR__ . '/',
    'WRITEPATH' => __DIR__ . '/writable/',
    'FCPATH' => __DIR__ . '/public/',
    'SYSTEMPATH' => __DIR__ . '/system/',
] as $constant => $value) {
    if (! defined($constant)) {
        define($constant, $value);
    }
}

if (! function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $default;
    }
}

if (! function_exists('service')) {
    function service(string $name, ...$params)
    {
        return null;
    }
}

if (! function_exists('config')) {
    function config(?string $name = null)
    {
        return null;
    }
}

if (! function_exists('lang')) {
    function lang(string $line, array $args = [], ?string $locale = null)
    {
        return $line;
    }
}

if (! function_exists('redirect')) {
    function redirect()
    {
        return null;
    }
}

if (! function_exists('site_url')) {
    function site_url(string $uri = '', ?string $protocol = null, ?\Config\App $altConfig = null): string
    {
        return $uri;
    }
}

if (! function_exists('route_to')) {
    function route_to(string $routeName, ...$params): string
    {
        return '';
    }
}

if (! function_exists('session')) {
    function session(?string $val = null)
    {
        return null;
    }
}

if (! function_exists('request')) {
    function request()
    {
        return null;
    }
}

if (! function_exists('url_is')) {
    function url_is(string $path): bool
    {
        return false;
    }
}

if (! function_exists('esc')) {
    function esc($data, string $context = 'html', ?string $encoding = null)
    {
        return $data;
    }
}

if (! function_exists('is_cli')) {
    function is_cli(): bool
    {
        return false;
    }
}

if (! function_exists('log_message')) {
    function log_message(string $level, string $message, array $context = []): void
    {
    }
}

if (! function_exists('helper')) {
    function helper($filenames = []): void
    {
    }
}

if (! function_exists('view')) {
    function view(string $name, array $data = [], array $options = []): string
    {
        return $name;
    }
}
