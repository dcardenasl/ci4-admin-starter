<?php
/**
 * @var string $name
 * @var string $label
 * @var mixed|null $value
 * @var bool|null $required
 * @var string|null $help
 */

declare(strict_types=1);

helper('form');

$required = $required ?? false;
$value = old($name, $value ?? false);
$help = $help ?? '';
$checked = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

if ($checked === null) {
    $checked = (bool) $value;
}
?>
<div>
    <span class="block text-sm font-medium text-gray-700">
        <?= lang($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500" aria-hidden="true">*</span>
        <?php endif; ?>
    </span>

    <input type="hidden" name="<?= esc($name, 'attr') ?>" value="0">

    <label class="mt-2 inline-flex cursor-pointer items-center gap-3">
        <input
            id="<?= esc($name, 'attr') ?>"
            name="<?= esc($name, 'attr') ?>"
            type="checkbox"
            value="1"
            class="peer sr-only"
            <?= $checked ? 'checked' : '' ?>
            <?= $required ? 'required' : '' ?>
            <?= field_aria_attrs($name, $required) ?>
        >
        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-gray-200 transition-colors peer-checked:bg-brand-600 peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2">
            <span class="inline-block h-5 w-5 translate-x-0.5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
        </span>
        <span class="text-sm font-medium text-gray-700">
            <?= esc($checked ? 'Enabled' : 'Disabled') ?>
        </span>
    </label>

    <?php if ($help): ?>
        <p class="mt-1 text-xs text-gray-500"><?= lang($help) ?></p>
    <?php endif; ?>
    <?= render_field_error($name) ?>
</div>
