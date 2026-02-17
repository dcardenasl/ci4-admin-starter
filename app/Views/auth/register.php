<form method="post" action="<?= site_url('register') ?>" class="space-y-4" x-data="{ password: '' }">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700" for="first_name"><?= lang('Auth.firstNameLabel') ?></label>
            <input id="first_name" name="first_name" type="text" value="<?= old('first_name') ?>" autocomplete="given-name" required
                class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('first_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
            <?= render_field_error('first_name') ?>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="last_name"><?= lang('Auth.lastNameLabel') ?></label>
            <input id="last_name" name="last_name" type="text" value="<?= old('last_name') ?>" autocomplete="family-name" required
                class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('last_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
            <?= render_field_error('last_name') ?>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="email"><?= lang('Auth.emailLabel') ?></label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('email') ?>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password"><?= lang('Auth.passwordLabel') ?></label>
        <input id="password" name="password" type="password" x-model="password" autocomplete="new-password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('password') ?>
        <p class="mt-1 text-xs"
           :class="password.length >= 12 ? 'text-green-700' : password.length >= 8 ? 'text-yellow-700' : 'text-red-700'"
           x-text="password.length >= 12 ? '<?= lang('Auth.passwordStrong') ?>' : password.length >= 8 ? '<?= lang('Auth.passwordMedium') ?>' : '<?= lang('Auth.passwordWeak') ?>'"></p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="password_confirmation"><?= lang('Auth.confirmPassword') ?></label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('password_confirmation') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('password_confirmation') ?>
    </div>

    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Auth.registerButton') ?></button>
</form>

<div class="mt-4 text-sm text-gray-600 text-center">
    <a href="<?= site_url('login') ?>" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.hasAccount') ?></a>
</div>
