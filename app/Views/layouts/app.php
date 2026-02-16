<!doctype html>
<html lang="es">
<head>
    <?= $this->include('layouts/partials/head') ?>
</head>
<body class="bg-gray-50 font-sans text-gray-900" x-data="appShell()">
    <div class="min-h-screen md:flex">
        <?= $this->include('layouts/partials/sidebar') ?>

        <div class="flex-1 min-w-0">
            <?= $this->include('layouts/partials/navbar') ?>
            <main class="p-4 md:p-6">
                <?= $this->include('layouts/partials/flash_messages') ?>
                <?= $this->include($view) ?>
            </main>
        </div>
    </div>

    <?= $this->include('layouts/partials/confirm_modal') ?>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
