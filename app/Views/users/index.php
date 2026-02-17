<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= site_url('admin/users/data') ?>',
        pageUrl: '<?= site_url('admin/users') ?>',
        mode: 'users',
        routes: {
            showBase: '<?= site_url('admin/users') ?>',
            editBase: '<?= site_url('admin/users') ?>'
        }
    })" x-init="init()">
    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('Users.title'),
        'actionsView' => 'users/partials/toolbar_actions',
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => site_url('admin/users'),
        'clearUrl' => site_url('admin/users'),
        'hasFilters' => has_active_filters(),
        'reactiveHasFilters' => true,
        'fieldsView' => 'users/partials/filters',
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        Cargando usuarios...
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && rows.length === 0">
        <p class="mt-6 text-sm text-gray-500"><?= lang('Users.noUsers') ?></p>
    </template>
    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('first_name')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('first_name')" :aria-label="'Ordenar por nombre'">
                                <span><?= lang('Users.name') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('first_name')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('email')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('email')" :aria-label="'Ordenar por correo'">
                                <span><?= lang('Users.email') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('email')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('role')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('role')" :aria-label="'Ordenar por rol'">
                                <span><?= lang('Users.role') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('role')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('status')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('status')" :aria-label="'Ordenar por estado'">
                                <span><?= lang('Users.status') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('status')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                            <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" :aria-label="'Ordenar por fecha'">
                                <span><?= lang('Users.date') ?></span>
                                <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                            </button>
                        </th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Users.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class('primary')) ?>" x-text="fullName(row)"></td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.email ?? '-')"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs" :class="roleBadgeClass(row.role)" x-text="String(row.role ?? '-')"></span>
                            </td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs" :class="statusBadgeClass(row.status)" x-text="statusLabel(row.status)"></span>
                            </td>
                            <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                            <td class="<?= esc(table_td_class()) ?>">
                                <div class="flex items-center gap-2">
                                    <a :href="userShowUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('Users.view') ?></a>
                                    <a :href="userEditUrl(row.id)" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                                </div>
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
