<?php
$appName ??= config('App')->appName;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title ?? $appName) ?></title>
<?php if (!empty($sessionExpiresAt ?? null)): ?>
<meta name="session-expires-at" content="<?= esc((string) (int) $sessionExpiresAt) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= asset_url('assets/css/app.css') ?>">
<?php
// Alpine and Lucide are vendored locally via `npm run build:vendor` so the
// admin doesn't depend on jsdelivr at runtime (no external POF, no tracking
// surface). When the vendored files are missing — e.g. someone forgot the
// build step on a fresh clone — fall back to the pinned CDN URLs so the
// page still works in development. Vendored copies are cache-busted via
// `asset_url()`; CDN URLs already pin a version (audit B8.1).
$alpineLocal = file_exists(FCPATH . 'assets/vendor/alpine.min.js');
$lucideLocal = file_exists(FCPATH . 'assets/vendor/lucide.min.js');
?>
<?php if ($alpineLocal): ?>
<script defer src="<?= asset_url('assets/vendor/alpine.min.js') ?>"></script>
<?php else: ?>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" integrity="sha384-9Ax3MmS9AClxJyd5/zafcXXjxmwFhZCdsT6HJoJjarvCaAkJlk5QDzjLJm+Wdx5F" crossorigin="anonymous"></script>
<?php endif; ?>
<?php if ($lucideLocal): ?>
<script defer src="<?= asset_url('assets/vendor/lucide.min.js') ?>"></script>
<?php else: ?>
<script defer src="https://cdn.jsdelivr.net/npm/lucide@0.539.0/dist/umd/lucide.min.js" integrity="sha384-Ui80VKnKTTUky8NmDUdXcnOrP66fD6bYHb7J1+kL+Zx517BmW5a6kvGDwY3BKt+w" crossorigin="anonymous"></script>
<?php endif; ?>
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
<?php // tailwind.config script removed as we now use compiled CSS?>
<?php if (isset($extraHead)) {
    echo $extraHead;
} ?>
