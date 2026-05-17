<?php
/** @var array<int, array{id:int,name:string}> $applications */
$item                  = $item ?? [];
$applications          = $applications ?? [];
$allPermissions        = $allPermissions ?? [];
$assignedPermissionIds = $assignedPermissionIds ?? [];
$isSystem              = (bool) ($item['is_system'] ?? false);
$selectedApp           = old('application_id', $item['application_id'] ?? '');
$selectedApp           = $selectedApp === null ? '' : (string) $selectedApp;

$oldPermIds    = (array) old('permission_ids', $assignedPermissionIds);
$oldPermIdsStr = array_map('strval', $oldPermIds);

$grantableItems = $allPermissions;
if (! is_superadmin()) {
    $grantableItems = array_values(array_filter(
        $allPermissions,
        static fn (array $p): bool => actor_owns_permission((string) ($p['code'] ?? ''))
    ));
}

// Permissions already assigned that the actor cannot grant. They must remain
// attached on submit (we re-emit them as hidden inputs) — otherwise this form
// would silently strip them. Mirrors the "locked roles" pattern from users/edit.php.
$grantableIds       = array_map(static fn (array $p): string => (string) ($p['id'] ?? ''), $grantableItems);
$assignedIdsStr     = array_map('strval', $assignedPermissionIds);
$lockedAssignedIds  = array_values(array_diff($assignedIdsStr, $grantableIds));
?>
<div class="mb-4 flex items-center justify-between">
    <a href="<?= route_to('admin.iam.roles') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
    <?php if (! $isSystem): ?>
        <form method="post" action="<?= route_to('admin.iam.roles.delete', (string) ($item['id'] ?? '')) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_delete')) ?>');">
            <?= csrf_field() ?>
            <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
                <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
                <?= esc(lang('App.delete')) ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Iam.roles_edit')) ?></h3>

    <?php if ($isSystem): ?>
        <p class="mt-2 text-sm text-amber-700"><?= esc(lang('Iam.system_role_notice')) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= route_to('admin.iam.roles.update', (string) ($item['id'] ?? '')) ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="application_id"><?= esc(lang('Iam.field_application')) ?></label>
                <select id="application_id" name="application_id" class="<?= esc(input_class('application_id')) ?>">
                    <option value="" <?= $selectedApp === '' ? 'selected' : '' ?>><?= esc(lang('Iam.role_global_label')) ?></option>
                    <?php foreach ($applications as $app): ?>
                        <option value="<?= esc((string) $app['id']) ?>" <?= $selectedApp === (string) $app['id'] ? 'selected' : '' ?>>
                            <?= esc($app['name']) ?> (#<?= (int) $app['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= render_field_error('application_id') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="code"><?= esc(lang('Iam.field_code')) ?> <span class="text-red-500">*</span></label>
                <input id="code" name="code" type="text" required maxlength="100"
                    value="<?= esc(old('code', (string) ($item['code'] ?? ''))) ?>"
                    class="<?= esc(input_class('code')) ?>">
                <?= render_field_error('code') ?>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700" for="name"><?= esc(lang('Iam.field_name')) ?> <span class="text-red-500">*</span></label>
                <input id="name" name="name" type="text" required maxlength="100"
                    value="<?= esc(old('name', (string) ($item['name'] ?? ''))) ?>"
                    class="<?= esc(input_class('name')) ?>">
                <?= render_field_error('name') ?>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700" for="description"><?= esc(lang('Iam.field_description')) ?></label>
                <textarea id="description" name="description" rows="3" maxlength="500"
                    class="<?= esc(input_class('description')) ?>"><?= esc(old('description', (string) ($item['description'] ?? ''))) ?></textarea>
                <?= render_field_error('description') ?>
            </div>
        </div>

        <div class="pt-2 border-t border-gray-100">
            <span class="block text-sm font-medium text-gray-700"><?= esc(lang('Iam.permissions_assigned')) ?></span>
            <p class="text-xs text-gray-500 mt-1"><?= esc(lang('Iam.permissions_help_edit')) ?></p>

            <?php if ($grantableItems === []): ?>
                <p class="mt-2 text-sm text-gray-500 italic"><?= esc(lang('Iam.permissions_none_grantable')) ?></p>
            <?php else: ?>
                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2 max-h-72 overflow-y-auto pr-1">
                    <?php foreach ($grantableItems as $perm): ?>
                        <?php $pid = (string) ($perm['id'] ?? ''); ?>
                        <label class="inline-flex items-start gap-2 text-sm rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50">
                            <input type="checkbox" name="permission_ids[]" value="<?= esc($pid) ?>"
                                <?= in_array($pid, $oldPermIdsStr, true) ? 'checked' : '' ?>
                                class="mt-1 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <code class="font-medium text-gray-900"><?= esc((string) ($perm['code'] ?? '-')) ?></code>
                                <?php if (! empty($perm['description'])): ?>
                                    <span class="block text-xs text-gray-500"><?= esc((string) $perm['description']) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php // Sentinel: ensures `permission_ids` is always posted, so the form?>
            <?php // can clear all permissions when no checkboxes are ticked. Filtered?>
            <?php // out by RoleStoreRequest::normalizedPermissionIds (non-positive).?>
            <input type="hidden" name="permission_ids[]" value="">

            <?php foreach ($lockedAssignedIds as $lockedId): ?>
                <input type="hidden" name="permission_ids[]" value="<?= esc((string) $lockedId) ?>">
            <?php endforeach; ?>
            <?php if ($lockedAssignedIds !== []): ?>
                <p class="mt-2 text-xs text-amber-700"><?= esc(lang('Iam.permissions_some_locked')) ?></p>
            <?php endif; ?>
            <?= render_field_error('permission_ids') ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="<?= esc(action_button_class('primary')) ?>"><?= esc(lang('App.update')) ?></button>
            <a href="<?= route_to('admin.iam.roles') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('App.cancel')) ?></a>
        </div>
    </form>
</section>
