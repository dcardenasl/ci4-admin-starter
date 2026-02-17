<?php
$isAuthed = (string) session('access_token') !== '';
$primaryUrl = $isAuthed ? site_url('dashboard') : site_url('login');
$primaryLabel = $isAuthed ? lang('App.goDashboard') : lang('App.goLogin');
?>
<!doctype html>
<html lang="<?= esc(service('language')->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(lang('App.error500Title')) ?></title>
    <?php include __DIR__ . '/partials/error_theme.php'; ?>
</head>
<body>
<main class="error-wrap" data-error-page="500">
    <section class="error-card" role="alert" aria-live="polite">
        <p class="error-code">500</p>
        <h1 class="error-title"><?= esc(lang('App.error500Title')) ?></h1>
        <p class="error-copy"><?= esc(lang('App.error500Body')) ?></p>

        <div class="error-actions">
            <a class="btn btn-primary" href="<?= esc($primaryUrl) ?>"><?= esc($primaryLabel) ?></a>
            <button class="btn btn-secondary" type="button" onclick="history.back()"><?= esc(lang('App.goBack')) ?></button>
        </div>
    </section>
</main>
</body>
</html>
