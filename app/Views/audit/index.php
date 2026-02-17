<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h3 class="text-lg font-semibold text-gray-900"><?= esc($title) ?></h3>
    </div>

    <form method="get" action="<?= site_url('admin/audit') ?>" class="mt-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1"><?= lang('App.search') ?></label>
            <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Audit.searchPlaceholder') ?>"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1"><?= lang('Audit.action') ?></label>
            <select name="action" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value=""><?= lang('Audit.allActions') ?></option>
                <option value="create" <?= request()->getGet('action') === 'create' ? 'selected' : '' ?>>Create</option>
                <option value="update" <?= request()->getGet('action') === 'update' ? 'selected' : '' ?>>Update</option>
                <option value="delete" <?= request()->getGet('action') === 'delete' ? 'selected' : '' ?>>Delete</option>
                <option value="login" <?= request()->getGet('action') === 'login' ? 'selected' : '' ?>>Login</option>
                <option value="logout" <?= request()->getGet('action') === 'logout' ? 'selected' : '' ?>>Logout</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.search') ?></button>
    </form>

    <?php if (empty($logs)): ?>
        <p class="mt-6 text-sm text-gray-500"><?= lang('Audit.noLogs') ?></p>
    <?php else: ?>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2 pr-4">ID</th>
                        <th class="py-2 pr-4"><?= lang('Audit.user') ?></th>
                        <th class="py-2 pr-4"><?= lang('Audit.action') ?></th>
                        <th class="py-2 pr-4"><?= lang('Audit.entity') ?></th>
                        <th class="py-2 pr-4"><?= lang('Audit.ipAddress') ?></th>
                        <th class="py-2 pr-4"><?= lang('Audit.date') ?></th>
                        <th class="py-2 pr-4"><?= lang('Audit.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($logs as $log): ?>
                        <?php $logId = (string) ($log['id'] ?? ''); ?>
                        <tr>
                            <td class="py-3 pr-4 text-gray-600"><?= esc($logId) ?></td>
                            <td class="py-3 pr-4 text-gray-800"><?= esc((string) ($log['user_email'] ?? $log['user_id'] ?? '-')) ?></td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= audit_action_badge($log['action'] ?? '') ?>">
                                    <?= esc((string) ($log['action'] ?? '-')) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-gray-600">
                                <?= esc((string) ($log['entity_type'] ?? '-')) ?>
                                <?php if (! empty($log['entity_id'])): ?>
                                    <span class="text-gray-400">#<?= esc((string) $log['entity_id']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 pr-4 text-gray-500 font-mono text-xs"><?= esc((string) ($log['ip_address'] ?? '-')) ?></td>
                            <td class="py-3 pr-4 text-gray-600"><?= esc(format_date($log['created_at'] ?? null)) ?></td>
                            <td class="py-3 pr-4">
                                <a href="<?= site_url('admin/audit/' . esc($logId, 'url')) ?>" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"><?= lang('Audit.view') ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pagination)): ?>
            <?= $this->include('layouts/partials/pagination', ['pagination' => $pagination, 'paginationUrl' => site_url('admin/audit')]) ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
