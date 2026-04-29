<?php
/** @var array<int, array{value:string, label:string}> $categoryOptions */
$categoryOptions  = $categoryOptions ?? [];
$currentCategory  = (string) request()->getGet('category');
$currentFrom      = (string) request()->getGet('date_from');
$currentTo        = (string) request()->getGet('date_to');
$currentSizeMin   = (string) request()->getGet('size_min');
$currentSizeMax   = (string) request()->getGet('size_max');
?>
<div class="mt-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('App.search') ?></label>
        <input type="text" name="search" value="<?= esc((string) request()->getGet('search')) ?>" placeholder="<?= lang('Files.search_placeholder') ?>"
            class="<?= esc(filter_input_class()) ?>" data-table-debounce="350">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Files.category') ?></label>
        <select name="category" class="<?= esc(filter_input_class()) ?>">
            <?php foreach ($categoryOptions as $opt): ?>
                <?php $value = (string) ($opt['value'] ?? ''); ?>
                <option value="<?= esc($value) ?>" <?= $value === $currentCategory ? 'selected' : '' ?>>
                    <?= esc((string) ($opt['label'] ?? $value)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?= view('layouts/partials/filter_limit', ['limitOptions' => $limitOptions ?? [10, 25, 50, 100]]) ?>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Files.filter_date_from') ?></label>
        <input type="date" name="date_from" value="<?= esc($currentFrom) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div>
        <label class="<?= esc(filter_label_class()) ?>"><?= lang('Files.filter_date_to') ?></label>
        <input type="date" name="date_to" value="<?= esc($currentTo) ?>" class="<?= esc(filter_input_class()) ?>">
    </div>
    <div class="grid grid-cols-2 gap-2">
        <div>
            <label class="<?= esc(filter_label_class()) ?>"><?= lang('Files.filter_size_min_kb') ?></label>
            <input type="number" min="0" name="size_min" value="<?= esc($currentSizeMin) ?>" class="<?= esc(filter_input_class()) ?>" placeholder="0">
        </div>
        <div>
            <label class="<?= esc(filter_label_class()) ?>"><?= lang('Files.filter_size_max_kb') ?></label>
            <input type="number" min="0" name="size_max" value="<?= esc($currentSizeMax) ?>" class="<?= esc(filter_input_class()) ?>" placeholder="∞">
        </div>
    </div>
</div>
