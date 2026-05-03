<?php
/** @var array<int, array{id:int,name:string}> $applications */
$applications = $applications ?? [];
$selectedApp  = old('application_id', $applications[0]['id'] ?? '');
?>
<div class="mb-4">
    <a href="<?= route_to('admin.iam.roles') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Iam.roles_create')) ?></h3>

    <form method="post" action="<?= route_to('admin.iam.roles.store') ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="application_id"><?= esc(lang('Iam.field_application')) ?></label>
                <select id="application_id" name="application_id" class="<?= esc(input_class('application_id')) ?>">
                    <option value="" <?= ((string) $selectedApp === '') ? 'selected' : '' ?>><?= esc(lang('Iam.role_global_label')) ?></option>
                    <?php foreach ($applications as $app): ?>
                        <option value="<?= esc((string) $app['id']) ?>" <?= ((string) $selectedApp === (string) $app['id']) ? 'selected' : '' ?>>
                            <?= esc($app['name']) ?> (#<?= (int) $app['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-gray-500"><?= esc(lang('Iam.application_help')) ?></p>
                <?= render_field_error('application_id') ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="code"><?= esc(lang('Iam.field_code')) ?> <span class="text-red-500">*</span></label>
                <input id="code" name="code" type="text" required maxlength="100"
                    value="<?= esc(old('code', '')) ?>"
                    placeholder="editor"
                    class="<?= esc(input_class('code')) ?>">
                <?= render_field_error('code') ?>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700" for="name"><?= esc(lang('Iam.field_name')) ?> <span class="text-red-500">*</span></label>
                <input id="name" name="name" type="text" required maxlength="100"
                    value="<?= esc(old('name', '')) ?>"
                    placeholder="Editor"
                    class="<?= esc(input_class('name')) ?>">
                <?= render_field_error('name') ?>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700" for="description"><?= esc(lang('Iam.field_description')) ?></label>
                <textarea id="description" name="description" rows="3" maxlength="500"
                    class="<?= esc(input_class('description')) ?>"><?= esc(old('description', '')) ?></textarea>
                <?= render_field_error('description') ?>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="<?= esc(action_button_class('primary')) ?>"><?= esc(lang('App.create')) ?></button>
            <a href="<?= route_to('admin.iam.roles') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('App.cancel')) ?></a>
        </div>
    </form>
</section>
