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
                <?php if (is_superadmin()): ?>
                    <a href="<?= route_to('admin.iam.permissions.edit', $itemId) ?>" class="<?= esc(action_button_class()) ?>"><?= lang('App.edit') ?></a>
                    <form method="post" action="<?= route_to('admin.iam.permissions.delete', $itemId) ?>" x-data @submit.prevent="$store.confirm.show('<?= esc(confirm_delete_message($permission['code'] ?? $permission['resource'] ?? null), 'js') ?>', () => $el.submit())">
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
                <dd class="mt-1 text-gray-900"><code class="text-xs"><?= esc((string) ($permission['code'] ?? '-')) ?></code></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_application') ?></dt>
                <dd class="mt-1 text-gray-900">
                    <?= esc((string) ($permission['application_name'] ?? '')) ?>
                    <?php if (! empty($permission['application_id'])): ?>
                        <span class="text-gray-500 text-xs">(#<?= (int) $permission['application_id'] ?>)</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_resource') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($permission['resource'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('Iam.field_action') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($permission['action'] ?? '-')) ?></dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-gray-500"><?= lang('Iam.field_description') ?></dt>
                <dd class="mt-1 text-gray-900 whitespace-pre-line"><?= esc((string) ($permission['description'] ?? '-')) ?></dd>
            </div>
            <div>
                <dt class="text-gray-500"><?= lang('TableColumns.created_at') ?></dt>
                <dd class="mt-1 text-gray-900"><?= esc((string) ($permission['created_at'] ?? '-')) ?></dd>
            </div>
        </dl>
    </section>
<?php endif; ?>
