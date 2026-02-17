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
        'hasFilters' => has_active_filters(),
        'reactiveHasFilters' => true,
        'fieldsView' => 'audit/partials/filters',
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        Cargando auditoria...
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
                        <th class="<?= esc(table_th_class()) ?>">ID</th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('user_id')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('user_id')" :aria-label="'Ordenar por usuario'">
                                <span><?= lang('Audit.user') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('user_id')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('action')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('action')" :aria-label="'Ordenar por accion'">
                                <span><?= lang('Audit.action') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('action')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('entity_type')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('entity_type')" :aria-label="'Ordenar por entidad'">
                                <span><?= lang('Audit.entity') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('entity_type')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Audit.ipAddress') ?></th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" :aria-label="'Ordenar por fecha'">
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
                                <span class="inline-flex rounded-full px-2 py-1 text-xs" :class="auditActionBadgeClass(row.action)" x-text="String(row.action ?? '-')"></span>
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

    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 text-sm text-gray-600" x-show="!loading && !error && rows.length > 0 && hasPagination()">
        <span x-text="paginationLabel()"></span>
        <nav class="flex items-center gap-1">
            <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                x-show="isCursorMode() ? (pagination.prevCursor !== '') : (pagination.currentPage > 1)"
                @click="isCursorMode() ? goToCursor(pagination.prevCursor) : goToPage(pagination.currentPage - 1)" :disabled="loading">Anterior</button>
            <template x-if="!isCursorMode()">
                <div class="flex items-center gap-1">
                    <template x-for="page in pageWindow()" :key="'p-' + page">
                        <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50" :class="page === pagination.currentPage ? 'bg-brand-600 text-white border-brand-600' : ''"
                            @click="goToPage(page)" :disabled="loading" x-text="page"></button>
                    </template>
                </div>
            </template>
            <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-40"
                x-show="isCursorMode() ? (pagination.nextCursor !== '') : (pagination.currentPage < pagination.lastPage)"
                @click="isCursorMode() ? goToCursor(pagination.nextCursor) : goToPage(pagination.currentPage + 1)" :disabled="loading">Siguiente</button>
        </nav>
    </div>
</section>
