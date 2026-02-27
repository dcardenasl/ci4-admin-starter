<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= site_url('admin/api-keys/data') ?>',
        pageUrl: '<?= site_url('admin/api-keys') ?>',
        mode: 'api_keys',
        routes: {
            showBase: '<?= site_url('admin/api-keys') ?>',
            editBase: '<?= site_url('admin/api-keys') ?>'
        }
    })" x-init="init()">
    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('ApiKeys.title'),
        'actionsView' => 'api_keys/partials/toolbar_actions',
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => site_url('admin/api-keys'),
        'clearUrl' => site_url('admin/api-keys'),
        'hasFilters' => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults' => ['limit' => '25'],
        'fieldsView' => 'api_keys/partials/filters',
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('ApiKeys.loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <p class="mt-6 text-sm text-gray-500"><?= lang('ApiKeys.noApiKeys') ?></p>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
                <table class="<?= esc(table_class()) ?>">
                    <thead class="<?= esc(table_head_class()) ?>">
                        <tr>
                            <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('name')">
                                <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('name')">
                                    <span><?= lang('ApiKeys.name') ?></span>
                                    <span aria-hidden="true" x-text="sortIcon('name')"></span>
                                </button>
                            </th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('ApiKeys.keyPrefix') ?></th>
                            <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('isActive')">
                                <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('isActive')">
                                    <span><?= lang('ApiKeys.status') ?></span>
                                    <span aria-hidden="true" x-text="sortIcon('isActive')"></span>
                                </button>
                            </th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('ApiKeys.rateLimitRequests') ?></th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('ApiKeys.rateLimitWindow') ?></th>
                            <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('createdAt')">
                                <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('createdAt')">
                                    <span><?= lang('ApiKeys.createdAt') ?></span>
                                    <span aria-hidden="true" x-text="sortIcon('createdAt')"></span>
                                </button>
                            </th>
                            <th class="<?= esc(table_th_class()) ?>"><?= lang('ApiKeys.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody class="<?= esc(table_body_class()) ?>">
                        <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                            <tr class="<?= esc(table_row_class()) ?>">
                                <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.name ?? '-')"></td>
                                <td class="<?= esc(table_td_class('subtle')) ?> font-mono text-xs" x-text="String(row.keyPrefix ?? '-')"></td>
                                <td class="<?= esc(table_td_class()) ?>">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs" :class="(row.isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700')" x-text="row.isActive ? '<?= esc(lang('ApiKeys.active')) ?>' : '<?= esc(lang('ApiKeys.inactive')) ?>'"></span>
                                </td>
                                <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.rateLimitRequests ?? '-')"></td>
                                <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.rateLimitWindow ?? '-')"></td>
                                <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.createdAt)"></td>
                                <td class="<?= esc(table_td_class()) ?>">
                                    <div class="flex items-center gap-2">
                                        <a :href="apiKeyShowUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('ApiKeys.view') ?></a>
                                        <a :href="apiKeyEditUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
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
