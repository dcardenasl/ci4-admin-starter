<!doctype html>
<html lang="es">
<head>
    <?= $this->include('layouts/partials/head') ?>
</head>
<body class="bg-gray-50 font-sans text-gray-900">
    <main class="min-h-screen flex items-center justify-center px-4 py-10">
        <section class="w-full max-w-md bg-white border border-gray-200 rounded-xl shadow-sm p-6 md:p-8">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold"><?= esc($appName ?? 'API Client') ?></h1>
                <?php if (! empty($subtitle)): ?>
                    <p class="mt-1 text-sm text-gray-500"><?= esc($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <?= $this->include('layouts/partials/flash_messages') ?>
            <?= $this->include($view) ?>
        </section>
    </main>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
