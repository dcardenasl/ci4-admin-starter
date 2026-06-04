<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= route_to('admin.api_keys.data') ?>',
        pageUrl: '<?= route_to('admin.api_keys') ?>',
        mode: 'api_keys',
        routes: {
            showBase: '<?= route_to('admin.api_keys') ?>',
            editBase: '<?= route_to('admin.api_keys') ?>'
        },
        limitOptions: <?= esc(json_encode(array_map('strval', $limitOptions ?? [10, 25, 50, 100]))) ?>
    })" x-init="init()">
    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('ApiKeys.title'),
        'actionsView' => 'api_keys/partials/toolbar_actions',
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => route_to('admin.api_keys'),
        'clearUrl' => route_to('admin.api_keys'),
        'hasFilters' => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults' => ['limit' => '25'],
        'fieldsView' => 'api_keys/partials/filters',
        'fieldsData' => [
            'statusOptions' => $statusOptions ?? [],
            'limitOptions' => $limitOptions ?? [10, 25, 50, 100],
        ],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('ApiKeys.loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <?= view('components/display/empty_state', [
            'icon'        => 'key',
            'actionUrl'   => route_to('admin.api_keys.create'),
            'actionLabel' => 'App.create',
        ]) ?>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
                <table class="<?= esc(table_class()) ?>">
                    <thead class="<?= esc(table_head_class()) ?>">
                        <tr>
                            <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('name')">
                                <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('name')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.name')])) ?>">
                                    <span><?= lang('TableColumns.name') ?></span>
                                    <span aria-hidden="true" x-text="sortIcon('name')"></span>
                                </button>
                            </th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.key_prefix') ?></th>
                            <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('is_active')">
                                <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('is_active')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.status')])) ?>">
                                    <span><?= lang('TableColumns.status') ?></span>
                                    <span aria-hidden="true" x-text="sortIcon('is_active')"></span>
                                </button>
                            </th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('ApiKeys.rate_limit_requests') ?></th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('ApiKeys.rate_limit_window') ?></th>
                            <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                                <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('TableColumns.created_at')])) ?>">
                                    <span><?= lang('TableColumns.created_at') ?></span>
                                    <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                                </button>
                            </th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody class="<?= esc(table_body_class()) ?>">
                        <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                            <tr class="<?= esc(table_row_class()) ?>">
                                <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.name ?? '-')"></td>
                                <td class="<?= esc(table_td_class('subtle')) ?> font-mono text-xs" x-text="String(row.key_prefix ?? '-')"></td>
                                <td class="<?= esc(table_td_class()) ?>">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs" :class="(row.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700')" x-text="row.is_active ? '<?= esc(lang('ApiKeys.active')) ?>' : '<?= esc(lang('ApiKeys.inactive')) ?>'"></span>
                                </td>
                                <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.rate_limit_requests ?? '-')"></td>
                                <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.rate_limit_window ?? '-')"></td>
                                <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                                <td class="<?= esc(table_td_class()) ?>">
                                    <div class="flex items-center gap-2">
                                        <a :href="apiKeyShowUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('ApiKeys.view') ?></a>
                                        <?php if (has_permission('apikeys.write')): ?>
                                            <a :href="apiKeyEditUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                                        <?php endif; ?>
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
