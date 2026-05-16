<?php /** @var array $limitOptions */ ?>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= route_to('admin.iam.applications.data') ?>',
        pageUrl: '<?= route_to('admin.iam.applications') ?>',
        routes: {
            showBase: '<?= route_to('admin.iam.applications') ?>'
        },
        limitOptions: <?= esc(json_encode(array_map('strval', $limitOptions ?? [10, 25, 50, 100]))) ?>
    })" x-init="init()">

    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('Iam.applications_title'),
    ]) ?>

    <p class="mt-2 text-xs text-gray-500"><?= esc(lang('Iam.applications_managed_server_side')) ?></p>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl'          => route_to('admin.iam.applications'),
        'clearUrl'           => route_to('admin.iam.applications'),
        'hasFilters'         => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults'     => ['limit' => '25'],
        'fieldsView'         => 'iam/applications/partials/filters',
        'fieldsData'         => [
            'limitOptions' => $limitOptions ?? [10, 25, 50, 100],
        ],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('Iam.applications_loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <p class="mt-6 text-sm text-gray-500"><?= lang('Iam.applications_no_results') ?></p>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('code')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('code')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('Iam.field_code')])) ?>">
                                <span><?= lang('Iam.field_code') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('code')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('name')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('name')" aria-label="<?= esc(lang('TableA11y.sort_by', [lang('Iam.field_name')])) ?>">
                                <span><?= lang('Iam.field_name') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('name')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Iam.field_active') ?></th>
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
                            <td class="<?= esc(table_td_class('primary')) ?>"><code class="text-xs" x-text="String(row.code ?? '-')"></code></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.name ?? '-')"></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="row.is_active ? 'yes' : 'no'"></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <a :href="showUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('App.view') ?></a>
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
