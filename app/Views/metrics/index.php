<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <?= view('layouts/partials/table_toolbar', [
        'title' => lang('Metrics.title'),
    ]) ?>

    <?= view('layouts/partials/filter_panel', [
        'actionUrl' => site_url('admin/metrics'),
        'clearUrl' => site_url('admin/metrics'),
        'hasFilters' => $hasFilters ?? false,
        'filterDefaults' => $defaultFilters ?? [],
        'fieldsView' => 'metrics/partials/filters',
        'fieldsData' => ['filters' => $filters],
        'submitLabel' => lang('Metrics.applyFilters'),
    ]) ?>
</section>

<section class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php if (isset($metrics['requestStats'])): ?>
        <?php $stats = $metrics['requestStats']; ?>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.totalRequests') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['totalRequests'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.avgResponseTime') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['avgResponseTimeMs'] ?? 0)) ?> ms</p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.availability') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['availabilityPercent'] ?? 0)) ?>%</p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.successRequests') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($stats['successfulRequests'] ?? 0)) ?></p>
        </article>
    <?php else: ?>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.totalUsers') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['totalUsers'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.activeUsers') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['activeUsers'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.totalFiles') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['totalFiles'] ?? 0)) ?></p>
        </article>
        <article class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <p class="text-sm text-gray-500"><?= lang('Metrics.storageUsed') ?></p>
            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= esc((string) ($metrics['storageUsed'] ?? '0 B')) ?></p>
        </article>
    <?php endif; ?>
</section>

<?php if (! empty($metrics['slowRequests'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.slowRequests') ?></h3>
        <div class="<?= esc(table_wrapper_class()) ?>">
            <div class="<?= esc(table_scroll_class()) ?>">
            <table class="<?= esc(table_class()) ?>">
                <thead class="<?= esc(table_head_class()) ?>">
                    <tr>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.method') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.path') ?></th>
                        <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.duration') ?></th>
                    </tr>
                </thead>
                <tbody class="<?= esc(table_body_class()) ?>">
                    <?php foreach ($metrics['slowRequests'] as $req): ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>"><?= esc($req['method'] ?? '-') ?></td>
                            <td class="<?= esc(table_td_class()) ?>"><?= esc($req['path'] ?? '-') ?></td>
                            <td class="<?= esc(table_td_class('primary')) ?>"><?= esc($req['durationMs'] ?? 0) ?> ms</td>
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
                    <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.period') ?></th>
                    <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.value') ?></th>
                </tr>
            </thead>
            <tbody class="<?= esc(table_body_class()) ?>">
                <?php foreach ($timeseries as $point): ?>
                    <tr class="<?= esc(table_row_class()) ?>">
                                                    <td class="<?= esc(table_td_class()) ?>">
                                                        <?= esc((string) ($point['period'] ?? $point['date'] ?? $point['label'] ?? $point['timestamp'] ?? $point['groupBy'] ?? '-')) ?>
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
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.sloMetric') ?></th>
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.sloTarget') ?></th>
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.sloCurrent') ?></th>
                                                <th class="<?= esc(table_th_class()) ?>"><?= lang('Metrics.errorBudget') ?></th>
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
                        
<?php if (! empty($metrics['usersByRole'])): ?>
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
                    <?php foreach ($metrics['usersByRole'] as $role => $count): ?>
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

<?php if (! empty($metrics['usersByStatus'])): ?>
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
                    <?php foreach ($metrics['usersByStatus'] as $status => $count): ?>
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

<?php if (! empty($metrics['recentActivity'])): ?>
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
                    <?php foreach ($metrics['recentActivity'] as $activity): ?>
                        <tr class="<?= esc(table_row_class()) ?>">
                            <td class="<?= esc(table_td_class()) ?>">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= audit_action_badge($activity['action'] ?? '') ?>">
                                    <?= esc((string) ($activity['action'] ?? '-')) ?>
                                </span>
                            </td>
                            <td class="<?= esc(table_td_class('primary')) ?>"><?= esc((string) ($activity['userEmail'] ?? $activity['userId'] ?? '-')) ?></td>
                            <td class="<?= esc(table_td_class('muted')) ?>"><?= esc(format_date($activity['createdAt'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </section>
<?php endif; ?>
