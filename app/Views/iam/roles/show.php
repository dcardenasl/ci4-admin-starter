<?php
$role = $role ?? [];
$assignedItems = $assignedPermissions ?? [];

$canModify = can_modify_role($role);
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
                <?php if ($canModify): ?>
                    <a href="<?= route_to('admin.iam.roles.edit', $itemId) ?>" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                <?php endif; ?>
                <?php if (empty($role['is_system']) || is_superadmin()): ?>
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
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Iam.permissions_assigned') ?></h3>
            <?php if ($canModify): ?>
                <a href="<?= route_to('admin.iam.role_permissions') ?>?tab=<?= urlencode($itemId) ?>" class="text-sm text-brand-600 hover:text-brand-700"><?= esc(lang('Iam.permissions_edit_link')) ?></a>
            <?php endif; ?>
        </div>

        <?php if ($assignedItems === []): ?>
            <p class="mt-3 text-sm text-gray-500"><?= lang('Iam.permissions_none_assigned') ?></p>
        <?php else: ?>
            <ul class="mt-3 divide-y divide-gray-100 border border-gray-100 rounded-lg">
                <?php foreach ($assignedItems as $perm): ?>
                    <li class="p-3 text-sm">
                        <code class="text-gray-900"><?= esc((string) ($perm['code'] ?? '-')) ?></code>
                        <?php if (! empty($perm['description'])): ?>
                            <span class="ml-2 text-gray-500"><?= esc((string) $perm['description']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>
