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
                <h3 class="text-lg font-semibold text-gray-900"><?= lang('Dashboard.latest_files') ?></h3>
                <a href="<?= route_to('files') ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700"><?= lang('Dashboard.manage_files') ?> &rarr;</a>
            </div>

            <div x-data="{ previewShow: false, previewUrl: '' }">
                <?php if (empty($recentFiles)): ?>
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <?= ui_icon('file-plus', 'h-12 w-12') ?>
                        </div>
                        <p class="mt-2 text-sm text-gray-600"><?= lang('Dashboard.no_recent_files') ?></p>
                        <a href="<?= route_to('files') ?>" class="mt-4 inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                            <?= lang('Dashboard.manage_files') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="<?= esc(table_wrapper_class()) ?>">
                        <div class="<?= esc(table_scroll_class()) ?>">
                            <table class="<?= esc(table_class()) ?>">
                                <thead class="<?= esc(table_head_class()) ?>">
                                    <tr>
                                        <th class="<?= esc(table_th_class()) ?> w-16"><?= lang('TableColumns.preview') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.file_name') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.category') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.size') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.date') ?></th>
                                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody class="<?= esc(table_body_class()) ?>">
                                    <?php foreach ($recentFiles as $file): ?>
                                        <?php
                                        $fileId   = $file['id'] ?? '';
                                        $isImage  = (bool) ($file['is_image'] ?? false);
                                        $thumbUrl = $file['variants']['sm']['url'] ?? ($isImage ? route_to('files.view', $fileId) : null);
                                        $largeUrl = $file['variants']['lg']['url'] ?? ($isImage ? route_to('files.view', $fileId) : null);
                                        ?>
                                        <tr class="<?= esc(table_row_class()) ?>">
                                            <td class="<?= esc(table_td_class()) ?>">
                                                <?php if ($thumbUrl !== null): ?>
                                                    <button type="button" @click="previewUrl = '<?= esc($largeUrl ?? $thumbUrl) ?>'; previewShow = true">
                                                        <img src="<?= esc($thumbUrl) ?>"
                                                             class="h-10 w-10 rounded-lg object-cover border border-gray-200 hover:scale-110 transition-transform shadow-sm"
                                                             alt="<?= esc((string) ($file['original_name'] ?? '')) ?>">
                                                    </button>
                                                <?php else: ?>
                                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200">
                                                        <?= ui_icon('file', 'h-5 w-5 text-gray-400') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="<?= esc(table_td_class('primary')) ?>">
                                                <?= esc((string) ($file['original_name'] ?? $file['filename'] ?? '-')) ?>
                                            </td>
                                            <td class="<?= esc(table_td_class('subtle')) ?> text-xs uppercase">
                                                <?= esc((string) ($file['category'] ?? '-')) ?>
                                            </td>
                                            <td class="<?= esc(table_td_class('muted')) ?>">
                                                <?= esc((string) ($file['human_size'] ?? '-')) ?>
                                            </td>
                                            <td class="<?= esc(table_td_class('muted')) ?>">
                                                <?= esc(format_date($file['uploaded_at'] ?? null)) ?>
                                            </td>
                                            <td class="<?= esc(table_td_class()) ?>">
                                                <div class="flex items-center gap-2">
                                                    <a href="<?= route_to('files.show', $fileId) ?>" class="<?= esc(action_button_class()) ?>" title="<?= esc(lang('App.view')) ?>">
                                                        <?= ui_icon('eye', 'h-3.5 w-3.5') ?>
                                                        <span class="hidden md:inline"><?= lang('App.view') ?></span>
                                                    </a>
                                                    <a href="<?= route_to('files.download', $fileId) ?>" class="<?= esc(action_button_class()) ?>" title="<?= esc(lang('App.download')) ?>">
                                                        <?= ui_icon('download', 'h-3.5 w-3.5') ?>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Lightbox Modal -->
                <div x-show="previewShow" x-cloak @keydown.escape.window="previewShow = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click="previewShow = false">
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
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4"><?= lang('Dashboard.system_status') ?></h3>
            <?php
            $healthTone = health_tone_badge($apiHealth['state'] ?? 'down');
            $healthData = is_array($apiHealth['data'] ?? null) ? $apiHealth['data'] : [];
            $healthChecks = is_array($healthData['checks'] ?? null) ? $healthData['checks'] : [];
            $healthTimestamp = $healthData['timestamp'] ?? null;
            $dbCheck = is_array($healthChecks['database'] ?? null) ? $healthChecks['database'] : null;
            $diskCheck = is_array($healthChecks['disk'] ?? null) ? $healthChecks['disk'] : null;
            $writableCheck = is_array($healthChecks['writable'] ?? null) ? $healthChecks['writable'] : null;
            ?>

            <!-- Estado general -->
            <div class="flex items-center gap-3 p-3 rounded-lg <?= esc($healthTone['bg']) ?>">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?= esc($healthTone['dot']) ?> opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 <?= esc($healthTone['dot']) ?>"></span>
                </span>
                <span class="text-sm font-medium <?= esc($healthTone['text']) ?>">
                    API: <?= esc(lang('Dashboard.status_' . ($apiHealth['state'] ?? 'down'))) ?>
                    (<?= esc((string) ($apiHealth['latency_ms'] ?? 0)) ?>ms)
                </span>
            </div>

            <?php if ($healthTimestamp !== null): ?>
                <p class="mt-2 text-xs text-gray-500">
                    <?= esc(lang('Dashboard.last_check')) ?>: <?= esc((string) $healthTimestamp) ?>
                </p>
            <?php endif; ?>

            <!-- Detalle por componente -->
            <?php if ($dbCheck !== null || $diskCheck !== null || $writableCheck !== null): ?>
                <div class="mt-4 space-y-2 border-t border-gray-100 pt-4">

                    <?php if ($dbCheck !== null): ?>
                        <?php $tone = check_tone_badge((string) ($dbCheck['status'] ?? 'unknown')); ?>
                        <div class="flex items-center justify-between gap-3 py-1">
                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                <?= ui_icon('database', 'h-4 w-4 text-gray-400') ?>
                                <span><?= esc(lang('Dashboard.check_database')) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-2 w-2 rounded-full <?= esc($tone['dot']) ?>"></span>
                                <span class="text-xs font-medium <?= esc($tone['text']) ?>">
                                    <?php if (isset($dbCheck['response_time_ms']) && is_numeric($dbCheck['response_time_ms'])): ?>
                                        <?= esc((string) $dbCheck['response_time_ms']) ?> ms
                                    <?php else: ?>
                                        <?= esc(lang('Dashboard.check_status_' . ($dbCheck['status'] ?? 'unknown'))) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($diskCheck !== null): ?>
                        <?php
                        $tone = check_tone_badge((string) ($diskCheck['status'] ?? 'unknown'));
                        $usedPct = $diskCheck['used_percentage'] ?? null;
                        $freeMb = $diskCheck['free_space_mb'] ?? null;
                        $freeLabel = '';
                        if (is_numeric($freeMb)) {
                            $freeLabel = (float) $freeMb >= 1024
                                ? number_format((float) $freeMb / 1024, 1) . ' GB'
                                : number_format((float) $freeMb, 0) . ' MB';
                        }
                        ?>
                        <div class="py-1">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <?= ui_icon('hard-drive', 'h-4 w-4 text-gray-400') ?>
                                    <span><?= esc(lang('Dashboard.check_disk')) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2 w-2 rounded-full <?= esc($tone['dot']) ?>"></span>
                                    <span class="text-xs font-medium <?= esc($tone['text']) ?>">
                                        <?php if (is_numeric($usedPct)): ?>
                                            <?= esc(number_format((float) $usedPct, 0)) ?>%<?php if ($freeLabel !== ''): ?> · <?= esc($freeLabel) ?> <?= esc(lang('Dashboard.disk_free_suffix')) ?><?php endif; ?>
                                        <?php else: ?>
                                            <?= esc(lang('Dashboard.check_status_' . ($diskCheck['status'] ?? 'unknown'))) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (is_numeric($usedPct)): ?>
                                <div class="mt-1.5 ml-6 h-1 w-[calc(100%-1.5rem)] rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full <?= esc($tone['dot']) ?>" style="width: <?= esc((string) min(100, max(0, (float) $usedPct))) ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($writableCheck !== null): ?>
                        <?php
                        $tone = check_tone_badge((string) ($writableCheck['status'] ?? 'unknown'));
                        $nonWritable = $writableCheck['non_writable'] ?? [];
                        $blockedCount = is_array($nonWritable) ? count($nonWritable) : 0;
                        ?>
                        <div class="py-1">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                    <?= ui_icon('folder-lock', 'h-4 w-4 text-gray-400') ?>
                                    <span><?= esc(lang('Dashboard.check_writable')) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2 w-2 rounded-full <?= esc($tone['dot']) ?>"></span>
                                    <span class="text-xs font-medium <?= esc($tone['text']) ?>">
                                        <?php if ($blockedCount === 0): ?>
                                            <?= esc(lang('Dashboard.writable_ok')) ?>
                                        <?php else: ?>
                                            <?= esc(sprintf(lang('Dashboard.writable_blocked'), $blockedCount)) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($blockedCount > 0 && is_array($nonWritable)): ?>
                                <ul class="mt-1 ml-6 space-y-0.5 text-xs text-red-600 font-mono break-all">
                                    <?php foreach ($nonWritable as $path): ?>
                                        <li><?= esc((string) $path) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Widget: Recent Activity -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-6"><?= lang('Dashboard.recent_activity') ?></h3>
            <div class="flow-root">
                <?php if (empty($recent_activity)): ?>
                    <p class="text-sm text-gray-500 text-center py-4 italic"><?= lang('Dashboard.noRecentActivity') ?></p>
                <?php else: ?>
                    <ul role="list" class="-mb-8">
                        <?php foreach ($recent_activity as $index => $item): ?>
                            <?= view('dashboard/partials/activity_item', [
                                'item' => $item,
                                'isLast' => $index === count($recent_activity) - 1,
                            ]) ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <!-- Widget: Quick Start -->
        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-2"><?= lang('Dashboard.quick_start') ?></h3>
            <p class="text-xs text-gray-500 mb-4"><?= lang('Dashboard.quick_start_desc') ?></p>
            <div class="grid grid-cols-2 gap-2">
                <?php if (has_admin_access((string) (session('user.role') ?? ''))): ?>
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
