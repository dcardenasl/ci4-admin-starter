<form method="post" action="<?= site_url('forgot-password') ?>" class="space-y-4">
    <?= csrf_field() ?>
    <div>
        <label class="block text-sm font-medium text-gray-700" for="email"><?= lang('Auth.emailLabel') ?></label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required
            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 <?= has_field_error('email') ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-brand-500 focus:ring-brand-500' ?>">
        <?= render_field_error('email') ?>
    </div>
    <button type="submit" class="w-full rounded-lg bg-brand-600 text-white px-4 py-2 font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Auth.sendLink') ?></button>
</form>

<div class="mt-4 text-sm text-center">
    <a href="<?= site_url('login') ?>" class="text-brand-600 hover:text-brand-700"><?= lang('Auth.backToLogin') ?></a>
</div>
