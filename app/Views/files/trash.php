<?php
$csrfName = csrf_token();
$csrfHash = csrf_hash();
?>
<div class="mb-4 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-gray-900"><?= esc(lang('Files.trash_title')) ?></h2>
        <p class="text-sm text-gray-500 mt-1"><?= esc(lang('Files.confirm_force_delete')) ?></p>
    </div>
    <a href="<?= route_to('files') ?>" class="<?= esc(action_button_class()) ?>">
        &larr; <?= esc(lang('App.back')) ?>
    </a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="Object.assign({
        selectedIds: [],
        isSelected(id) { return this.selectedIds.includes(String(id)); },
        toggleSelect(id) {
            const key = String(id);
            const idx = this.selectedIds.indexOf(key);
            if (idx >= 0) this.selectedIds.splice(idx, 1); else this.selectedIds.push(key);
        },
        toggleAllOnPage() {
            const ids = (this.rows || []).map(r => String(r.id));
            const allSelected = ids.every(id => this.selectedIds.includes(id));
            if (allSelected) this.selectedIds = this.selectedIds.filter(id => !ids.includes(id));
            else ids.forEach(id => { if (!this.selectedIds.includes(id)) this.selectedIds.push(id); });
        },
        clearSelection() { this.selectedIds = []; }
    }, remoteTable({
        apiUrl: '<?= site_url('files/trash/data') ?>',
        pageUrl: '<?= route_to('files.trash') ?>',
        defaultSort: '-uploaded_at',
        routes: {},
        csrf: { name: '<?= esc($csrfName) ?>', hash: '<?= esc($csrfHash) ?>' },
        limitOptions: <?= esc(json_encode(array_map('strval', $limitOptions ?? [10, 25, 50, 100]))) ?>
    }))" x-init="init()">

    <?= view('layouts/partials/filter_panel', [
        'actionUrl'          => route_to('files.trash'),
        'clearUrl'           => route_to('files.trash'),
        'hasFilters'         => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults'     => ['limit' => '25'],
        'fieldsView'         => 'files/partials/filters',
        'fieldsData'         => [
            'limitOptions'    => $limitOptions ?? [10, 25, 50, 100],
            'categoryOptions' => $categoryOptions ?? [],
        ],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <!-- Bulk action bar (trash) -->
    <div x-show="selectedIds.length > 0"
         x-cloak
         class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm">
        <div class="flex items-center gap-3">
            <span class="font-medium text-brand-700"
                  x-text="`<?= esc(lang('Files.bulk_selected', ['___COUNT___'])) ?>`.replace('___COUNT___', selectedIds.length)"></span>
            <button type="button" @click="clearSelection()" class="text-xs text-brand-700 hover:underline">
                <?= esc(lang('Files.bulk_clear')) ?>
            </button>
        </div>
        <div class="flex items-center gap-2">
            <form method="post" action="<?= route_to('files.bulk') ?>">
                <input type="hidden" :name="csrf.name" :value="csrf.hash">
                <input type="hidden" name="action" value="restore">
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit" class="<?= esc(action_button_class('primary')) ?>">
                    <?= ui_icon('refresh-ccw', 'h-3.5 w-3.5') ?>
                    <?= esc(lang('Files.bulk_restore')) ?>
                </button>
            </form>
            <form method="post" action="<?= route_to('files.bulk') ?>"
                  @submit.prevent="$store.confirm.show('<?= esc(lang('Files.bulk_confirm_force'), 'js') ?>', () => $el.submit())">
                <input type="hidden" :name="csrf.name" :value="csrf.hash">
                <input type="hidden" name="action" value="force">
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
                    <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                    <?= esc(lang('Files.bulk_force_delete')) ?>
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('App.loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <?= view('components/display/empty_state', [
            'icon'        => 'trash-2',
            'title'       => 'Files.trash_empty',
            'description' => 'App.no_results_desc',
        ]) ?>
    </template>

    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?> w-10">
                            <input type="checkbox"
                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                   @change="toggleAllOnPage()"
                                   :checked="rows.length > 0 && rows.every(r => selectedIds.includes(String(r.id)))"
                                   :aria-label="'<?= esc(lang('TableColumns.select_all')) ?>'">
                        </th>
                        <th class="<?= esc(table_th_class()) ?> w-16"><?= lang('TableColumns.preview') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.file_name') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.category') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.size') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.date') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                        <tr class="<?= esc(table_row_class()) ?>"
                            :class="{ 'bg-brand-50/40': isSelected(row.id) }">
                            <td class="<?= esc(table_td_class()) ?>">
                                <input type="checkbox"
                                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                       :checked="isSelected(row.id)"
                                       @change="toggleSelect(row.id)">
                            </td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <template x-if="row.is_image || (row.variants && row.variants.sm && row.variants.sm.url)">
                                    <img :src="(row.variants && row.variants.sm && row.variants.sm.url) || ('<?= route_to('files') ?>/' + (row.id ?? '') + '/view')"
                                         class="h-10 w-10 rounded-lg object-cover border border-gray-200 grayscale opacity-70"
                                         :alt="row.original_name">
                                </template>
                                <template x-if="!(row.is_image || (row.variants && row.variants.sm && row.variants.sm.url))">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-gray-100 border border-gray-200">
                                        <?= ui_icon('file', 'h-5 w-5 text-gray-400') ?>
                                    </div>
                                </template>
                            </td>
                            <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.original_name || '-')"></td>
                            <td class="<?= esc(table_td_class('subtle')) ?> text-xs uppercase" x-text="String(row.category || '-')"></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.human_size || '-')"></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.deleted_at || row.uploaded_at)"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <div class="flex items-center gap-2">
                                    <form method="post" :action="'<?= route_to('files') ?>/' + (row.id ?? '') + '/restore'">
                                        <input type="hidden" :name="csrf.name" :value="csrf.hash">
                                        <button type="submit" class="<?= esc(action_button_class('primary')) ?>" :title="'<?= esc(lang('Files.restore')) ?>'">
                                            <?= ui_icon('refresh-ccw', 'h-3.5 w-3.5') ?>
                                            <span class="hidden md:inline"><?= lang('Files.restore') ?></span>
                                        </button>
                                    </form>
                                    <form method="post" :action="'<?= route_to('files') ?>/' + (row.id ?? '') + '/force'"
                                          @submit.prevent="$store.confirm.show('<?= esc(lang('Files.confirm_force_delete'), 'js') ?>', () => $el.submit())">
                                        <input type="hidden" :name="csrf.name" :value="csrf.hash">
                                        <button type="submit" class="<?= esc(action_button_class('danger')) ?>" :title="'<?= esc(lang('Files.force_delete')) ?>'">
                                            <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                                            <span class="hidden md:inline"><?= lang('Files.force_delete') ?></span>
                                        </button>
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
