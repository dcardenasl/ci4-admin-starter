<header class="mb-8">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= sprintf(lang('Dashboard.welcome_title'), esc($user['first_name'] ?? $user['username'] ?? 'User')) ?></h1>
            <p class="text-gray-500 mt-1">
                <?= lang('Dashboard.welcome_subtitle') ?>
                <a href="<?= route_to('profile') ?>" class="inline-flex items-center gap-1 text-brand-600 hover:text-brand-700 font-medium ml-1 transition-colors">
                    <?= ui_icon('edit', 'h-3.5 w-3.5') ?>
                    <?= lang('Dashboard.edit_profile') ?>
                </a>
            </p>
        </div>
    </div>
</header>

<!-- ZONA 1: Stats Principales -->
<div
    x-data="{ loaded: false, error: false }"
    x-init="
        fetch('<?= route_to('dashboard.widgets.stats') ?>')
            .then(r => r.ok ? r.text() : Promise.reject())
            .then(h => {
                $refs.statsContent.innerHTML = h;
                window.lucide && window.lucide.createIcons({ attrs: { 'stroke-width': 1.8 } });
                loaded = true;
            })
            .catch(() => { error = true; loaded = true; })
    "
>
    <!-- Skeleton -->
    <section x-show="!loaded" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php for ($i = 0; $i < 2; $i++): ?>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center gap-4 animate-pulse">
            <div class="flex-shrink-0 h-12 w-12 bg-gray-100 rounded-lg"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3 bg-gray-200 rounded w-24"></div>
                <div class="h-7 bg-gray-200 rounded w-16"></div>
            </div>
        </article>
        <?php endfor; ?>
    </section>
    <!-- Content -->
    <section x-ref="statsContent" x-show="loaded && !error" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" x-cloak></section>
</div>

<div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">

    <!-- ZONA 2: Área Principal (2/3) -->
    <div class="xl:col-span-2 space-y-6">
        <!-- Tabla de Archivos Recientes -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-900"><?= lang('Dashboard.latest_files') ?></h3>
                <a href="<?= route_to('files') ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700"><?= lang('Dashboard.manage_files') ?> &rarr;</a>
            </div>

            <div
                x-data="{ loaded: false, error: false }"
                x-init="
                    fetch('<?= route_to('dashboard.widgets.recent-files') ?>')
                        .then(r => r.ok ? r.text() : Promise.reject())
                        .then(h => {
                            $refs.filesContent.innerHTML = h;
                            window.lucide && window.lucide.createIcons({ attrs: { 'stroke-width': 1.8 } });
                            loaded = true;
                        })
                        .catch(() => { error = true; loaded = true; })
                "
            >
                <!-- Skeleton -->
                <div x-show="!loaded" class="space-y-3">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="flex items-center gap-3 animate-pulse">
                        <div class="h-10 w-10 bg-gray-200 rounded-lg flex-shrink-0"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                        </div>
                        <div class="h-4 bg-gray-200 rounded w-16"></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <!-- Content -->
                <div x-ref="filesContent" x-show="loaded && !error" x-cloak></div>
                <!-- Error -->
                <div x-show="loaded && error" class="py-6 text-center text-sm text-gray-400" x-cloak>
                    <?= ui_icon('triangle-alert', 'h-5 w-5 mx-auto mb-1 text-gray-300') ?>
                    <?= lang('App.connection_error') ?>
                </div>
            </div>
        </section>
    </div>

    <!-- ZONA 3: Sidebar (1/3) -->
    <div class="space-y-6">

        <!-- Widget: Service Health -->
        <div
            x-data="{ loaded: false, error: false }"
            x-init="
                fetch('<?= route_to('dashboard.widgets.health') ?>')
                    .then(r => r.ok ? r.text() : Promise.reject())
                    .then(h => {
                        $refs.healthContent.innerHTML = h;
                        window.lucide && window.lucide.createIcons({ attrs: { 'stroke-width': 1.8 } });
                        loaded = true;
                    })
                    .catch(() => { error = true; loaded = true; })
            "
        >
            <!-- Skeleton -->
            <section x-show="!loaded" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 animate-pulse">
                <div class="flex items-center justify-between mb-4">
                    <div class="h-3 bg-gray-200 rounded w-28"></div>
                    <div class="h-3 bg-gray-200 rounded w-12"></div>
                </div>
                <div class="h-11 bg-gray-100 rounded-lg"></div>
                <div class="mt-4 space-y-2 border-t border-gray-100 pt-4">
                    <div class="h-5 bg-gray-100 rounded"></div>
                    <div class="h-5 bg-gray-100 rounded"></div>
                    <div class="h-5 bg-gray-100 rounded"></div>
                </div>
            </section>
            <!-- Content -->
            <div x-ref="healthContent" x-show="loaded && !error" x-cloak></div>
        </div>

        <!-- Widget: Recent Activity -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-6"><?= lang('Dashboard.recent_activity') ?></h3>
            <div class="flow-root">
                <div
                    x-data="{ loaded: false, error: false }"
                    x-init="
                        fetch('<?= route_to('dashboard.widgets.activity') ?>')
                            .then(r => r.ok ? r.text() : Promise.reject())
                            .then(h => {
                                $refs.activityContent.innerHTML = h;
                                window.lucide && window.lucide.createIcons({ attrs: { 'stroke-width': 1.8 } });
                                loaded = true;
                            })
                            .catch(() => { error = true; loaded = true; })
                    "
                >
                    <!-- Skeleton -->
                    <div x-show="!loaded" class="space-y-4">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="flex gap-3 animate-pulse">
                            <div class="h-8 w-8 bg-gray-200 rounded-full flex-shrink-0"></div>
                            <div class="flex-1 space-y-1.5 pt-1">
                                <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-3 bg-gray-200 rounded w-1/4"></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <!-- Content -->
                    <div x-ref="activityContent" x-show="loaded && !error" x-cloak></div>
                    <!-- Error -->
                    <div x-show="loaded && error" class="py-4 text-center text-sm text-gray-400" x-cloak>
                        <?= lang('App.connection_error') ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Widget: Quick Start -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-2"><?= lang('Dashboard.quick_start') ?></h3>
            <p class="text-xs text-gray-500 mb-4"><?= lang('Dashboard.quick_start_desc') ?></p>
            <div class="grid grid-cols-2 gap-2">
                <?php if (has_permission('users.read')): ?>
                    <a href="<?= route_to('admin.users') ?>" class="flex items-center justify-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <?= ui_icon('users', 'h-3.5 w-3.5 text-gray-400') ?>
                        <?= lang('Dashboard.users') ?>
                    </a>
                <?php endif; ?>
                <a href="<?= route_to('files') ?>" class="flex items-center justify-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <?= ui_icon('files', 'h-3.5 w-3.5 text-gray-400') ?>
                    <?= lang('Dashboard.files') ?>
                </a>
            </div>
        </section>
    </div>
</div>
