<?php
$memberships = $memberships ?? [];
$user = $user ?? [];
$canModifyTarget = ! empty($user) ? can_act_on_user($user) : false;
?>
<div class="mb-4">
    <a href="<?= route_to('admin.users') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Users.back_to_list') ?></a>
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
                    <?php if ($canModifyTarget): ?>
                        <a href="<?= route_to('admin.users.edit', $uid) ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.edit') ?></a>
                        <form method="post" action="<?= route_to('admin.users.delete', $uid) ?>" x-data @submit.prevent="$store.confirm.show('<?= esc(lang('Users.confirm_delete'), 'js') ?>', () => $el.submit())">
                            <?= csrf_field() ?>
                            <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"><?= lang('App.delete') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500"><?= lang('Users.first_name') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($user['first_name'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.last_name') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($user['last_name'] ?? '-')) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.email') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) ($user['email'] ?? '-')) ?></dd>
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
                    <dt class="text-gray-500"><?= lang('Users.email_verified') ?></dt>
                    <dd class="mt-1 text-gray-900">
                        <?php if (! empty($user['email_verified_at'])): ?>
                            <?= esc(format_date($user['email_verified_at'])) ?>
                        <?php else: ?>
                            <?= is_email_verified($user) ? lang('App.yes') : lang('App.no') ?>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.created_at') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($user['created_at'] ?? null)) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500"><?= lang('Users.updated_at') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(format_date($user['updated_at'] ?? null)) ?></dd>
                </div>
            </dl>

            <div class="mt-6 border-t border-gray-100 pt-4">
                <h4 class="text-sm font-semibold text-gray-900"><?= lang('Users.apps_and_roles') ?></h4>
                <?php if ($memberships === []): ?>
                    <p class="mt-2 text-sm text-gray-500"><?= lang('Users.no_memberships') ?></p>
                <?php else: ?>
                    <ul class="mt-3 divide-y divide-gray-100 border border-gray-100 rounded-lg">
                        <?php foreach ($memberships as $membership): ?>
                            <?php $mid = (string) ($membership['id'] ?? ''); ?>
                            <li class="flex items-center justify-between p-3 text-sm">
                                <div>
                                    <span class="text-gray-900 font-medium">App #<?= esc((string) ($membership['application_id'] ?? '-')) ?></span>
                                    <span class="ml-2 inline-flex rounded-full px-2 py-1 text-xs <?= status_badge((string) ($membership['status'] ?? '')) ?>">
                                        <?= esc(localized_status((string) ($membership['status'] ?? '-'))) ?>
                                    </span>
                                </div>
                                <?php if ($canModifyTarget): ?>
                                    <a href="<?= route_to('admin.iam.memberships.show', $mid) ?>" class="text-xs text-brand-600 hover:text-brand-700">
                                        <?= lang('Users.manage_roles') ?> &rarr;
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Users.quick_actions') ?></h3>
            <div class="mt-4 space-y-3">
                <?php if (($user['status'] ?? '') === 'pending_approval'): ?>
                    <form method="post" action="<?= route_to('admin.users.approve', $uid) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"><?= lang('Users.approve') ?></button>
                    </form>
                <?php endif; ?>
                <?php if ($canModifyTarget): ?>
                    <a href="<?= route_to('admin.users.edit', $uid) ?>" class="block w-full text-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('App.edit') ?></a>
                <?php endif; ?>
                <a href="<?= route_to('admin.audit') . '?user_id=' . rawurlencode($uid) ?>" class="block w-full text-center rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><?= lang('Users.view_audit') ?></a>
            </div>
        </section>
    </div>
<?php endif; ?>
