<form method="post" action="<?= site_url('login') ?>" class="space-y-4">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="email"><?= lang('Auth.emailLabel') ?></label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" required
            class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('email') ?>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="password"><?= lang('Auth.passwordLabel') ?></label>
        <input id="password" name="password" type="password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 <?= has_field_error('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('password') ?>
    </div>
    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 hover:bg-brand-700"><?= lang('Auth.loginButton') ?></button>
</form>

<div class="mt-4 text-sm text-gray-600 flex items-center justify-between">
    <a href="<?= site_url('forgot-password') ?>" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.forgotPassword') ?></a>
    <a href="<?= site_url('register') ?>" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.createAccount') ?></a>
</div>
