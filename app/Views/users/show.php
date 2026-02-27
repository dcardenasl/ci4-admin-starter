<div class="mb-4">
    <a href="<?= site_url('admin/users') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Users.backToList') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($user)): ?>
    <?php $uid = (string) ($user['id'] ?? ''); ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900"><?= lang('Users.details') ?></h3>
                <div class="flex items-center gap-2">
                    <a href="<?= site_url('admin/users/' . esc($uid, 'url') . '/edit') ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.edit') ?></a>
                    <form method="post" action="<?= site_url('admin/users/' . esc($uid, 'url') . '/delete') ?>" onsubmit="return confirm('<?= lang('Users.confirmDelete') ?>');">
                        <?= csrf_field() ?>
                        <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"><?= lang('App.delete') ?></button>
                    </form>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500"><?= lang('Users.firstName') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($user['firstName'] ?? $user['first_name'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.lastName') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($user['lastName'] ?? $user['last_name'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.email') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($user['email'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.role') ?></dt>
                    <dd class="mt-1">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs <?= role_badge($user['role'] ?? 'user') ?>">
                            <?= esc(localized_role((string) ($user['role'] ?? 'user'))) ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.status') ?></dt>
                    <dd class="mt-1">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge($user['status'] ?? '') ?>">
                            <?= esc(localized_status((string) ($user['status'] ?? '-'))) ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.emailVerified') ?></dt>
                    <dd class="mt-1 text-gray-900">
                        <?php if (! empty($user['emailVerifiedAt'])): ?>
                            <?= esc(format_date($user['emailVerifiedAt'])) ?>
                        <?php elseif (! empty($user['verifiedAt'])): ?>
                            <?= esc(format_date($user['verifiedAt'])) ?>
                        <?php elseif (! empty($user['email_verified_at'])): ?>
                            <?= esc(format_date($user['email_verified_at'])) ?>
                        <?php else: ?>
                            <?= is_email_verified($user) ? lang('App.yes') : lang('App.no') ?>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.createdAt') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($user['createdAt'] ?? $user['created_at'] ?? null)) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.updatedAt') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($user['updatedAt'] ?? $user['updated_at'] ?? null)) ?></dd>
                </div>
            </dl>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Users.quickActions') ?></h3>
            <div class="mt-4 space-y-3">
                <?php if (($user['status'] ?? '') === 'pending_approval'): ?>
                    <form method="post" action="<?= site_url('admin/users/' . esc($uid, 'url') . '/approve') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"><?= lang('Users.approve') ?></button>
                    </form>
                <?php endif; ?>
                <a href="<?= site_url('admin/users/' . esc($uid, 'url') . '/edit') ?>" class="block w-full text-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.edit') ?></a>
                <a href="<?= site_url('admin/audit?user_id=' . esc($uid, 'url')) ?>" class="block w-full text-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('Users.viewAudit') ?></a>
            </div>
        </section>
    </div>
<?php endif; ?>
