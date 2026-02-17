<?php
$actionUrl ??= current_url();
$clearUrl ??= $actionUrl;
$method ??= 'get';
$title ??= lang('App.filters');
$submitLabel ??= lang('App.search');
$submitFullWidth ??= false;
$hasFilters ??= request()->getGet() !== [];
$fieldsView ??= null;
$fieldsData ??= [];
?>
<form method="<?= esc($method) ?>" action="<?= esc($actionUrl) ?>" class="<?= esc(filter_panel_class()) ?>" data-table-filter-form="1">
    <div class="flex items-center justify-between gap-3">
        <h4 class="text-sm font-semibold text-gray-800"><?= esc($title) ?></h4>
        <?php if ($hasFilters): ?>
            <a href="<?= esc($clearUrl) ?>" class="text-xs font-medium text-brand-700 hover:text-brand-800 hover:underline"><?= lang('App.clearFilters') ?></a>
        <?php endif; ?>
    </div>

    <?php if (is_string($fieldsView) && $fieldsView !== ''): ?>
        <?= view($fieldsView, is_array($fieldsData) ? $fieldsData : []) ?>
    <?php endif; ?>

    <div class="mt-3 flex items-center justify-end gap-2">
        <button type="submit" class="<?= esc(filter_submit_button_class((bool) $submitFullWidth)) ?>">
            <?= ui_icon('search', 'h-3.5 w-3.5') ?>
            <?= esc($submitLabel) ?>
        </button>
    </div>
</form>
