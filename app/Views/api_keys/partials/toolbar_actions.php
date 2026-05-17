<?php if (has_permission('apikeys.write')): ?>
    <a href="<?= route_to('admin.api_keys.create') ?>" class="<?= esc(action_button_class('primary')) ?>">
        <?= ui_icon('plus', 'h-3.5 w-3.5') ?>
        <?= lang('ApiKeys.create') ?>
    </a>
<?php else: ?>
    <span class="inline-flex items-center gap-2 rounded-md bg-amber-50 text-amber-800 border border-amber-200 px-3 py-1.5 text-xs">
        <?= ui_icon('lock', 'h-3.5 w-3.5') ?>
        <?= lang('ApiKeys.read_only_badge') ?>
    </span>
<?php endif; ?>
