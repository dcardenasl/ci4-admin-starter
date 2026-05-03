<?php
$role = $role ?? [];
$allPermissions = $allPermissions ?? [];
$assignedPermissionIds = $assignedPermissionIds ?? [];
$assignedSet = array_flip(array_map('intval', $assignedPermissionIds));
$assignedItems = array_values(array_filter($allPermissions, static fn (array $p): bool => isset($assignedSet[(int) ($p['id'] ?? 0)])));
$availableItems = array_values(array_filter($allPermissions, static fn (array $p): bool => ! isset($assignedSet[(int) ($p['id'] ?? 0)])));
?>
<div class="mb-4">
    <a href="<?= route_to('admin.iam.roles') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Iam.roles_title') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($role)): ?>
    <?php $itemId = (string) ($role['id'] ?? ''); ?>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Iam.roles_details') ?></h3>
            <div class="flex items-center gap-2">
                <a href="<?= route_to('admin.iam.roles.edit', $itemId) ?>" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                <?php if (empty($role['is_system'])): ?>
                    <form method="post" action="<?= route_to('admin.iam.roles.delete', $itemId) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_delete')) ?>');">
                        <?= csrf_field() ?>
                        <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
                            <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                            <?= esc(lang('App.delete')) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_code') ?></dt>
                <dd class="mt-1 text-gray-900 font-mono"><?= esc((string) ($role['code'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_name') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($role['name'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_application') ?></dt>
                <dd class="mt-1 text-gray-900">
                    <?php if (! empty($role['application_name'])): ?>
                        <?= esc((string) $role['application_name']) ?>
                        <span class="text-gray-500 text-xs">(#<?= (int) $role['application_id'] ?>)</span>
                    <?php elseif (! empty($role['application_id'])): ?>
                        #<?= (int) $role['application_id'] ?>
                    <?php else: ?>
                        <span class="text-gray-500"><?= esc(lang('Iam.role_global_label')) ?></span>
                    <?php endif; ?>
                </dd>
            </div>
            <?php if (! empty($role['is_system'])): ?>
                <div>
                    <dt class="text-gray-500"><?= esc(lang('App.warning')) ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc(lang('Iam.system_role_notice')) ?></dd>
                </div>
            <?php endif; ?>
            <?php if (! empty($role['description'])): ?>
                <div class="md:col-span-2">
                    <dt class="text-gray-500"><?= lang('Iam.field_description') ?></dt>
                    <dd class="mt-1 text-gray-900"><?= esc((string) $role['description']) ?></dd>
                </div>
            <?php endif; ?>
            <div>
                <dt class="text-gray-500"><?= lang('TableColumns.created_at') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($role['created_at'] ?? '-')) ?></dd>
            </div>
        </dl>
    </section>

    <section class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
        <h3 class="text-lg font-semibold text-gray-900"><?= lang('Iam.permissions_assigned') ?></h3>

        <?php if ($assignedItems === []): ?>
            <p class="mt-3 text-sm text-gray-500"><?= lang('Iam.permissions_none_assigned') ?></p>
        <?php else: ?>
            <ul class="mt-3 divide-y divide-gray-100 border border-gray-100 rounded-lg">
                <?php foreach ($assignedItems as $perm): ?>
                    <?php $pid = (string) ($perm['id'] ?? ''); ?>
                    <li class="flex items-center justify-between p-3 text-sm">
                        <div>
                            <code class="text-gray-900"><?= esc((string) ($perm['code'] ?? '-')) ?></code>
                            <?php if (! empty($perm['description'])): ?>
                                <span class="ml-2 text-gray-500"><?= esc((string) $perm['description']) ?></span>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="<?= route_to('admin.iam.roles.permissions.detach', $itemId, $pid) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_remove')) ?>');">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-xs text-red-600 hover:text-red-700"><?= lang('App.remove') ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($availableItems !== []): ?>
            <form method="post" action="<?= route_to('admin.iam.roles.permissions.attach', $itemId) ?>" class="mt-6 border-t border-gray-100 pt-4">
                <?= csrf_field() ?>
                <h4 class="text-sm font-medium text-gray-900 mb-2"><?= lang('Iam.permissions_available') ?></h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-72 overflow-y-auto pr-1">
                    <?php foreach ($availableItems as $perm): ?>
                        <label class="flex items-start gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="permission_ids[]" value="<?= esc((string) ($perm['id'] ?? '')) ?>" class="mt-1">
                            <span>
                                <code class="text-gray-900"><?= esc((string) ($perm['code'] ?? '-')) ?></code>
                                <?php if (! empty($perm['description'])): ?>
                                    <span class="block text-xs text-gray-500"><?= esc((string) $perm['description']) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="<?= esc(action_button_class()) ?>"><?= lang('Iam.permissions_attach') ?></button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
