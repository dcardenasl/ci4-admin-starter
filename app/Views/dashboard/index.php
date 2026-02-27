<header class="mb-8">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= sprintf(lang('Dashboard.welcomeTitle'), esc($user['firstName'] ?? $user['username'] ?? 'User')) ?></h1>
            <p class="text-gray-500 mt-1">
                <?= lang('Dashboard.welcomeSubtitle') ?> 
                <a href="<?= site_url('profile') ?>" class="inline-flex items-center gap-1 text-brand-600 hover:text-brand-700 font-medium ml-1 transition-colors">
                    <?= ui_icon('edit', 'h-3.5 w-3.5') ?>
                    <?= lang('Dashboard.editProfile') ?>
                </a>
            </p>
        </div>
    </div>
</header>

<!-- ZONA 1: Stats Principales -->
<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ($stats as $stat): ?>
        <?= view('dashboard/partials/stat_card', [
            'label'  => $stat['label'],
            'value'  => $stat['value'],
            'icon'   => $stat['icon'],
            'suffix' => $stat['suffix'] ?? null,
        ]) ?>
    <?php endforeach; ?>
</section>

<div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
    
    <!-- ZONA 2: Área Principal (2/3) -->
    <div class="xl:col-span-2 space-y-6">
        <!-- Tabla de Archivos Recientes -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-900"><?= lang('Dashboard.latestFiles') ?></h3>
                <a href="<?= site_url('files') ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700"><?= lang('Dashboard.manageFiles') ?> &rarr;</a>
            </div>

            <div x-data="{ previewShow: false, previewUrl: '' }">
                <?php if (empty($recentFiles)): ?>
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <?= ui_icon('file-plus', 'h-12 w-12') ?>
                        </div>
                        <p class="mt-2 text-sm text-gray-600"><?= lang('Dashboard.noRecentFiles') ?></p>
                        <a href="<?= site_url('files') ?>" class="mt-4 inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                            <?= lang('Dashboard.manageFiles') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="<?= esc(table_wrapper_class()) ?>">
                        <div class="<?= esc(table_scroll_class()) ?>">
                            <table class="<?= esc(table_class()) ?>">
                                <thead class="<?= esc(table_head_class()) ?>">
                                    <tr>
                                        <th class="<?= esc(table_th_class()) ?> w-16"><?= lang('App.preview') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Files.fileName') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Files.status') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Files.date') ?></th>
                                    </tr>
                                </thead>
                                <tbody class="<?= esc(table_body_class()) ?>">
                                    <?php foreach ($recentFiles as $file): ?>
                                        <tr class="<?= esc(table_row_class()) ?>">
                                            <td class="<?= esc(table_td_class()) ?>">
                                                <?php if (! empty($file['isImage'])): ?>
                                                    <?php $viewUrl = site_url('files/' . ($file['id'] ?? '') . '/view'); ?>
                                                    <button type="button" @click="previewUrl = '<?= $viewUrl ?>'; previewShow = true">
                                                        <img src="<?= $viewUrl ?>" 
                                                             class="h-8 w-8 rounded-lg object-cover border border-gray-200 hover:scale-110 transition-transform shadow-sm" 
                                                             alt="<?= esc((string) ($file['originalName'] ?? '')) ?>">
                                                    </button>
                                                <?php else: ?>
                                                    <div class="h-8 w-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200">
                                                        <?= ui_icon('file', 'h-4 w-4 text-gray-400') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="<?= esc(table_td_class('primary')) ?>">
                                                <?= esc((string) ($file['originalName'] ?? $file['filename'] ?? '-')) ?>
                                            </td>
                                            <td class="<?= esc(table_td_class()) ?>">
                                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge($file['status'] ?? 'active') ?>">
                                                    <?= esc(localized_status((string) ($file['status'] ?? 'active'))) ?>
                                                </span>
                                            </td>
                                            <td class="<?= esc(table_td_class('muted')) ?>">
                                                <?= esc(format_date($file['uploadedAt'] ?? null)) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Lightbox Modal -->
                <div x-show="previewShow" @keydown.escape.window="previewShow = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click="previewShow = false" style="display: none;">
                    <div class="relative max-h-full max-w-full" @click.stop>
                        <button type="button" @click="previewShow = false" class="absolute -top-12 right-0 p-2 text-white hover:text-gray-300"><?= ui_icon('x', 'h-8 w-8') ?></button>
                        <img :src="previewUrl" class="max-h-[85vh] max-w-[90vw] rounded-lg shadow-2xl object-contain border border-white/10">
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- ZONA 3: Sidebar (1/3) -->
    <div class="space-y-6">
        <!-- Widget: API Health -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4"><?= lang('Dashboard.systemStatus') ?></h3>
            <?php
            $healthState = $apiHealth['state'] ?? 'down';
            $healthTone = match ($healthState) {
                'up' => ['dot' => 'bg-green-500', 'text' => 'text-green-700', 'bg' => 'bg-green-50'],
                'degraded' => ['dot' => 'bg-amber-500', 'text' => 'text-amber-700', 'bg' => 'bg-amber-50'],
                default => ['dot' => 'bg-red-500', 'text' => 'text-red-700', 'bg' => 'bg-red-50'],
            };
            ?>
            <div class="flex items-center gap-3 p-3 rounded-lg <?= esc($healthTone['bg']) ?>">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?= esc($healthTone['dot']) ?> opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 <?= esc($healthTone['dot']) ?>"></span>
                </span>
                <span class="text-sm font-medium <?= esc($healthTone['text']) ?>">
                    API: <?= esc((string) ($apiHealth['state'] ?? $apiHealth['status'] ?? 'unknown')) ?> 
                    (<?= esc((string) ($apiHealth['latencyMs'] ?? $apiHealth['latency_ms'] ?? 0)) ?>ms)
                </span>
            </div>
        </section>

        <!-- Widget: Recent Activity -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-6"><?= lang('Dashboard.recentActivity') ?></h3>
            <div class="flow-root">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-sm text-gray-500 text-center py-4 italic">No recent activity detected.</p>
                <?php else: ?>
                    <ul role="list" class="-mb-8">
                        <?php foreach ($recentActivity as $index => $item): ?>
                            <?= view('dashboard/partials/activity_item', [
                                'item' => $item,
                                'isLast' => $index === count($recentActivity) - 1,
                            ]) ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <!-- Widget: Quick Start -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-2"><?= lang('Dashboard.quickStart') ?></h3>
            <p class="text-xs text-gray-500 mb-4"><?= lang('Dashboard.quickStartDesc') ?></p>
            <div class="grid grid-cols-2 gap-2">
                <?php if (has_admin_access((string) (session('user.role') ?? ''))): ?>
                    <a href="<?= site_url('admin/users') ?>" class="flex items-center justify-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <?= ui_icon('users', 'h-3.5 w-3.5 text-gray-400') ?>
                        <?= lang('Dashboard.users') ?>
                    </a>
                <?php endif; ?>
                <a href="<?= site_url('files') ?>" class="flex items-center justify-center gap-2 p-2 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <?= ui_icon('files', 'h-3.5 w-3.5 text-gray-400') ?>
                    <?= lang('Dashboard.files') ?>
                </a>
            </div>
        </section>
    </div>
</div>