<?php $csrfName = csrf_token(); $csrfHash = csrf_hash(); ?>
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
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('name')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('name')" aria-label="<?= esc(lang('Files.sortByFileName')) ?>">
                                <span><?= lang('Files.fileName') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('name')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('status')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('status')" aria-label="<?= esc(lang('Files.sortByStatus')) ?>">
                                <span><?= lang('Files.status') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('status')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" aria-label="<?= esc(lang('Files.sortByDate')) ?>">
                                <span><?= lang('Files.date') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Files.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.name ?? row.filename ?? '-')"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs" :class="statusBadgeClass(row.status)" x-text="statusLabel(row.status)"></span>
                            </td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
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
</section>
