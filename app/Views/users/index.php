<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Users.title') ?></h3>
        <a href="<?= site_url('admin/users/create') ?>" class="inline-flex items-center rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700">
            <?= lang('Users.create') ?>
        </a>
    </div>

    <form method="get" action="<?= site_url('admin/users') ?>" class="mt-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1"><?= lang('App.search') ?></label>
            <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Users.searchPlaceholder') ?>"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1"><?= lang('Users.status') ?></label>
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value=""><?= lang('Users.allStatuses') ?></option>
                <option value="active" <?= request()->getGet('status') === 'active' ? 'selected' : '' ?>><?= lang('App.active') ?></option>
                <option value="pending" <?= request()->getGet('status') === 'pending' ? 'selected' : '' ?>><?= lang('App.pending') ?></option>
                <option value="suspended" <?= request()->getGet('status') === 'suspended' ? 'selected' : '' ?>><?= lang('Users.suspended') ?></option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1"><?= lang('Users.role') ?></label>
            <select name="role" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value=""><?= lang('Users.allRoles') ?></option>
                <option value="admin" <?= request()->getGet('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="user" <?= request()->getGet('role') === 'user' ? 'selected' : '' ?>>User</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.search') ?></button>
    </form>

    <?php if (empty($users)): ?>
        <p class="mt-6 text-sm text-gray-500"><?= lang('Users.noUsers') ?></p>
    <?php else: ?>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-2 pr-4"><?= lang('Users.name') ?></th>
                        <th class="py-2 pr-4"><?= lang('Users.email') ?></th>
                        <th class="py-2 pr-4"><?= lang('Users.role') ?></th>
                        <th class="py-2 pr-4"><?= lang('Users.status') ?></th>
                        <th class="py-2 pr-4"><?= lang('Users.date') ?></th>
                        <th class="py-2 pr-4"><?= lang('Users.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($users as $u): ?>
                        <?php $uid = (string) ($u['id'] ?? ''); ?>
                        <tr>
                            <td class="py-3 pr-4 text-gray-800"><?= esc(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></td>
                            <td class="py-3 pr-4 text-gray-600"><?= esc((string) ($u['email'] ?? '')) ?></td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= role_badge($u['role'] ?? 'user') ?>">
                                    <?= esc((string) ($u['role'] ?? 'user')) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge($u['status'] ?? '') ?>">
                                    <?= esc((string) ($u['status'] ?? '-')) ?>
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-gray-600"><?= esc(format_date($u['created_at'] ?? null)) ?></td>
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2">
                                    <a href="<?= site_url('admin/users/' . esc($uid, 'url')) ?>" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"><?= lang('Users.view') ?></a>
                                    <a href="<?= site_url('admin/users/' . esc($uid, 'url') . '/edit') ?>" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50"><?= lang('App.edit') ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pagination)): ?>
            <?= $this->include('layouts/partials/pagination', ['pagination' => $pagination, 'paginationUrl' => site_url('admin/users')]) ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
