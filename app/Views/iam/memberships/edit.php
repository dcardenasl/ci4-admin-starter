<?php
/** @var array<int, array{id:int,name:string}> $applications */
/** @var array<int, array{id:int,email:string,first_name:string,last_name:string,label:string}> $users */
$item         = $item ?? [];
$applications = $applications ?? [];
$users        = $users ?? [];
$selectedApp  = (int) old('application_id', (int) ($item['application_id'] ?? 0));
$selectedUser = (int) old('user_id', (int) ($item['user_id'] ?? 0));
?>
<div class="mb-4 flex items-center justify-between">
    <a href="<?= route_to('admin.iam.memberships') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
    <form method="post" action="<?= route_to('admin.iam.memberships.delete', (string) ($item['id'] ?? '')) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_delete')) ?>');">
        <?= csrf_field() ?>
        <button type="submit" class="<?= esc(action_button_class('danger')) ?>">
            <?= ui_icon('trash', 'h-3.5 w-3.5') ?>
            <?= esc(lang('App.delete')) ?>
        </button>
    </form>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Iam.app_user_memberships_edit')) ?></h3>

    <form method="post" action="<?= route_to('admin.iam.memberships.update', (string) ($item['id'] ?? '')) ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="user_id"><?= esc(lang('Iam.field_user')) ?> <span class="text-red-500">*</span></label>
                <?php if ($users === []): ?>
                    <p class="mt-2 text-sm text-amber-700"><?= esc(lang('Iam.no_users')) ?></p>
                    <input type="hidden" name="user_id" value="<?= esc((string) $selectedUser) ?>">
                <?php else: ?>
                    <select id="user_id" name="user_id" required class="<?= esc(input_class('user_id')) ?>">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= esc((string) $u['id']) ?>" <?= $selectedUser === (int) $u['id'] ? 'selected' : '' ?>>
                                <?= esc($u['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?= render_field_error('user_id') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="application_id"><?= esc(lang('Iam.field_application')) ?> <span class="text-red-500">*</span></label>
                <?php if ($applications === []): ?>
                    <p class="mt-2 text-sm text-amber-700"><?= esc(lang('Iam.no_applications')) ?></p>
                    <input type="hidden" name="application_id" value="<?= esc((string) $selectedApp) ?>">
                <?php else: ?>
                    <select id="application_id" name="application_id" required class="<?= esc(input_class('application_id')) ?>">
                        <?php foreach ($applications as $app): ?>
                            <option value="<?= esc((string) $app['id']) ?>" <?= $selectedApp === (int) $app['id'] ? 'selected' : '' ?>>
                                <?= esc($app['name']) ?> (#<?= (int) $app['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?= render_field_error('application_id') ?>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700" for="status"><?= esc(lang('Iam.field_status')) ?></label>
                <select id="status" name="status" class="<?= esc(input_class('status')) ?>">
                    <?php $current = (string) old('status', (string) ($item['status'] ?? 'active')); ?>
                    <?php foreach (['active', 'inactive', 'suspended', 'pending'] as $opt): ?>
                        <option value="<?= esc($opt) ?>" <?= $current === $opt ? 'selected' : '' ?>><?= esc(localized_status($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= render_field_error('status') ?>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="<?= esc(action_button_class('primary')) ?>"><?= esc(lang('App.update')) ?></button>
            <a href="<?= route_to('admin.iam.memberships') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('App.cancel')) ?></a>
        </div>
    </form>
</section>
