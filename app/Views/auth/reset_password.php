<form method="post" action="<?= site_url('reset-password') ?>" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= esc($token ?? old('token')) ?>">
    <input type="hidden" name="email" value="<?= esc($email ?? old('email')) ?>">

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password"><?= lang('Auth.newPassword') ?></label>
        <input id="password" name="password" type="password" autocomplete="new-password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('password') ?>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="passwordConfirmation"><?= lang('Auth.confirmPassword') ?></label>
        <input id="passwordConfirmation" name="passwordConfirmation" type="password" autocomplete="new-password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('passwordConfirmation') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('passwordConfirmation') ?>
    </div>

    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Auth.resetButton') ?></button>
</form>
