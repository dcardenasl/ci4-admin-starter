<?php $uid = (string) ($editUser['id'] ?? ''); ?>

<div class="mb-4">
    <a href="<?= site_url('admin/users/' . esc($uid, 'url')) ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Users.backToDetails') ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-2xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= lang('Users.editUser') ?></h3>

    <form method="post" action="<?= site_url('admin/users/' . esc($uid, 'url')) ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="original_email" value="<?= esc(old('original_email', $editUser['email'] ?? '')) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="first_name"><?= lang('Users.firstName') ?></label>
                <input id="first_name" name="first_name" type="text" value="<?= esc(old('first_name', $editUser['first_name'] ?? '')) ?>" required
                    class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('first_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
                <?= render_field_error('first_name') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="last_name"><?= lang('Users.lastName') ?></label>
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
            <label class="block text-sm font-medium text-gray-700" for="role"><?= lang('Users.role') ?></label>
            <select id="role" name="role" required
                class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('role') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
                <option value="user" <?= old('role', $editUser['role'] ?? 'user') === 'user' ? 'selected' : '' ?>><?= lang('Users.userRole') ?></option>
                <option value="admin" <?= old('role', $editUser['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>><?= lang('Users.adminRole') ?></option>
            </select>
            <?= render_field_error('role') ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm hover:bg-brand-700"><?= lang('App.save') ?></button>
            <a href="<?= site_url('admin/users/' . esc($uid, 'url')) ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.cancel') ?></a>
        </div>
    </form>
</section>
