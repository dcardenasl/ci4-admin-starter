<?php $permission = $permission ?? []; ?>
<div class="mb-4">
    <a href="<?= route_to('admin.iam.permissions') ?>" class="text-sm text-brand-600 hover:text-brand-700">&larr; <?= lang('Iam.permissions_title') ?></a>
</div>

<?php if (! empty($error)): ?>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <p class="text-sm text-red-600"><?= esc($error) ?></p>
    </div>
<?php elseif (! empty($permission)): ?>
    <?php $itemId = (string) ($permission['id'] ?? ''); ?>

    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 max-w-3xl">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900"><?= lang('Iam.permissions_details') ?></h3>
            <div class="flex items-center gap-2">
                <a href="<?= route_to('admin.iam.permissions.edit', $itemId) ?>" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                <form method="post" action="<?= route_to('admin.iam.permissions.delete', $itemId) ?>" onsubmit="return confirm('<?= esc(lang('App.confirm_delete')) ?>');">
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
                <dt class="text-gray-500"><?= lang('Iam.field_name') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($permission['name'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('TableColumns.created_at') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($permission['created_at'] ?? '-')) ?></dd>
            </div>
        </dl>
    </section>
<?php endif; ?>
