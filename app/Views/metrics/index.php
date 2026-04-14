<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('Metrics.title'),
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => route_to('admin.metrics'),
        'clearUrl' => route_to('admin.metrics'),
        'hasFilters' => $hasFilters ?? false,
        'filterDefaults' => $defaultFilters ?? [],
        'fieldsView' => 'metrics/partials/filters',
        'fieldsData' => [
            'filters' => $filters,
            'periodOptions' => $periodOptions ?? [],
        ],
        'submitLabel' => lang('Metrics.apply_filters'),
    ]) ?>
</section>

<section class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
<?php if (isset($metrics['request_stats'])): ?>
        <?php $stats = $metrics['request_stats']; ?>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.total_requests') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['total_requests'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.avg_response_time') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['avg_response_time_ms'] ?? 0)) ?> ms</p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.availability') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['availability_percent'] ?? 0)) ?>%</p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.success_requests') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['successful_requests'] ?? 0)) ?></p>
        </article>
    <?php else: ?>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.total_users') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['total_users'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.active_users') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['active_users'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.total_files') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['total_files'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.storage_used') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['storage_used'] ?? '0 B')) ?></p>
        </article>
    <?php endif; ?>
</section>

<?php if (! empty($metrics['slow_requests'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.slow_requests') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.method') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.path') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.duration_ms') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <?php foreach ($metrics['slow_requests'] as $req): ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>"><?= esc($req['method'] ?? '-') ?></td>
                            <td class="<?= esc(table_td_class()) ?>"><?= esc($req['path'] ?? '-') ?></td>
                            <td class="<?= esc(table_td_class('primary')) ?>"><?= esc($req['duration_ms'] ?? 0) ?> ms</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </section>
<?php endif; ?>


<?php if (! empty($timeseries)): ?>
<section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.trends') ?></h3>
    <div class="<?= esc(table_wrapper_class()) ?>">
        <div class="<?= esc(table_scroll_class()) ?>">
        <table class="<?= esc(table_class()) ?>">
            <thead class="<?= esc(table_head_class()) ?>">
                <tr>
                    <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.period') ?></th>
                    <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.value') ?></th>
                </tr>
            </thead>
            <tbody class="<?= esc(table_body_class()) ?>">
                <?php foreach ($timeseries as $point): ?>
                    <tr class="<?= esc(table_row_class()) ?>">
                                                    <td class="<?= esc(table_td_class()) ?>">
                                                        <?= esc((string) ($point['period'] ?? $point['date'] ?? $point['label'] ?? $point['timestamp'] ?? $point['group_by'] ?? '-')) ?>
                                                    </td>
                                                    <td class="<?= esc(table_td_class('primary')) ?>">
                                                        <?= esc((string) ($point['value'] ?? $point['count'] ?? $point['total'] ?? $point['avg'] ?? '-')) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>
                        
                        <?php if (! empty($metrics['slo'])): ?>
                            <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                                <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.slo') ?></h3>
                                <div class="<?= esc(table_wrapper_class()) ?>">
                                    <div class="<?= esc(table_scroll_class()) ?>">
                                    <table class="<?= esc(table_class()) ?>">
                                        <thead class="<?= esc(table_head_class()) ?>">
                                            <tr>
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.slo_metric') ?></th>
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.slo_target') ?></th>
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.slo_current') ?></th>
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.error_budget') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody class="<?= esc(table_body_class()) ?>">
                                            <?php
                                                $slos = [];
                            if (isset($metrics['slo']['availability'])) {
                                $slos['Availability'] = $metrics['slo']['availability'];
                            }
                            if (isset($metrics['slo']['uptime'])) {
                                $slos['Uptime'] = $metrics['slo']['uptime'];
                            }
                            if (isset($metrics['slo']['latency'])) {
                                $slos['Latency'] = $metrics['slo']['latency'];
                            }

                            if (empty($slos)) {
                                foreach ($metrics['slo'] as $k => $v) {
                                    if (is_array($v)) {
                                        $slos[ucfirst((string) $k)] = $v;
                                    }
                                }
                            }
                            ?>
                                            <?php foreach ($slos as $name => $slo): ?>
                                                <tr class="<?= esc(table_row_class()) ?>">
                                                    <td class="<?= esc(table_td_class()) ?>"><?= esc($name) ?></td>
                                                    <td class="<?= esc(table_td_class()) ?>"><?= esc((string) ($slo['target'] ?? $slo['goal'] ?? '-')) ?>%</td>
                                                    <td class="<?= esc(table_td_class('primary')) ?>"><?= esc((string) ($slo['current'] ?? $slo['value'] ?? '-')) ?>%</td>
                                                    <td class="<?= esc(table_td_class()) ?>">
                                                        <?php
                                            $remaining = $slo['remaining'] ?? $slo['error_budget'] ?? $slo['budget'] ?? null;
                                                ?>
                                                        <?php if ($remaining !== null): ?>
                                                            <span class="px-2 py-1 rounded text-xs <?= (float) $remaining > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                                                <?= esc((string) $remaining) ?>%
                                                            </span>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            </section>
                        <?php endif; ?>
                        
<?php if (! empty($metrics['users_by_role'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.users_by_role') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.role') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.count') ?></th>
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
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.users_by_status') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.status') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.count') ?></th>
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
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.recent_activity') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.action') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.user') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('TableColumns.date') ?></th>
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
