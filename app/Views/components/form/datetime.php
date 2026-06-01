<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value
 * @var bool|null $required
 * @var string|null $placeholder
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? '');
$placeholder = $placeholder ?? '';
$help = $help ?? '';
?>
<div>
    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name, 'attr') ?>">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>
    <input 
        id="<?= esc($name, 'attr') ?>" 
        name="<?= esc($name, 'attr') ?>" 
        type="datetime-local" 
        value="<?= esc($value) ?>" 
        class="<?= input_class($name) ?>"
        placeholder="<?= esc($placeholder, 'attr') ?>"
        <?= $required ? 'required' : '' ?>
        <?= field_aria_attrs($name, $required) ?>
    >
    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>
