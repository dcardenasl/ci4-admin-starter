<?php $csrfName = csrf_token();
$csrfHash = csrf_hash(); ?>
<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= site_url('files/data') ?>',
        pageUrl: '<?= site_url('files') ?>',
        mode: 'files',
        routes: {
            downloadBase: '<?= site_url('files') ?>',
            deleteBase: '<?= site_url('files') ?>'
        },
        csrf: {
            name: '<?= esc($csrfName) ?>',
            hash: '<?= esc($csrfHash) ?>'
        },
        confirmDelete: '<?= esc(lang('Files.confirmDelete')) ?>'
    })" x-init="init()">
    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('Files.myFiles'),
    ]) ?>
    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => site_url('files'),
        'clearUrl' => site_url('files'),
        'hasFilters' => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults' => ['limit' => '25'],
        'fieldsView' => 'files/partials/filters',
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('Files.loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
            <p class="text-sm text-gray-600"><?= lang('Files.noFiles') ?></p>
            <p class="mt-1 text-xs text-gray-500"><?= lang('Files.dragDrop') ?></p>
        </div>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?> w-16"><?= lang('App.preview') ?? 'Preview' ?></th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('originalName')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('originalName')" aria-label="<?= esc(lang('Files.sortByFileName')) ?>">
                                <span><?= lang('Files.fileName') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('originalName')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('uploadedAt')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('uploadedAt')" aria-label="<?= esc(lang('Files.sortByDate')) ?>">
                                <span><?= lang('Files.date') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('uploadedAt')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Files.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>">
                                <template x-if="row.isImage || row.is_image">
                                    <button type="button" @click="$dispatch('open-preview', '<?= site_url('files') ?>/' + (row.id ?? '') + '/view')">
                                        <img :src="'<?= site_url('files') ?>/' + (row.id ?? '') + '/view'" 
                                             class="h-10 w-10 rounded-lg object-cover border border-gray-200 hover:scale-110 transition-transform shadow-sm" 
                                             :alt="row.originalName || row.original_name">
                                    </button>
                                </template>
                                <template x-if="!(row.isImage || row.is_image)">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200">
                                        <?= ui_icon('file', 'h-5 w-5 text-gray-400') ?>
                                    </div>
                                </template>
                            </td>
                            <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.originalName || row.original_name || '-')"></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.uploadedAt || row.uploaded_at)"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <div class="flex items-center gap-2">
                                    <a :href="fileDownloadUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('Files.download') ?></a>
                                    <form method="post" :action="fileDeleteUrl(row.id)" @submit="return confirm(confirmDelete)">
                                        <input type="hidden" :name="csrf.name" :value="csrf.hash">
                                        <button type="submit" class="<?= esc(action_button_class('danger')) ?>"><?= lang('Files.delete') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
    </template>

    <?= view('layouts/partials/remote_pagination') ?>

    <!-- Image Preview Modal (Lightbox) -->
    <div x-data="{ show: false, url: '' }"
         x-show="show" 
         @open-preview.window="url = $event.detail; show = true"
         @keydown.escape.window="show = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
         @click="show = false"
         style="display: none;">
        
        <div class="relative max-h-full max-w-full" @click.stop>
            <button type="button" @click="show = false" 
                    class="absolute -top-12 right-0 p-2 text-white hover:text-gray-300 focus:outline-none transition-colors"
                    aria-label="<?= lang('App.close') ?>">
                <?= ui_icon('x', 'h-8 w-8') ?>
            </button>
            
            <img :src="url" 
                 class="max-h-[85vh] max-w-[90vw] rounded-lg shadow-2xl object-contain border border-white/10"
                 @click.stop>
        </div>
    </div>
</section>
