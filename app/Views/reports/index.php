<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
    x-data="remoteTable({
        apiUrl: '<?= site_url('admin/reports/data') ?>',
        pageUrl: '<?= site_url('admin/reports') ?>',
        mode: 'reports',
        routes: {
            exportCsv: '<?= site_url('admin/reports/export/csv') ?>',
            exportPdf: '<?= site_url('admin/reports/export/pdf') ?>'
        }
    })" x-init="init()">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900"><?= esc($title) ?></h3>
        </div>
        <div class="flex items-center gap-2">
            <a :href="reportExportUrl('csv')" class="<?= esc(action_button_class()) ?>"><?= lang('Reports.exportCsv') ?></a>
            <a :href="reportExportUrl('pdf')" class="<?= esc(action_button_class('primary')) ?>"><?= lang('Reports.exportPdf') ?></a>
        </div>
    </div>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => site_url('admin/reports'),
        'clearUrl' => site_url('admin/reports'),
        'hasFilters' => has_active_filters(request()->getGet(), $defaultFilters ?? []),
        'reactiveHasFilters' => true,
        'filterDefaults' => $defaultFilters ?? [],
        'fieldsView' => 'reports/partials/filters_form',
        'fieldsData' => ['filters' => $filters],
        'submitLabel' => lang('App.search'),
    ]) ?>

    <div class="mt-6 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600" x-show="loading">
        Cargando reporte...
    </div>
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-show="error" x-text="errorMessage"></div>

    <template x-if="!loading && !error && Object.keys(summary).length > 0">
        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            <template x-for="[label, value] in Object.entries(summary)" :key="label">
                <article class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500" x-text="String(label).replaceAll('_', ' ')"></p>
                    <p class="mt-1 text-xl font-semibold text-gray-900" x-text="String(value)"></p>
                </article>
            </template>
        </div>
    </template>

    <template x-if="!loading && !error && rows.length === 0">
        <p class="mt-6 text-sm text-gray-500"><?= lang('Reports.noResults') ?></p>
    </template>

    <template x-if="!loading && !error && rows.length > 0">
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
                <table class="<?= esc(table_class()) ?>">
                    <thead class="<?= esc(table_head_class()) ?>">
                        <template x-if="currentReportType() === 'users'">
                            <tr>
                                <th class="<?= esc(table_th_class()) ?>">ID</th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('email')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('email')" :aria-label="'Ordenar por usuario'">
                                        <span><?= lang('Reports.user') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('email')"></span>
                                    </button>
                                </th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('role')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('role')" :aria-label="'Ordenar por rol'">
                                        <span><?= lang('Reports.role') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('role')"></span>
                                    </button>
                                </th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('status')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('status')" :aria-label="'Ordenar por estado'">
                                        <span><?= lang('Reports.status') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('status')"></span>
                                    </button>
                                </th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" :aria-label="'Ordenar por fecha'">
                                        <span><?= lang('Reports.createdAt') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                                    </button>
                                </th>
                            </tr>
                        </template>
                        <template x-if="currentReportType() === 'activity'">
                            <tr>
                                <th class="<?= esc(table_th_class()) ?>">ID</th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('action')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('action')" :aria-label="'Ordenar por accion'">
                                        <span><?= lang('Reports.action') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('action')"></span>
                                    </button>
                                </th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('email')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('email')" :aria-label="'Ordenar por usuario'">
                                        <span><?= lang('Reports.user') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('email')"></span>
                                    </button>
                                </th>
                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Reports.entity') ?></th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" :aria-label="'Ordenar por fecha'">
                                        <span><?= lang('Reports.createdAt') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                                    </button>
                                </th>
                            </tr>
                        </template>
                        <template x-if="currentReportType() === 'files'">
                            <tr>
                                <th class="<?= esc(table_th_class()) ?>">ID</th>
                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Reports.fileName') ?></th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('status')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('status')" :aria-label="'Ordenar por estado'">
                                        <span><?= lang('Reports.status') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('status')"></span>
                                    </button>
                                </th>
                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Reports.size') ?></th>
                                <th class="<?= esc(table_th_class()) ?>" :aria-sort="sortAria('created_at')">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-700" @click="toggleSort('created_at')" :aria-label="'Ordenar por fecha'">
                                        <span><?= lang('Reports.createdAt') ?></span>
                                        <span aria-hidden="true" x-text="sortIcon('created_at')"></span>
                                    </button>
                                </th>
                            </tr>
                        </template>
                    </thead>
                    <tbody class="<?= esc(table_body_class()) ?>">
                        <template x-for="row in rows" :key="String(row.id ?? Math.random())">
                            <tr class="<?= esc(table_row_class()) ?>">
                                <template x-if="currentReportType() === 'users'">
                                    <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.id ?? '-')"></td>
                                    <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.email ?? row.name ?? '-')"></td>
                                    <td class="<?= esc(table_td_class()) ?>"><span class="inline-flex rounded-full px-2 py-1 text-xs" :class="roleBadgeClass(row.role)" x-text="String(row.role ?? '-')"></span></td>
                                    <td class="<?= esc(table_td_class()) ?>"><span class="inline-flex rounded-full px-2 py-1 text-xs" :class="statusBadgeClass(row.status)" x-text="String(row.status ?? '-')"></span></td>
                                    <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                                </template>
                                <template x-if="currentReportType() === 'activity'">
                                    <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.id ?? '-')"></td>
                                    <td class="<?= esc(table_td_class()) ?>"><span class="inline-flex rounded-full px-2 py-1 text-xs" :class="auditActionBadgeClass(row.action)" x-text="String(row.action ?? '-')"></span></td>
                                    <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.user_email ?? row.user_id ?? '-')"></td>
                                    <td class="<?= esc(table_td_class('muted')) ?>"><span x-text="String(row.entity_type ?? '-')"></span><span class="text-gray-400" x-show="row.entity_id">#<span x-text="String(row.entity_id)"></span></span></td>
                                    <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                                </template>
                                <template x-if="currentReportType() === 'files'">
                                    <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.id ?? '-')"></td>
                                    <td class="<?= esc(table_td_class('primary')) ?>" x-text="String(row.name ?? row.filename ?? '-')"></td>
                                    <td class="<?= esc(table_td_class()) ?>"><span class="inline-flex rounded-full px-2 py-1 text-xs" :class="statusBadgeClass(row.status)" x-text="String(row.status ?? '-')"></span></td>
                                    <td class="<?= esc(table_td_class('muted')) ?>" x-text="String(row.size_human ?? row.size ?? '-')"></td>
                                    <td class="<?= esc(table_td_class('muted')) ?>" x-text="formatDate(row.created_at)"></td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    <?= view('layouts/partials/remote_pagination') ?>
</section>
