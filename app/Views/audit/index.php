<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= site_url('admin/audit/data') ?>',
        pageUrl: '<?= site_url('admin/audit') ?>',
        mode: 'audit',
        routes: {
            showBase: '<?= site_url('admin/audit') ?>'
        }
    })" x-init="init()">
    <?= view('layouts/partials/table_toolbar', [
        'title' => esc($title),
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => site_url('admin/audit'),
        'clearUrl' => site_url('admin/audit'),
        'hasFilters' => has_active_filters(request()->getGet(), ['limit' => '25']),
        'reactiveHasFilters' => true,
        'filterDefaults' => ['limit' => '25'],
        'fieldsView' => 'audit/partials/filters',
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        <?= lang('Audit.loading') ?>
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <p class="mt-6 text-sm text-gray-500"><?= lang('Audit.noLogs') ?></p>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('App.id') ?></th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('user_id')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('user_id')" aria-label="<?= esc(lang('Audit.sortByUser')) ?>">
                                <span><?= lang('Audit.user') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('user_id')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('action')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('action')" aria-label="<?= esc(lang('Audit.sortByAction')) ?>">
                                <span><?= lang('Audit.action') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('action')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('entity_type')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('entity_type')" aria-label="<?= esc(lang('Audit.sortByEntity')) ?>">
                                <span><?= lang('Audit.entity') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('entity_type')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Audit.ipAddress') ?></th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" aria-label="<?= esc(lang('Audit.sortByDate')) ?>">
                                <span><?= lang('Audit.date') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Audit.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.id ?? '-')"></td>
                            <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.user_email ?? row.user_id ?? '-')"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs" :class="auditActionBadgeClass(row.action)" x-text="auditActionLabel(row.action)"></span>
                            </td>
                            <td class="<?= esc(table_td_class('muted')) ?>">
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
