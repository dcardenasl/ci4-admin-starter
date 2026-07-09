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
        --color-brand-50: rgb(239 246 255);
        --color-brand-100: rgb(219 234 254);
        --color-brand-200: rgb(191 219 254);
        --color-brand-300: rgb(147 197 253);
        --color-brand-400: rgb(96 165 250);
        --color-brand-500: rgb(59 130 246);
        --color-brand-600: rgb(37 99 235);
        --color-brand-700: rgb(29 78 216);
        --color-brand-800: rgb(30 64 175);
        --color-brand-900: rgb(30 58 138);
        --font-sans: "Inter", system-ui, -apple-system, sans-serif;
        --font-mono: "JetBrains Mono", ui-monospace, monospace;
    }
</style>
<?php
// Inject UI labels from lang() files into JavaScript globals
// so labels.js can read from window.uiLabels, statusLabels, etc.
$uiLabelsJson = json_encode([
    'es' => [
        'confirmAction' => lang('App.confirmAction'),
        'confirm' => lang('App.confirm'),
        'requestFailed' => lang('App.requestFailed'),
        'loadRetry' => lang('App.loadRetry'),
    ],
    'en' => [
        'confirmAction' => lang('App.confirmAction'),
        'confirm' => lang('App.confirm'),
        'requestFailed' => lang('App.requestFailed'),
        'loadRetry' => lang('App.loadRetry'),
    ]
]);

$statusLabelsJson = json_encode([
    'es' => [
        'active' => lang('App.status.active'),
        'pending' => lang('App.status.pending'),
        'pending_approval' => lang('App.status.pending_approval'),
        'suspended' => lang('App.status.suspended'),
        'approved' => lang('App.status.approved'),
        'rejected' => lang('App.status.rejected'),
        'processing' => lang('App.status.processing'),
        'success' => lang('App.status.success'),
        'failed' => lang('App.status.failed'),
    ],
    'en' => [
        'active' => lang('App.status.active'),
        'pending' => lang('App.status.pending'),
        'pending_approval' => lang('App.status.pending_approval'),
        'suspended' => lang('App.status.suspended'),
        'approved' => lang('App.status.approved'),
        'rejected' => lang('App.status.rejected'),
        'processing' => lang('App.status.processing'),
        'success' => lang('App.status.success'),
        'failed' => lang('App.status.failed'),
    ]
]);

$auditActionLabelsJson = json_encode([
    'es' => [
        'create' => lang('App.audit.action.create'),
        'update' => lang('App.audit.action.update'),
        'delete' => lang('App.audit.action.delete'),
        'login' => lang('App.audit.action.login'),
        'login_success' => lang('App.audit.action.login_success'),
        'login_failure' => lang('App.audit.action.login_failure'),
        'logout' => lang('App.audit.action.logout'),
        'approve' => lang('App.audit.action.approve'),
    ],
    'en' => [
        'create' => lang('App.audit.action.create'),
        'update' => lang('App.audit.action.update'),
        'delete' => lang('App.audit.action.delete'),
        'login' => lang('App.audit.action.login'),
        'login_success' => lang('App.audit.action.login_success'),
        'login_failure' => lang('App.audit.action.login_failure'),
        'logout' => lang('App.audit.action.logout'),
        'approve' => lang('App.audit.action.approve'),
    ]
]);

$auditResultLabelsJson = json_encode([
    'es' => [
        'success' => lang('App.audit.result.success'),
        'failure' => lang('App.audit.result.failure'),
        'denied' => lang('App.audit.result.denied'),
    ],
    'en' => [
        'success' => lang('App.audit.result.success'),
        'failure' => lang('App.audit.result.failure'),
        'denied' => lang('App.audit.result.denied'),
    ]
]);

$auditSeverityLabelsJson = json_encode([
    'es' => [
        'info' => lang('App.audit.severity.info'),
        'warning' => lang('App.audit.severity.warning'),
        'critical' => lang('App.audit.severity.critical'),
    ],
    'en' => [
        'info' => lang('App.audit.severity.info'),
        'warning' => lang('App.audit.severity.warning'),
        'critical' => lang('App.audit.severity.critical'),
    ]
]);

$paginationLabelsJson = json_encode([
    'es' => [
        'visibleResults' => lang('App.pagination.visibleResults'),
        'showing' => lang('App.pagination.showing'),
        'of' => lang('App.pagination.of'),
    ],
    'en' => [
        'visibleResults' => lang('App.pagination.visibleResults'),
        'showing' => lang('App.pagination.showing'),
        'of' => lang('App.pagination.of'),
    ]
]);
?>
<script <?= csp_script_nonce() ?>>
  window.uiLabels = <?= $uiLabelsJson ?>;
  window.statusLabels = <?= $statusLabelsJson ?>;
  window.auditActionLabels = <?= $auditActionLabelsJson ?>;
  window.auditResultLabels = <?= $auditResultLabelsJson ?>;
  window.auditSeverityLabels = <?= $auditSeverityLabelsJson ?>;
  window.paginationLabels = <?= $paginationLabelsJson ?>;
</script>
<?php // tailwind.config script removed as we now use compiled CSS?>
<?php if (isset($extraHead)) {
    echo $extraHead;
} ?>
