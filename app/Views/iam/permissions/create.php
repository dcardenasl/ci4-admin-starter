<?php
/** @var array<int, array{id:int,name:string}> $applications */
$applications = $applications ?? [];
$selectedApp  = (int) old('application_id', $applications[0]['id'] ?? 0);
?>
<div class="mb-4">
    <a href="<?= route_to('admin.iam.permissions') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Iam.permissions_create')) ?></h3>

    <form method="post" action="<?= route_to('admin.iam.permissions.store') ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="application_id"><?= esc(lang('Iam.field_application')) ?> <span class="text-red-500">*</span></label>
                <?php if ($applications === []): ?>
                    <p class="mt-2 text-sm text-amber-700"><?= esc(lang('Iam.no_applications')) ?></p>
                    <input type="hidden" name="application_id" value="">
                <?php else: ?>
                    <select id="application_id" name="application_id" required class="<?= esc(input_class('application_id')) ?>">
                        <?php foreach ($applications as $app): ?>
                            <option value="<?= esc((string) $app['id']) ?>" <?= $selectedApp === (int) $app['id'] ? 'selected' : '' ?>>
                                <?= esc($app['name']) ?> <span class="text-gray-500">(#<?= (int) $app['id'] ?>)</span>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500"><?= esc(lang('Iam.application_help')) ?></p>
                <?php endif; ?>
                <?= render_field_error('application_id') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="code"><?= esc(lang('Iam.field_code')) ?> <span class="text-red-500">*</span></label>
                <input id="code" name="code" type="text" required maxlength="100"
                    value="<?= esc(old('code', '')) ?>"
                    placeholder="users.write"
                    class="<?= esc(input_class('code')) ?>">
                <?= render_field_error('code') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="resource"><?= esc(lang('Iam.field_resource')) ?> <span class="text-red-500">*</span></label>
                <input id="resource" name="resource" type="text" required maxlength="50"
                    value="<?= esc(old('resource', '')) ?>"
                    placeholder="users"
                    class="<?= esc(input_class('resource')) ?>">
                <?= render_field_error('resource') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="action"><?= esc(lang('Iam.field_action')) ?> <span class="text-red-500">*</span></label>
                <input id="action" name="action" type="text" required maxlength="50"
                    value="<?= esc(old('action', '')) ?>"
                    placeholder="write"
                    class="<?= esc(input_class('action')) ?>">
                <?= render_field_error('action') ?>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="description"><?= esc(lang('Iam.field_description')) ?></label>
            <textarea id="description" name="description" rows="3" maxlength="500"
                class="<?= esc(input_class('description')) ?>"><?= esc(old('description', '')) ?></textarea>
            <?= render_field_error('description') ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="<?= esc(action_button_class('primary')) ?>" <?= $applications === [] ? 'disabled' : '' ?>><?= esc(lang('App.create')) ?></button>
            <a href="<?= route_to('admin.iam.permissions') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('App.cancel')) ?></a>
        </div>
    </form>
</section>
