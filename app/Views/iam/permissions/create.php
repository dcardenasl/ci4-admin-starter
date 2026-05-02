<div class="mb-4">
    <a href="<?= route_to('admin.iam.permissions') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= esc(lang('App.back')) ?></a>
</div>

<section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
    <h3 class="text-lg font-semibold text-gray-900"><?= esc(lang('Iam.permissions_create')) ?></h3>

    <form method="post" action="<?= route_to('admin.iam.permissions.store') ?>" class="mt-4 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="name"><?= esc(lang('Iam.field_name')) ?> <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text" required maxlength="255" value="<?= esc(old('name', '')) ?>"
                class="<?= esc(input_class('name')) ?>">
            <?= render_field_error('name') ?>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="<?= esc(action_button_class('primary')) ?>"><?= esc(lang('App.create')) ?></button>
            <a href="<?= route_to('admin.iam.permissions') ?>" class="<?= esc(action_button_class()) ?>"><?= esc(lang('App.cancel')) ?></a>
        </div>
    </form>
</section>
