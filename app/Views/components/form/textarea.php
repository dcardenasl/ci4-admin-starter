<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value
 * @var bool|null $required
 * @var int|null $rows
 * @var string|null $placeholder
 * @var string|null $help
 */

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? '');
$placeholder = $placeholder ?? '';
$help = $help ?? '';
$rows = $rows ?? 4;
?>
<div>
    <label class="block text-sm font-medium text-gray-700" for="<?= esc($name, 'attr') ?>">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>
    <textarea 
        id="<?= esc($name, 'attr') ?>" 
        name="<?= esc($name, 'attr') ?>" 
        rows="<?= (int) $rows ?>" 
        class="<?= input_class($name) ?> resize-y"
        placeholder="<?= esc($placeholder, 'attr') ?>"
        <?= $required ? 'required' : '' ?>
        <?= field_aria_attrs($name, $required) ?>
    ><?= esc($value) ?></textarea>
    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>
