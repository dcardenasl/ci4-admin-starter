<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= route_to('admin.audit.data') ?>',
        pageUrl: '<?= route_to('admin.audit') ?>',
        mode: 'audit',
        defaultSort: '-created_at',
        routes: {
            showBase: '<?= route_to('admin.audit') ?>'
        },
        limitOptions: <?= esc(json_encode(array_map('strval', $limitOptions ?? [10, 25, 50, 100]))) ?>
    })" x-init="init()">
    <?= view('layouts/partials/table_toolbar', [
        'title' => esc($title),
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => route_to('admin.audit'),
        'clearUrl' => route_to('admin.audit'),
        'hasFilters' => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults' => ['limit' => '25'],
        'fieldsView' => 'audit/partials/filters',
        'fieldsData' => [
            'actionOptions' => $actionOptions ?? [],
            'limitOptions' => $limitOptions ?? [10, 25, 50, 100],
        ],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('Audit.loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <?= view('components/display/empty_state', ['icon' => 'clipboard-list']) ?>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.id') ?></th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('user_id')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('user_id')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.user')])) ?>">
                                <span><?= lang('TableColumns.user') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('user_id')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('action')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('action')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.action')])) ?>">
                                <span><?= lang('TableColumns.action') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('action')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('entity_type')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('entity_type')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.entity')])) ?>">
                                <span><?= lang('TableColumns.entity') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('entity_type')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.ip_address') ?></th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.date')])) ?>">
                                <span><?= lang('TableColumns.date') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.id ?? '-')"></td>
                            <td class="<?= esc(table_td_class('primary')) ?>">
                                <template x-if="row.user_id">
                                    <a :href="'<?= route_to('admin.users') ?>/' + row.user_id" class="flex items-center gap-1.5 hover:text-brand-600 transition-colors">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-[10px] font-bold text-gray-500" x-text="String(row.user_id)"></span>
                                        <span x-text="row.user_email || '<?= lang('Audit.view_user') ?>'"></span>
                                    </a>
                                </template>
                                <template x-if="!row.user_id">
                                    <span class="text-gray-400 italic" x-text="row.user_email || '-'"></span>
                                </template>
                            </td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs w-fit" :class="auditActionBadgeClass(row.action)" x-text="auditActionLabel(row.action)"></span>
                                    <div class="flex gap-1">
                                        <template x-if="row.result && row.result !== 'success'">
                                            <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px]" :class="auditResultBadgeClass(row.result)" x-text="auditResultLabel(row.result)"></span>
                                        </template>
                                        <template x-if="row.severity && row.severity !== 'info'">
                                            <span class="inline-flex rounded-full px-1.5 py-0.5 text-[10px]" :class="auditSeverityBadgeClass(row.severity)" x-text="auditSeverityLabel(row.severity)"></span>
                                        </template>
                                    </div>
                                </div>
                            </td>                            <td class="<?= esc(table_td_class('muted')) ?>">
                                <span x-text="String(row.entity_type ?? '-')"></span>
                                <span class="text-gray-400" x-show="row.entity_id">#<span x-text="String(row.entity_id)"></span></span>
                            </td>
                            <td class="<?= esc(table_td_class('subtle')) ?> font-mono text-xs" x-text="String(row.ip_address ?? '-')"></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <a :href="auditShowUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('Audit.view') ?></a>
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
