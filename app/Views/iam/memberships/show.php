<?php
$appUserMembership = $appUserMembership ?? [];
$allRoles = $allRoles ?? [];
$assignedRoleIds = $assignedRoleIds ?? [];
$assignedSet = array_flip(array_map('intval', $assignedRoleIds));
$assignedItems = array_values(array_filter($allRoles, static fn (array $r): bool => isset($assignedSet[(int) ($r['id'] ?? 0)])));
$availableItems = array_values(array_filter($allRoles, static fn (array $r): bool => ! isset($assignedSet[(int) ($r['id'] ?? 0)])));
?>
<div class="mb-4">
    <a href="<?= route_to('admin.iam.memberships') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Iam.app_user_memberships_title') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($appUserMembership)): ?>
    <?php $itemId = (string) ($appUserMembership['id'] ?? ''); ?>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Iam.app_user_memberships_details') ?></h3>
            <div class="flex items-center gap-2">
                <a href="<?= route_to('admin.iam.memberships.edit', $itemId) ?>" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                <form method="post" action="<?= route_to('admin.iam.memberships.delete', $itemId) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_delete')) ?>');">
                    <?= csrf_field() ?>
                    <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
                        <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                        <?= esc(lang('App.delete')) ?>
                    </button>
                </form>
            </div>
        </div>

        <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_user_id') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($appUserMembership['user_id'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_application_id') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($appUserMembership['application_id'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_status') ?></dt>
                <dd class="mt-1">
                    <span class="inline-flex rounded-full px-2 py-1 text-xs <?= status_badge((string) ($appUserMembership['status'] ?? '')) ?>">
                        <?= esc(localized_status((string) ($appUserMembership['status'] ?? '-'))) ?>
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('TableColumns.created_at') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($appUserMembership['created_at'] ?? '-')) ?></dd>
            </div>
        </dl>
    </section>

    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Iam.roles_assigned') ?></h3>

        <?php if ($assignedItems === []): ?>
            <p class="mt-3 text-sm text-gray-500"><?= lang('Iam.roles_none_assigned') ?></p>
        <?php else: ?>
            <ul class="mt-3 divide-y divide-gray-100 border border-gray-100 rounded-lg">
                <?php foreach ($assignedItems as $r): ?>
                    <?php $rid = (string) ($r['id'] ?? ''); ?>
                    <li class="flex items-center justify-between p-3 text-sm">
                        <div>
                            <span class="text-gray-900 font-medium"><?= esc((string) ($r['name'] ?? $r['code'] ?? '-')) ?></span>
                            <code class="ml-2 text-xs text-gray-500"><?= esc((string) ($r['code'] ?? '')) ?></code>
                        </div>
                        <form method="post" action="<?= route_to('admin.iam.memberships.roles.detach', $itemId, $rid) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_remove')) ?>');">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-xs text-red-600 hover:text-red-700"><?= lang('App.remove') ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($availableItems !== []): ?>
            <form method="post" action="<?= route_to('admin.iam.memberships.roles.attach', $itemId) ?>" class="mt-6 border-t border-gray-100 pt-4">
                <?= csrf_field() ?>
                <h4 class="text-sm font-medium text-gray-900 mb-2"><?= lang('Iam.roles_available') ?></h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-72 overflow-y-auto pr-1">
                    <?php foreach ($availableItems as $r): ?>
                        <label class="flex items-start gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="role_ids[]" value="<?= esc((string) ($r['id'] ?? '')) ?>" class="mt-1">
                            <span>
                                <span class="text-gray-900 font-medium"><?= esc((string) ($r['name'] ?? $r['code'] ?? '-')) ?></span>
                                <code class="block text-xs text-gray-500"><?= esc((string) ($r['code'] ?? '')) ?></code>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="<?= esc(action_button_class()) ?>"><?= lang('Iam.roles_attach') ?></button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
