<?php
$csrfName = csrf_token();
$csrfHash = csrf_hash();
$categoryOptions = $categoryOptions ?? [
    ['value' => '',         'label' => lang('Files.category_all')],
    ['value' => 'image',    'label' => lang('Files.category_image')],
    ['value' => 'document', 'label' => lang('Files.category_document')],
    ['value' => 'video',    'label' => lang('Files.category_video')],
    ['value' => 'audio',    'label' => lang('Files.category_audio')],
];
$currentCategory = (string) request()->getGet('category');
?>
<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="Object.assign({
        // viewMode is a UI preference scoped to the current tab; sessionStorage
        // (not localStorage) keeps it out of cross-user persistence on shared
        // machines and aligns with the architecture rule of not stashing state
        // outside the server-side session.
        viewMode: sessionStorage.getItem('filesViewMode') || 'table',
        setViewMode(mode) { this.viewMode = mode; sessionStorage.setItem('filesViewMode', mode); },
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
        apiUrl: '<?= site_url('files/data') ?>',
        pageUrl: '<?= route_to('files') ?>',
        defaultSort: '-uploaded_at',
        routes: {
            downloadBase: '<?= route_to('files') ?>',
            deleteBase: '<?= route_to('files') ?>'
        },
        csrf: {
            name: '<?= esc($csrfName) ?>',
            hash: '<?= esc($csrfHash) ?>'
        },
        confirmDelete: '<?= esc(lang('Files.confirm_delete')) ?>',
        limitOptions: <?= esc(json_encode(array_map('strval', $limitOptions ?? [10, 25, 50, 100]))) ?>
    }))" x-init="init()">

    <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Files.my_files') ?></h3>
        <div class="flex items-center gap-2">
            <a href="<?= route_to('files.trash') ?>" class="<?= esc(action_button_class()) ?>" title="<?= esc(lang('Files.trash_title')) ?>">
                <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                <span class="hidden md:inline"><?= esc(lang('Files.trash_title')) ?></span>
            </a>
            <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden">
                <button type="button" @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-brand-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 text-sm transition-colors">
                    <?= ui_icon('list', 'h-4 w-4') ?>
                </button>
                <button type="button" @click="setViewMode('grid')" :class="viewMode === 'grid' ? 'bg-brand-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 text-sm transition-colors">
                    <?= ui_icon('grid', 'h-4 w-4') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Type tabs (URL-driven) -->
    <?php
    $tabsBaseQuery = request()->getGet();
if (! is_array($tabsBaseQuery)) {
    $tabsBaseQuery = [];
}
unset($tabsBaseQuery['page'], $tabsBaseQuery['cursor'], $tabsBaseQuery['category']);
?>
    <div class="mt-3 flex flex-wrap gap-2 text-sm">
        <?php foreach ($categoryOptions as $opt): ?>
            <?php
        $val   = (string) ($opt['value'] ?? '');
            $label = (string) ($opt['label'] ?? $val);
            $query = $tabsBaseQuery;
            if ($val !== '') {
                $query['category'] = $val;
            }
            $qs    = http_build_query($query);
            $url   = route_to('files') . ($qs !== '' ? '?' . $qs : '');
            $active = $currentCategory === $val;
            ?>
            <a href="<?= esc($url) ?>"
               class="rounded-lg px-3 py-1.5 font-medium transition-colors <?= $active ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                <?= esc($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl'          => route_to('files'),
        'clearUrl'           => route_to('files'),
        'hasFilters'         => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults'     => ['limit' => '25'],
        'fieldsView'         => 'files/partials/filters',
        'fieldsData'         => [
            'limitOptions'    => $limitOptions ?? [10, 25, 50, 100],
            'categoryOptions' => $categoryOptions,
        ],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <!-- Bulk action bar -->
    <div x-show="selectedIds.length > 0"
         x-cloak
         class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm">
        <div class="flex items-center gap-3">
            <span class="font-medium text-brand-700"
                  x-text="`<?= esc(lang('Files.bulk_selected', ['___COUNT___'])) ?>`.replace('___COUNT___', selectedIds.length)"></span>
            <button type="button" @click="clearSelection()" class="text-xs text-brand-700 hover:underline">
                <?= esc(lang('Files.bulk_clear')) ?>
            </button>
        </div>
        <form method="post" action="<?= route_to('files.bulk') ?>"
              @submit.prevent="$store.confirm.show('<?= esc(lang('Files.bulk_confirm_delete'), 'js') ?>', () => $el.submit())">
            <input type="hidden" :name="csrf.name" :value="csrf.hash">
            <input type="hidden" name="action" value="delete">
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
                <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                <?= esc(lang('Files.bulk_delete')) ?>
            </button>
        </form>
    </div>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('App.loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <?= view('components/display/empty_state', [
            'icon'        => 'upload-cloud',
            'title'       => 'Files.no_files',
            'description' => 'Files.drag_drop',
        ]) ?>
    </template>

    <!-- TABLE VIEW -->
    <template x-if="!loading && !error && rows.length > 0 && viewMode === 'table'">
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
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('original_name')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('original_name')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.file_name')])) ?>">
                                <span><?= lang('TableColumns.file_name') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('original_name')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.category') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.size') ?></th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('uploaded_at')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('uploaded_at')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.date')])) ?>">
                                <span><?= lang('TableColumns.date') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('uploaded_at')"></span>
                            </button>
                        </th>
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
                                    <button type="button" @click="$dispatch('open-preview', (row.variants && row.variants.lg && row.variants.lg.url) || ('<?= route_to('files') ?>/' + (row.id ?? '') + '/view'))">
                                        <img :src="(row.variants && row.variants.sm && row.variants.sm.url) || ('<?= route_to('files') ?>/' + (row.id ?? '') + '/view')"
                                             class="h-10 w-10 rounded-lg object-cover border border-gray-200 hover:scale-110 transition-transform shadow-sm"
                                             :alt="row.alt_text || row.original_name">
                                    </button>
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
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.uploaded_at)"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <div class="flex items-center gap-2">
                                    <a :href="'<?= route_to('files') ?>/' + (row.id ?? '') + '/show'" class="<?= esc(action_button_class()) ?>" :title="'<?= esc(lang('App.view')) ?>'">
                                        <?= ui_icon('eye', 'h-3.5 w-3.5') ?>
                                        <span class="hidden md:inline"><?= lang('App.view') ?></span>
                                    </a>
                                    <a :href="fileDownloadUrl(row.id)" class="<?= esc(action_button_class()) ?>" :title="'<?= esc(lang('App.download')) ?>'">
                                        <?= ui_icon('download', 'h-3.5 w-3.5') ?>
                                    </a>
                                    <form method="post" :action="fileDeleteUrl(row.id)" @submit.prevent="$store.confirm.show(confirmDelete, () => $el.submit())">
                                        <input type="hidden" :name="csrf.name" :value="csrf.hash">
                                        <button type="submit" class="<?= esc(action_button_class('danger')) ?>" :title="'<?= esc(lang('App.delete')) ?>'">
                                            <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
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

    <!-- GRID VIEW -->
    <template x-if="!loading && !error && rows.length > 0 && viewMode === 'grid'">
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                <a :href="'<?= route_to('files') ?>/' + (row.id ?? '') + '/show'" class="group block rounded-lg border border-gray-200 bg-gray-50 p-2 hover:border-brand-300 hover:shadow-sm transition-all">
                    <div class="aspect-square w-full overflow-hidden rounded-md bg-white border border-gray-100 flex items-center justify-center">
                        <template x-if="row.is_image || (row.variants && row.variants.sm && row.variants.sm.url)">
                            <img :src="(row.variants && row.variants.sm && row.variants.sm.url) || ('<?= route_to('files') ?>/' + (row.id ?? '') + '/view')"
                                 :alt="row.alt_text || row.original_name"
                                 class="w-full h-full object-cover">
                        </template>
                        <template x-if="!(row.is_image || (row.variants && row.variants.sm && row.variants.sm.url))">
                            <div class="text-gray-300">
                                <?= ui_icon('file', 'h-12 w-12') ?>
                            </div>
                        </template>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs font-medium text-gray-800 truncate" x-text="String(row.original_name || '-')"></p>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="String(row.human_size || '-')"></p>
                    </div>
                </a>
            </template>
        </div>
    </template>

    <?= view('layouts/partials/remote_pagination') ?>

    <!-- Image Preview Modal (Lightbox) -->
    <div x-data="{ show: false, url: '' }"
         x-show="show"
         x-cloak
         @open-preview.window="url = $event.detail; show = true"
         @keydown.escape.window="show = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
         @click="show = false">

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
