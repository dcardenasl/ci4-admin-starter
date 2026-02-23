<?php
$limitFieldName ??= 'limit';
$limitLabel ??= lang('App.perPage');
$limitOptions ??= [10, 25, 50, 100];
$currentLimit = (string) (request()->getGet($limitFieldName) ?: '25');
?>
<div>
    <label class="<?= esc(filter_label_class()) ?>"><?= esc($limitLabel) ?></label>
    <select name="<?= esc($limitFieldName) ?>" class="<?= esc(filter_input_class()) ?>">
        <?php foreach ($limitOptions as $option): ?>
            <?php $value = (string) $option; ?>
            <option value="<?= esc($value) ?>" <?= $currentLimit === $value ? 'selected' : '' ?>><?= esc($value) ?></option>
        <?php endforeach; ?>
    </select>
</div>
