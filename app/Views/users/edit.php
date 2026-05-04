<?php $uid = (string) ($editUser['id'] ?? ''); ?>

<div class="mb-4">
    <a href="<?= route_to('admin.users.show', $uid) ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Users.back_to_details') ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-2xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Users.edit_user') ?></h3>

    <form method="post" action="<?= route_to('admin.users.update', $uid) ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="original_email" value="<?= esc(old('original_email', $editUser['email'] ?? '')) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="first_name"><?= lang('Users.first_name') ?></label>
                <input id="first_name" name="first_name" type="text" value="<?= esc(old('first_name', $editUser['first_name'] ?? '')) ?>" required
                    class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('first_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
                <?= render_field_error('first_name') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="last_name"><?= lang('Users.last_name') ?></label>
                <input id="last_name" name="last_name" type="text" value="<?= esc(old('last_name', $editUser['last_name'] ?? '')) ?>" required
                    class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('last_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
                <?= render_field_error('last_name') ?>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="email"><?= lang('Users.email') ?></label>
            <input id="email" name="email" type="email" value="<?= esc(old('email', $editUser['email'] ?? '')) ?>" required
                class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
            <?= render_field_error('email') ?>
        </div>

        <div>
            <span class="block text-sm font-medium text-gray-700"><?= lang('Users.roles') ?></span>
            <p class="text-xs text-gray-500 mt-1"><?= lang('Users.roles_help_edit') ?></p>
            <?php
                $oldRoleIds = (array) old('role_ids', $currentRoleIds ?? []);
$oldRoleIdsStr = array_map('strval', $oldRoleIds);
$assignableIds = array_map(static fn ($r) => (string) ($r['id'] ?? ''), $assignableRoles ?? []);
$hiddenLockedRoleIds = array_diff($oldRoleIdsStr, $assignableIds);
?>
            <?php if (! empty($assignableRoles)): ?>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($assignableRoles as $role): ?>
                        <label class="inline-flex items-start gap-2 text-sm rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50">
                            <input type="checkbox" name="role_ids[]" value="<?= (int) $role['id'] ?>"
                                <?= in_array((string) $role['id'], $oldRoleIdsStr, true) ? 'checked' : '' ?>
                                class="mt-1 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="font-medium text-gray-900"><?= esc($role['name']) ?></span>
                                <span class="block text-xs text-gray-500"><?= esc($role['code']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mt-2 text-sm text-gray-500 italic"><?= lang('Users.roles_none_assignable') ?></p>
            <?php endif; ?>

            <?php foreach ($hiddenLockedRoleIds as $lockedId): ?>
                <input type="hidden" name="role_ids[]" value="<?= (int) $lockedId ?>">
            <?php endforeach; ?>
            <?php if ($hiddenLockedRoleIds !== []): ?>
                <p class="mt-2 text-xs text-amber-700"><?= lang('Users.roles_some_locked') ?></p>
            <?php endif; ?>
            <?= render_field_error('role_ids') ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700"><?= lang('App.save') ?></button>
            <a href="<?= route_to('admin.users.show', $uid) ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.cancel') ?></a>
        </div>
    </form>
</section>
