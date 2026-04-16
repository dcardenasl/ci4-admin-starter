<!doctype html>
<html lang="<?= esc($currentLocale ?? 'es') ?>" data-env="<?= esc(ENVIRONMENT) ?>">
<head>
    <?= $this->include('layouts/partials/head') ?>
</head>
<body class="bg-gray-50 font-sans text-gray-900" x-data="appShell()">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-brand-700 focus:shadow">
        <?= lang('App.skip_to_content') ?>
    </a>
    <div class="min-h-screen md:flex">
        <?= $this->include('layouts/partials/sidebar') ?>

        <div class="flex-1 min-w-0">
            <?= $this->include('layouts/partials/navbar') ?>
            <main id="main-content" class="p-4 md:p-6">
                <?= $this->include('layouts/partials/flash_messages') ?>
                <?= $this->include('layouts/partials/toast_messages') ?>
                <?= $this->include($view) ?>
            </main>
        </div>
    </div>

    <?= $this->include('layouts/partials/confirm_modal') ?>
    <script <?= csp_script_nonce() ?> src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
