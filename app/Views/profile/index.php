<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= $isAdmin ? lang('Profile.personal_info') : lang('Profile.personal_info_readonly') ?></h3>
        <?php if ($isAdmin): ?>
            <form method="post" action="<?= route_to('profile.update') ?>" class="mt-4 space-y-4">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="first_name"><?= lang('Profile.first_name_label') ?></label>
                        <input id="first_name" name="first_name" type="text" value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>" autocomplete="given-name" required
                            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 border-gray-300 focus:border-brand-500 focus:ring-brand-500 <?= field_error_class('first_name') ?>">
                        <?= render_field_error('first_name') ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="last_name"><?= lang('Profile.last_name_label') ?></label>
                        <input id="last_name" name="last_name" type="text" value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>" autocomplete="family-name" required
                            class="mt-1 w-full rounded-lg border px-3 py-2 focus-visible:outline-none focus-visible:ring-2 border-gray-300 focus:border-brand-500 focus:ring-brand-500 <?= field_error_class('last_name') ?>">
                        <?= render_field_error('last_name') ?>
                    </div>
                </div>
                <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Profile.save_changes') ?></button>
            </form>
        <?php else: ?>
            <p class="mt-3 text-sm text-gray-600"><?= lang('Profile.readonly_help') ?></p>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?= lang('Profile.first_name_label') ?></label>
                    <p class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800"><?= esc($user['first_name'] ?? '') ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?= lang('Profile.last_name_label') ?></label>
                    <p class="mt-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800"><?= esc($user['last_name'] ?? '') ?></p>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500"><?= lang('Profile.edit_requires_admin') ?></p>
        <?php endif; ?>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Profile.security') ?></h3>
        <p class="mt-3 text-sm text-gray-600"><?= lang('Profile.password_reset_help') ?></p>
        <form method="post" action="<?= site_url('profile/request-password-reset') ?>" class="mt-4">
            <?= csrf_field() ?>
            <button type="submit" class="rounded-lg bg-brand-600 text-white px-4 py-2 text-sm font-medium hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"><?= lang('Profile.send_password_reset') ?></button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <h4 class="font-medium text-gray-900"><?= lang('Profile.email_verification') ?></h4>
            <?php $email_verified = is_email_verified(is_array($user) ? $user : []); ?>
            <p class="mt-1 text-sm text-gray-600">
                <?= lang('Profile.status') ?>:
                <span class="inline-flex rounded-full px-2 py-1 text-xs <?= $email_verified ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                    <?= $email_verified ? lang('Profile.verified') : lang('Profile.pending') ?>
                </span>
            </p>
            <?php if (! $email_verified): ?>
                <form method="post" action="<?= site_url('profile/resend-verification') ?>" class="mt-3">
                    <?= csrf_field() ?>
                    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        <?= lang('Profile.resend_verification') ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>
