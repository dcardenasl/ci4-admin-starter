<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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

<?php if (! empty($metrics['users_by_role'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.usersByRole') ?></h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2 pr-4"><?= lang('Metrics.role') ?></th>
                        <th class="py-2 pr-4"><?= lang('Metrics.count') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($metrics['users_by_role'] as $role => $count): ?>
                        <tr>
                            <td class="py-3 pr-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= role_badge((string) $role) ?>">
                                    <?= esc((string) $role) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-gray-900"><?= esc((string) $count) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if (! empty($metrics['users_by_status'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.usersByStatus') ?></h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2 pr-4"><?= lang('Metrics.status') ?></th>
                        <th class="py-2 pr-4"><?= lang('Metrics.count') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($metrics['users_by_status'] as $status => $count): ?>
                        <tr>
                            <td class="py-3 pr-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge((string) $status) ?>">
                                    <?= esc((string) $status) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-gray-900"><?= esc((string) $count) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if (! empty($metrics['recent_activity'])): ?>
    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Metrics.recentActivity') ?></h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2 pr-4"><?= lang('Audit.action') ?></th>
                        <th class="py-2 pr-4"><?= lang('Audit.user') ?></th>
                        <th class="py-2 pr-4"><?= lang('Audit.date') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($metrics['recent_activity'] as $activity): ?>
                        <tr>
                            <td class="py-3 pr-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= audit_action_badge($activity['action'] ?? '') ?>">
                                    <?= esc((string) ($activity['action'] ?? '-')) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-gray-800"><?= esc((string) ($activity['user_email'] ?? $activity['user_id'] ?? '-')) ?></td>
                            <td class="py-3 pr-4 text-gray-600"><?= esc(format_date($activity['created_at'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
