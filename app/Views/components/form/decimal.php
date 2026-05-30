<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value
 * @var bool|null $required
 * @var float|string|null $step
 * @var float|int|null $min
 * @var float|int|null $max
 * @var string|null $placeholder
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? '');
$placeholder = $placeholder ?? '';
$help = $help ?? '';
$step = isset($step) ? 'step="' . esc($step, 'attr') . '"' : 'step="0.01"';
$min = isset($min) ? 'min="' . esc($min, 'attr') . '"' : '';
$max = isset($max) ? 'max="' . esc($max, 'attr') . '"' : '';
?>
<div>
    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name, 'attr') ?>">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>
    <div class="relative mt-1 rounded-lg shadow-sm">
        <input 
            id="<?= esc($name, 'attr') ?>" 
            name="<?= esc($name, 'attr') ?>" 
            type="number" 
            value="<?= esc($value) ?>" 
            class="<?= input_class($name) ?>"
            placeholder="<?= esc($placeholder, 'attr') ?>"
            <?= $step ?>
            <?= $min ?>
            <?= $max ?>
            <?= $required ? 'required' : '' ?>
            <?= field_aria_attrs($name, $required) ?>
        >
    </div>
    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>
