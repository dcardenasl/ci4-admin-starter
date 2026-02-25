<section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <?php
    $roleValue = (string) ($stats['role'] ?? 'user');
    ?>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Dashboard.recentFiles') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['files'] ?? 0)) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Dashboard.role') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc(localized_role($roleValue)) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Dashboard.emailVerified') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['emailVerified'] ?? lang('App.no'))) ?></p>
    </article>
    <?php
    $healthState = $apiHealth['state'] ?? 'down';
    $healthTone = match ($healthState) {
        'up' => [
            'dot' => 'bg-green-500',
            'chip' => 'bg-green-100 text-green-800',
            'panel' => 'bg-green-50 border-green-200',
        ],
        'degraded' => [
            'dot' => 'bg-amber-500',
            'chip' => 'bg-amber-100 text-amber-800',
            'panel' => 'bg-amber-50 border-amber-200',
        ],
        default => [
            'dot' => 'bg-red-500',
            'chip' => 'bg-red-100 text-red-800',
            'panel' => 'bg-red-50 border-red-200',
        ],
    };
    ?>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500"><?= lang('Dashboard.apiHealth') ?></p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full <?= esc($healthTone['dot']) ?>"></span>
                    <p class="text-xl font-semibold text-gray-900"><?= esc((string) ($stats['apiHealth'] ?? lang('Dashboard.down'))) ?></p>
                </div>
            </div>
            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?= esc($healthTone['chip']) ?>">
                HTTP <?= esc((string) (($apiHealth['status'] ?? 0) > 0 ? $apiHealth['status'] : '-')) ?>
            </span>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-600">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-2">
                <p class="text-[11px] uppercase tracking-wide text-gray-500"><?= lang('Dashboard.endpoint') ?></p>
                <p class="mt-0.5 font-medium text-gray-700"><?= esc((string) ($apiHealth['path'] ?? '/health')) ?></p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-2">
                <p class="text-[11px] uppercase tracking-wide text-gray-500"><?= lang('Dashboard.latency') ?></p>
                <p class="mt-0.5 font-medium text-gray-700"><?= esc((string) (($apiHealth['latency_ms'] ?? 0) > 0 ? $apiHealth['latency_ms'] . ' ms' : '-')) ?></p>
            </div>
        </div>

        <?php if (! empty($apiHealth['message'])): ?>
            <div class="mt-3 rounded-md border px-2.5 py-2 text-xs <?= esc($healthTone['panel']) ?>">
                <?= esc((string) $apiHealth['message']) ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Dashboard.latestFiles') ?></h3>
        <div class="space-x-2">
            <a href="<?= site_url('files') ?>" class="inline-block rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Dashboard.manageFiles') ?></a>
            <a href="<?= site_url('profile') ?>" class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Dashboard.editProfile') ?></a>
        </div>
    </div>

    <div class="mt-5" x-data="{ previewShow: false, previewUrl: '' }">
        <?php if (empty($recentFiles)): ?>
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                <p class="text-sm text-gray-600"><?= lang('Dashboard.noRecentFiles') ?></p>
                <a href="<?= site_url('files') ?>" class="mt-3 inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Dashboard.manageFiles') ?></a>
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
                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Files.actions') ?></th>
                            </tr>
                        </thead>
                        <tbody class="<?= esc(table_body_class()) ?>">
                            <?php foreach ($recentFiles as $file): ?>
                                <tr class="<?= esc(table_row_class()) ?>">
                                    <td class="<?= esc(table_td_class()) ?>">
                                        <?php if (! empty($file['is_image'])): ?>
                                            <?php $viewUrl = site_url('files/' . ($file['id'] ?? '') . '/view'); ?>
                                            <button type="button" @click="previewUrl = '<?= $viewUrl ?>'; previewShow = true">
                                                <img src="<?= $viewUrl ?>" 
                                                     class="h-8 w-8 rounded-lg object-cover border border-gray-200 hover:scale-110 transition-transform shadow-sm" 
                                                     alt="<?= esc((string) ($file['original_name'] ?? '')) ?>">
                                            </button>
                                        <?php else: ?>
                                            <div class="h-8 w-8 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200">
                                                <?= ui_icon('file', 'h-4 w-4 text-gray-400') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?= esc(table_td_class('primary')) ?>">
                                        <?= esc((string) ($file['original_name'] ?? $file['name'] ?? $file['filename'] ?? '-')) ?>
                                    </td>
                                    <td class="<?= esc(table_td_class()) ?>">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge($file['status'] ?? 'active') ?>">
                                            <?= esc(localized_status((string) ($file['status'] ?? 'active'))) ?>
                                        </span>
                                    </td>
                                    <td class="<?= esc(table_td_class('muted')) ?>">
                                        <?= esc(format_date($file['uploaded_at'] ?? $file['created_at'] ?? null)) ?>
                                    </td>
                                    <td class="<?= esc(table_td_class()) ?>">
                                        <a href="<?= site_url('files/' . ($file['id'] ?? '') . '/download') ?>" 
                                           class="<?= esc(action_button_class()) ?>">
                                            <?= lang('Files.download') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Image Preview Modal (Lightbox) -->
        <div x-show="previewShow" 
             @keydown.escape.window="previewShow = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
             @click="previewShow = false"
             style="display: none;">
            
            <div class="relative max-h-full max-w-full" @click.stop>
                <button type="button" @click="previewShow = false" 
                        class="absolute -top-12 right-0 p-2 text-white hover:text-gray-300 focus:outline-none transition-colors"
                        aria-label="<?= lang('App.close') ?>">
                    <?= ui_icon('x', 'h-8 w-8') ?>
                </button>
                
                <img :src="previewUrl" 
                     class="max-h-[85vh] max-w-[90vw] rounded-lg shadow-2xl object-contain border border-white/10"
                     @click.stop>
            </div>
        </div>
    </div>
</section>
