<form method="post" action="<?= site_url('reset-password') ?>" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= esc($token ?? old('token')) ?>">

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password"><?= lang('Auth.newPassword') ?></label>
        <input id="password" name="password" type="password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('password') ?>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password_confirmation"><?= lang('Auth.confirmPassword') ?></label>
        <input id="password_confirmation" name="password_confirmation" type="password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('password_confirmation') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('password_confirmation') ?>
    </div>

    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 hover:bg-brand-700"><?= lang('Auth.resetButton') ?></button>
</form>
