<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('Metrics.title'),
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => site_url('admin/metrics'),
        'clearUrl' => site_url('admin/metrics'),
        'hasFilters' => request()->getGet() !== [],
        'fieldsView' => 'metrics/partials/filters',
        'fieldsData' => ['filters' => $filters],
        'submitLabel' => lang('Metrics.applyFilters'),
    ]) ?>
</section>

<section class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Metrics.totalUsers') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['total_users'] ?? $metrics['users'] ?? 0)) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Metrics.activeUsers') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['active_users'] ?? 0)) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Metrics.totalFiles') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['total_files'] ?? $metrics['files'] ?? 0)) ?></p>
    </article>
    <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500"><?= lang('Metrics.storageUsed') ?></p>
        <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['storage_used'] ?? $metrics['storage'] ?? '0 B')) ?></p>
    </article>
</section>

<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.trends') ?></h3>
    <?php if (empty($timeseries)): ?>
        <p class="mt-3 text-sm text-gray-500"><?= lang('Metrics.noTrendData') ?></p>
    <?php else: ?>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.period') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.value') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <?php foreach ($timeseries as $point): ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>"><?= esc((string) ($point['period'] ?? $point['date'] ?? $point['label'] ?? '-')) ?></td>
                            <td class="<?= esc(table_td_class('primary')) ?>"><?= esc((string) ($point['value'] ?? $point['count'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php if (! empty($metrics['users_by_role'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.usersByRole') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.role') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.count') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <?php foreach ($metrics['users_by_role'] as $role => $count): ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= role_badge((string) $role) ?>">
                                    <?= esc((string) $role) ?>
                                </span>
                            </td>
                            <td class="<?= esc(table_td_class('primary')) ?>"><?= esc((string) $count) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (! empty($metrics['users_by_status'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.usersByStatus') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.status') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.count') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <?php foreach ($metrics['users_by_status'] as $status => $count): ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge((string) $status) ?>">
                                    <?= esc((string) $status) ?>
                                </span>
                            </td>
                            <td class="<?= esc(table_td_class('primary')) ?>"><?= esc((string) $count) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (! empty($metrics['recent_activity'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.recentActivity') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Audit.action') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Audit.user') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Audit.date') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <?php foreach ($metrics['recent_activity'] as $activity): ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= audit_action_badge($activity['action'] ?? '') ?>">
                                    <?= esc((string) ($activity['action'] ?? '-')) ?>
                                </span>
                            </td>
                            <td class="<?= esc(table_td_class('primary')) ?>"><?= esc((string) ($activity['user_email'] ?? $activity['user_id'] ?? '-')) ?></td>
                            <td class="<?= esc(table_td_class('muted')) ?>"><?= esc(format_date($activity['created_at'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </section>
<?php endif; ?>
