<?php
$appName ??= 'API Client';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title ?? $appName) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" integrity="sha384-9Ax3MmS9AClxJyd5/zafcXXjxmwFhZCdsT6HJoJjarvCaAkJlk5QDzjLJm+Wdx5F" crossorigin="anonymous"></script>
<script defer src="https://cdn.jsdelivr.net/npm/lucide@0.539.0/dist/umd/lucide.min.js" integrity="sha384-Ui80VKnKTTUky8NmDUdXcnOrP66fD6bYHb7J1+kL+Zx517BmW5a6kvGDwY3BKt+w" crossorigin="anonymous"></script>
<style <?= csp_style_nonce() ?>>
    [x-cloak] {
        display: none !important;
    }

    :root {
        --color-brand-50: 239 246 255;
        --color-brand-100: 219 234 254;
        --color-brand-200: 191 219 254;
        --color-brand-300: 147 197 253;
        --color-brand-400: 96 165 250;
        --color-brand-500: 59 130 246;
        --color-brand-600: 37 99 235;
        --color-brand-700: 29 78 216;
        --color-brand-800: 30 64 175;
        --color-brand-900: 30 58 138;
        --font-sans: "Inter", system-ui, -apple-system, sans-serif;
        --font-mono: "JetBrains Mono", ui-monospace, monospace;
    }
</style>
